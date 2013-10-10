<?php

namespace ISIS\Exception;

class LoaderFileRequirementException extends \ErrorException {
    
    public function __construct( $ext) {
        parent::__construct('Can\'t find the data file with the extension ' . $ext . ' in ' . $path . '.');
    }
}