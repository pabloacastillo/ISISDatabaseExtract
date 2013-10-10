<?php

namespace ISIS\Exception;

class LoaderPathException extends \ErrorException {
    
    public function __construct($path) {
        parent::__construct('The path ('. $path .') does not exits or not it\'s a directory.');
    }
}