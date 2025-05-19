<?php

require_once('config.php');

//подключение к базе данных, вывод ошибки
$con = mysqli_connect($config['db']['host'], $config['db']['user'], $config['db']['password'], $config['db']['database']);

if ($con === false) {
    print("Ошибка подключения: " . mysqli_connect_error());
    die();
}

mysqli_set_charset($con, "utf8");