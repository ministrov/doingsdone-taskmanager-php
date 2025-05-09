<?php
require_once("helpers.php");
require_once("data.php");

var_dump($cat);

$rand_key = array_rand($cites);

console_log($rand_key);

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
