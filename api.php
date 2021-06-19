<?php
require_once 'vendor/autoload.php';

ini_set("display_errors", "On");
ini_set("memory_limit", PHP_INT_MAX . 'MB');
error_reporting(E_ALL);

header('Content-Type: application/json');

print_r(json_encode($_REQUEST));
?>