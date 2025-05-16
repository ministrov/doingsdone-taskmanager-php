<?php
require_once("helpers.php");
require_once("data.php");
require_once("init.php");

if (!$connect) {
  $error = mysqli_connect_error();
} else {
  $sql = "SELECT * FROM projects";
  $result = mysqli_query($connect, $sql);

  if ($result) {
    $projects = mysqli_fetch_all($result, MYSQLI_ASSOC);
  } else {
    $error = mysqli_error($connect);
  }
}

console_log($projects);

if (!$connect) {
  $error = mysqli_connect_error();
} else {
  $sql = "SELECT * FROM tasks";
  $result = mysqli_query($connect, $sql);

  if ($result) {
    $tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
  } else {
    $error = mysqli_error($connect);
  }
}

console_log($tasks);

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
