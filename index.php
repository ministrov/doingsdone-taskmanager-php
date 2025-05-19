<?php

require_once("init_db.php");
require_once("helpers.php");
require_once("functions.php");

session_start();

$user_id = check_auth($config['title']);

//задачи пользователя
$tasks_and_errors = get_tasks($con, $user_id);
$tasks = $tasks_and_errors['tasks'];
$error_message = $tasks_and_errors['error_message'];

//смена статуса задачи выполнено/не выполнено
if (isset($_GET['task_id'])) {
  change_task_status($con, (int)$_GET['task_id']);
}

//фильтр дат
$date_filter = filter_input(INPUT_GET, 'filter', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$tasks = filter_tasks_by_date($tasks, $date_filter);

$page_content = include_template('main.php', [
  'user_projects'       => get_user_projects($con, $user_id),
  'tasks'               => $tasks,
  'error_message'       => $error_message,
  'all_user_tasks'      => get_all_user_projects($con, $user_id),
  'id_task_time'        => $date_filter,
  'show_complete_tasks' => isset($_GET['show_completed']) ? (int)$_GET['show_completed'] : 1
]);

$layout_content = include_template(
  'layout.php',
  [
    'content'   => $page_content,
    'title'     => $config['title'],
    'name_user' => get_user_name($con, $user_id)
  ]
);

print($layout_content);
