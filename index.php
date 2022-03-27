<?php
/**
 * This file is intended for testing the host.php file.
 */
include 'host.php';
include 'common.php';

$syno = new SynologyUloztoFree(
    "", // TODO:
    "http://localhost:8000",
    "4",
    null);

$results = $syno->getInitiatedUrl();
print_r($results);
print_r("\n");
$results = $syno->getDownloadUrl();
print_r($results);
print_r("\n");
print_r("\n");

$results = $syno->GetDownloadInfo();
print_r($results);
print_r("\n");

?>