<?php
require_once("helpers.php");
require_once("data.php");

// $config = ['debug' => true, 'version' => '1.0'];

// $data = [
//     'username' => 'johndoe',
//     'email' => 'john@example.com',
//     'age' => 30
// ];

// extract($data);

// echo $username; // Outputs: johndoe
// echo $email;    // Outputs: john@example.com
// echo $age;

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
