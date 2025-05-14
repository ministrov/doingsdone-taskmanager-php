<?php

// $settings = [
//   "host" => "MySQL-8.0",
//   "user" => "child_new_admin_2",
//   "password" => "111",
//   "database" => "doings_done"
// ];

// $connect = mysqli_connect($settings["host"], $settings["user"], $settings["password"], $settings["database"]);

// if (!$connent) {
//   print("Ошибка подключения: " . mysqli_connect_error());
//     die();
// }
mysqli_set_charset($connect, "utf8");