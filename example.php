<?php
include './ISIS/Exception/ExtractDataNVFBASEException.php';
include './ISIS/Exception/LoaderFileReadException.php';
include './ISIS/Exception/LoaderFileRequirementException.php';
include './ISIS/Exception/LoaderPathException.php';
include './ISIS/Database/Extract.php';
include './ISIS/Database/Loader.php';

/**
 * 
 * $loader = new ISIS\Database\Loader('./Data/isis');
 * $database = $loader->extract();
*/

/**
 * $loader = new ISIS\Database\Loader('./Data/isis');
 * $database = new \ISIS\Database\Extract($loader);
 */

$database = new \ISIS\Database\Extract('./Data/isis');

foreach ($database as $record){
    var_dump($record);
}

$mfn = 2;

/**
 * Yo can also access to record using;
 */
// var_dump( $database->fetch($mfn));

?>
