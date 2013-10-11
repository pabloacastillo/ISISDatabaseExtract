<?php

namespace ISIS\Database;

class Extract implements \Countable, \Iterator {
    
    /**
     * Loader instance.
     * @var Loader
     */
    private $res;

    /**
     * Count of records in DB.
     * @var integer
     */
    private $count;

    /**
     * Standad Subfield seperator in CDS/ISIS
     */
    const ISIS_SUBFIELD_DELIMITER = '^';
    
    /**
     * Key for field indicator in array.
     * @see fetch()
     */
    const RECORDEXTRACT_SUBFIELD_IND1 = 'i1';
    
    /**
     * @see RECORDEXTRACT_SUBFIELD_IND1
     */
    const RECORDEXTRACT_SUBFIELD_IND2 = 'i2';    
    
    
    public function __construct( $db) {
        
        if ( ! $db instanceof Loader) {
            if ( !is_string($db))
                throw new \UnexpectedValueException();

            $db = new Loader($db);
        }
        
        $this->res = $db;
        
        fseek( $this->res[Loader::FILE_MST], 4, 0);
        $buffer = fread( $this->res[Loader::FILE_MST], 4);
        $unpacked = unpack('V', $buffer);
        $this->count = array_shift($unpacked);                
    }
    
    /**
     * Fetch a record by $mfn (Master File Number)
     * @param integer $mfn
     * @return mixed Return array of data grouped by fields or Null for empty record or error.
     * @throws \ISIS\Exception\ExtractDataNVFBASEException
     */
    public function fetch( $mfn) {
        $buffer = null;
        $mfnpos = ($mfn + intval(($mfn-1)/127))*4;
        
        fseek( $this->res[Loader::FILE_XRF],$mfnpos,0);
        
        $buffer = fread( $this->res[Loader::FILE_XRF], 4);
        
        $pointer = unpack("V",$buffer);
        
        if (! $pointer) {
            return null;
		}
        
        $pointer = array_shift($pointer);        
        
        # check for logically deleted record
	if ( $pointer & 0x80000000) {
		return null;
	}        
        
        $XRFMFB = intval( intval($pointer) / 2048 );
	$XRFMFP = $pointer - ( $XRFMFB * 2048);
        
        $blk_off = (($XRFMFB - 1) * 512) + ($XRFMFP % 512);
        
        fseek( $this->res[Loader::FILE_MST],$blk_off,0);
        
        $buffer = fread( $this->res[Loader::FILE_MST], 4);
        $value = unpack("V",$buffer);
        $value= array_shift( $value);
        
        
        if ( $value != $mfn) {
		if ($value == 0) {			
			return null;
		}
		return null;
	}
        
        $buffer = fread( $this->res[Loader::FILE_MST], 14);
        
        //my ($MFRL,$MFBWB,$MFBWP,$BASE,$NVF,$STATUS) = unpack("vVvvvv", $buff)
        $unpacked = unpack("v*", $buffer);

        $MFRL = array_shift($unpacked);
        $MFBWB = array_shift($unpacked);
        array_shift($unpacked);
        $MFBWP  = array_shift($unpacked);
        $BASE = array_shift($unpacked);
        $NVF = array_shift($unpacked);
        $STATUS  = array_shift($unpacked);;
        
        if ($BASE != 18 + 6 * $NVF) {
            throw new \ISIS\Exception\ExtractDataNVFBASEException();
        }
        
        $FieldPOS = array();
	$FieldLEN = array();
	$FieldTAG = array();
        
        $buffer = fread( $this->res[Loader::FILE_MST], 6 * $NVF);
        
        $rec_len = 0;
        
        for ( $i = 0 ; $i < $NVF ; $i++) {

		//my ($TAG,$POS,$LEN) 
		$unpacked = unpack("v*", substr($buffer,$i * 6, 6));
                                
		$FieldTAG[] = $this->zeroLeftFill( $unpacked[1]);
		$FieldPOS[] = $unpacked[2];
		$FieldLEN[] = $unpacked[3];

		$rec_len += $unpacked[3];
	}
        
        $buffer = fread( $this->res[Loader::FILE_MST],$rec_len);
        
        $data = array();
        $last_tag = null;
        
        for ( $i = 0 ; $i < $NVF ; $i++) {
		# skip zero-sized fields
		if ($FieldLEN[$i] == 0)
                    continue;

		$v = substr($buffer,$FieldPOS[$i],$FieldLEN[$i]);                                
                $extracted = $this->subFieldExtract($v);      
                
                if ( $extracted ){
                    if ( is_array($extracted) && isset($extracted[self::RECORDEXTRACT_SUBFIELD_IND1]) ) {
                        if ( count($extracted) < 3){
                            $last_tag = $FieldTAG[$i];
                            continue;
                        }
                    }
                    
                    if ( !isset($data[$FieldTAG[$i]]))
                    {
                        $data[$FieldTAG[$i]] = array();
                    }
                    
                   
                    $data[$FieldTAG[$i]][] = $extracted;
 
                }                                
                
                $last_tag = $FieldTAG[$i];
	}
        
        return $data;
    }
    
    public function zeroLeftFill( $v) {
        switch (strlen($v)) {
            case 1:
                return "00{$v}";
                break;
            case 2:
                return "0{$v}";
                break;
            default :
                return $v;
                break;
        }
    }
    
    /**
     * Prepara contenido del campo o subcampos
     * 
     * @param string $f
     * @return mixed SubField 
     */
    private function subFieldExtract ( $f)
    {                        
        if (strpos($f, self::ISIS_SUBFIELD_DELIMITER) !== false )
        {    
            $matchs = array();
            
            preg_match_all('#\\'.self::ISIS_SUBFIELD_DELIMITER.'([a-zA-Z0-9][^\\'.self::ISIS_SUBFIELD_DELIMITER.']+)#i', $f, $matchs);

            if ( count($matchs) != 2) 
                return null;                        
            
            $subfield = array();
            
            if ( strpos($f, self::ISIS_SUBFIELD_DELIMITER) == 1) {
                $subfield[self::RECORDEXTRACT_SUBFIELD_IND1] = substr($f,0,1);
                $subfield[self::RECORDEXTRACT_SUBFIELD_IND2] = substr($f,1,1);
            }
            
            //$_sfs = explode(self::ISIS_SUBFIELD_DELIMITER, $f);
            foreach ( $matchs[1] as $v) {
                
                if ( isset($subfield[strtolower(substr($v,0,1))])) { //repetidos
                    if ( !is_array($subfield[strtolower(substr($v,0,1))]))
                            $subfield[strtolower(substr($v,0,1))] = array($subfield[strtolower(substr($v,0,1))]);
                    
                    $subfield[strtolower(substr($v,0,1))][] = $this->IsisDecode( trim(substr($v, 1)));                    
                } else {
                    $subfield[strtolower(substr($v,0,1))] = $this->IsisDecode( trim(substr($v, 1)));                
                }
            }
            return $subfield;
        } else return $this->IsisDecode (trim($f));        
    }
    
    /**
     * Traduce los caracteres de Isis
     * @param string $var
     * @return string 
     */
    protected function IsisDecode ( $var)
    {
        $new = NULL;
        for ( $i=0; $i<strlen( $var); $i++)
                $new .= ( $this->__decode_letter($var[$i]));        
        return $new;
    }    

    /**
     * @todo Completar el codigo de caracteres problematico
     * @param char $l
     * @return char 
     */
    private function __decode_letter( $l)
    {        
        if ( preg_match("#^[a-zA-Z0-9\^\[\]\{\}@_\'\#\$\%\&\*\(\)\~\`\"\+\=\:\.\?\<\>\/-]$#", $l ) )
            return $l;
        //Mas eficiente que usar un Array, ocupa más codigo pero tiene ahorro en recursos cuando se trata analizas grandes cantidades de ISOS
        switch (ord($l)) {                                                         
            //A
            case 131: return 'â'; case 132: return 'ä'; case 142: return 'Ä';
            case 160: return 'á'; case 181: return 'Á'; case 182: return 'Â';
            case 183: return 'À';                
            //E
            case 130: return 'é'; case 136: return 'ê'; case 137: return 'ë';
            case 138: return 'è'; case 144: return 'É'; case 212: return 'È';                
            //I
            case 139: return 'ï'; case 140: return 'î'; case 141: return 'ì';
            case 161: return 'í'; case 214: return 'Í'; case 215: return 'Î';
            case 216: return 'Ï';            
            //O    
            case 147: return 'ô'; case 148: return 'ö'; case 149: return 'ò';                        
            case 224: return 'Ó'; case 226: return 'Ô'; case 227: return 'Ò'; 
            case 162: return 'ó';                
            //U
            case 150: return 'û'; case 151: return 'ù'; case 152: return 'ù';                
            case 154: return 'Ü'; case 233: return 'Ú'; case 234: return 'Û';    
            case 235: return 'Ù'; case 163: return 'ú'; case 129: return 'ü';                
            //RESTO    
            case 128: return 'Ç'; case 135: return 'ç'; case 164: return 'ñ';
            case 165: return 'Ñ'; case 169: return '®'; case 194: return '¿';    
	    case 173: return '¡'; case 168: return '¿';  
            //Def    
            default:                 
                return ' ';
        }
    }
    
    public function count() {
        return $this->count-1;
    }

    protected $current = 1;

    public function current() {
        return $this->fetch($this->key());
    }

    public function key() {
        return $this->current;
    }

    public function next() {        
            $this->current++;
    }

    public function rewind() {
        $this->current = 1;
    }

    public function valid() {
        return ( $this->current < $this->count());
    }
}