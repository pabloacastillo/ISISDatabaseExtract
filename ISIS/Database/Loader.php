<?php

namespace ISIS\Database;

class Loader implements \ArrayAccess{
    
    const FILE_MST = 'mst';
    const FILE_XRF = 'xrf';
    const FILE_FDT = 'fdt';

    private $path;

    private $files;
    
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
            $this->resources[$ext] = fopen( $ext, 'rb');
            
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
                $files[ $matchs[1]] = $file;
            }
        }
	        
        //verify loaded files
        foreach ($extensions as $extension) {
            if ( !isset($files[ $extension])){
                throw new \ISIS\Exception\LoaderFileRequirementException($extension);
            }
        }
        
        $this->files = $files;
    }
    
    public function extract() {
        return new Extract($self);
    }

    public function offsetExists($offset) {
        return (isset($this->resources[$offset]));
    }

    public function offsetGet($offset) {
        return $this->resources[$offset];
    }

    public function offsetSet($offset, $value) {
        throw new \OutOfRangeException();
    }

    public function offsetUnset($offset) {
        throw new \BadMethodCallException();
    }
}