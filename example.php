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

echo '<pre>';
// CALL NEW FUNCTION TO MAP FIELD NAMES
$database->fdt_definitions();
$defs=$database->definitions;

foreach ($database as $record){
    foreach ($record as $k => $v) {
    	$nk=$defs[intval($k)]['name'];
    	$record[$nk]=$v;
    	unset($record[$k]);
    }
    print_r($record);
}

$mfn = 2;

/**
 * Yo can also access to record using;
 */
// var_dump( $database->fetch($mfn));
//print_r( $database->fetch($mfn));

?>
