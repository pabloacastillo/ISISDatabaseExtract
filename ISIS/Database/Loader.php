<?php

namespace ISIS\Database;

/**
 *
 */
class Loader implements \ArrayAccess{
    
    /**
     * Extension of Master file
     */
    const FILE_MST = 'mst';
    
    /**
     * Extension of Crossreference file (Master file index)
     */
    const FILE_XRF = 'xrf';
    
    /**
     * Field Definition Table
     */
    const FILE_FDT = 'fdt';

    /**
     * Path to database folder.
     * @var string
     */
    private $path;

    /**
     * Path to files by extension.
     * @var array
     */
    private $files;
    
    /**
     * fopen Resources for files into $files.
     * @var type 
     */
    private $resources;

    /**
     * 
     * @param type $path Path to database folder
     */
    public function __construct( $path) {
                
        if ( !is_dir($path)) {
            throw new \ISIS\Exception\LoaderPathException($path);
        }
        
        $this->path = $path;      
        
        $this->load();
    }
    
    /**
     * Open files
     * @throws \ISIS\Exception\LoaderFileReadException
     */
    public function load() {
        if ( !$this->files)
            $this->scanFiles ();
        
        foreach ($this->files as $ext => $file) {
            $this->resources[$ext] = fopen( $file, 'rb');
            
            if ( !$this->resources[$ext]) {
                throw new \ISIS\Exception\LoaderFileReadException($file);
            }
        }                                
    }

    /**
     * Look in path for necessary files.
     * @throws \ISIS\Exception\LoaderFileRequirementException
     */
    private function scanFiles() {
        $files = array();
        
        $extensions = array(
            //TODO: Fields declaration file  'FDT',
            self::FILE_MST,
            self::FILE_XRF,
        );
        
        $ext_regex = '/\.(' . implode('|', $extensions) . ')$/i';
        
        //scan for data file in $path
        foreach ( glob( $this->path . DIRECTORY_SEPARATOR . '*') as $file )
	{
            $matchs = array();
            if (preg_match( $ext_regex, $file, $matchs))
            {                
                $files[strtolower($matchs[1])] = $file;
            }
        }
	        
        //verify loaded files
        foreach ($extensions as $extension) {
            if ( !isset($files[ $extension])){
                throw new \ISIS\Exception\LoaderFileRequirementException($extension, $this->path);
            }
        }
        
        $this->files = $files;
    }
    
    /**
     * Return a instance of Extract.
     * @return \ISIS\Database\Extract
     */
    public function extract() {
        return new Extract(self);
    }

    /**
     * Check if $offset exist in $resources.
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset) {
        return (isset($this->resources[$offset]));
    }

    /**
     * Get a fopen resource for extension passed by $offset.
     * @param string $offset
     * @return fopen Resource
     */
    public function offsetGet($offset) {
        return $this->resources[$offset];
    }

    /**
     * @ignore
     */
    public function offsetSet($offset, $value) {
        throw new \OutOfRangeException();
    }

    /**
     * @ignore
     */
    public function offsetUnset($offset) {
        throw new \BadMethodCallException();
    }
}