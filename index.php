<?php
require_once("helpers.php");
require_once("data.php");

console_log(get_time_left('19-04-19'));

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
