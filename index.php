<?php
require_once("helpers.php");
require_once("data.php");

$settings = [
  "host" => "MySQL-8.0",
  "user" => "child_new_admin_2",
  "password" => "111",
  "database" => "doings_done"
];

$connect = mysqli_connect($settings["host"], $settings["user"], $settings["password"], $settings["database"]);
mysqli_set_charset($connect, "utf8");

if (!$connect) {
  print("Ошибка подключения: " . mysqli_connect_error());
} else {
  print("Соединение установлено");
}



$show_complete_tasks = rand(0, 1);

$page_content = include_template("main.php", [
    "projects" => $projects,
    "tasks" => $tasks,
    "show_complete_tasks" => $show_complete_tasks
]);

$layout_content = include_template("layout.php", [
    "title" => "Главная",
    "content" => $page_content
]);

print($layout_content);
