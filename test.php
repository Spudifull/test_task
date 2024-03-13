<?php

use FpDbTest\Database;
use FpDbTest\DatabaseTest;
use FpDbTest\SQLFormatter;

spl_autoload_register(function ($class) {
    if (class_exists($class, false)) {
        return;
    }

    $a = array_slice(explode('\\', $class), 1);
    if (!$a) {
        error_log("Unable to autoload class: $class");
        throw new Exception();
    }
    $filename = implode('/', [__DIR__, ...$a]) . '.php';
    require_once $filename;
});

$mysqli = @new mysqli('localhost', 'root', 'root', 'mydatabase', 3306);
if ($mysqli->connect_errno) {
    error_log("Wrong database");
    throw new Exception($mysqli->connect_error);
}

$db = new Database($mysqli);
$test = new DatabaseTest($db);
try {
    $test->testBuildQuery();
} catch (Exception $e) {
    error_log($e->getMessage());
    exit('Произошла ошибка.');
}

exit('OK');
