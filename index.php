<?php
require_once("helpers.php");
require_once("data.php");

// $cat = [
//   "name" => "Barsik",
//   "color" => "Yellow",
//   "weight" => 12,
//   "tail" => "short"
// ];

// console_log(isset($cat["name"]));
// console_log(isset($cat["fodfod"]));

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
