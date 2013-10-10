<?php

namespace ISIS\Exception;

class LoaderFileReadException extends \ErrorException {
    public function __construct($path) {
        parent::__construct('Error to open ' . $path . ' file.');
    }
}