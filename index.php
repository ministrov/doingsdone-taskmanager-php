<?php
require_once("helpers.php");
require_once("functions.php");
require_once("data.php");
require_once("init.php");

// $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
$user_id = $settings["user"];

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

$show_complete_tasks = rand(0, 1);

console_log($show_complete_tasks);

$page_content = include_template("main.php", [
  'user_projects'       => get_user_projects($connect, $user_id),
  'tasks'               => $tasks,
  // 'error_message'       => $error_message,
  'all_user_tasks'      => get_all_user_projects($connect, $user_id),
  // 'id_task_time'        => $date_filter,
  'show_complete_tasks' => $show_complete_tasks
]);

$layout_content = include_template("layout.php", [
  "title" => "Главная",
  "content" => $page_content
]);

print($layout_content);
