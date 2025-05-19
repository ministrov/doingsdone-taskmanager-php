<?php

/**
 * Проверка авторизации пользователя
 *
 * @param $title
 * @return mixed|void
 */
function check_auth($title)
{
  if (!isset($_SESSION["user"]["id"])) {
    $page_content = include_template('guest.php');
    $layout_content = include_template(
      'layout.php',
      [
        'content' => $page_content,
        'title'   => $title,
      ]
    );
    print($layout_content);
    exit();
  }
  return $_SESSION["user"]["id"];
}

/**
 * Проверка email на уникальность
 *
 * @param $connection
 * @param $user_mail
 * @return bool
 */
function check_email_duplicate($connection, $user_mail)
{
  $email = mysqli_real_escape_string($connection, $user_mail);
  $sql = "SELECT id FROM users WHERE email='$email'";
  $res = mysqli_query($connection, $sql);

  return mysqli_num_rows($res) > 0;
}

/**
 * Создание нового пользователя
 *
 * @param $connection
 * @param $data
 * @return bool
 */
function insert_user_to_db($connection, $data = [])
{
  $sql = 'INSERT INTO users (name, email, password) VALUES (?, ?, ?)';
  $stmt = db_get_prepare_stmt($connection, $sql, $data);

  return mysqli_stmt_execute($stmt);
}

/**
 * Смена статуса задачи
 *
 * @param $connection
 * @param $task_id
 * @return void
 */
function change_task_status($connection, $task_id)
{
  if ($task_id) {
    $sql = "SELECT * FROM task WHERE id=$task_id";
    $result = mysqli_query($connection, $sql);
    if ($result) {
      $task_status = mysqli_fetch_all($result, MYSQLI_ASSOC);
      $status = 0;
      if (isset($task_status[0]["status"]) && $task_status[0]["status"] === '0') {
        $status = 1;
      }
      $sql = "UPDATE task SET status=$status WHERE id= $task_id";
      mysqli_query($connection, $sql);

      header("Location: /?project_id=" . $_GET['project_id']);
    }
  }
}

/**
 * Получение задач пользователя с учетом фильтров
 *
 * @param $connection
 * @param $user_id
 * @return array
 */
function get_tasks($connection, $user_id)
{
  $current_project_id = filter_input(INPUT_GET, 'project_id', FILTER_SANITIZE_NUMBER_INT);

  $sub_query = '';

  if ($current_project_id && !isset($_GET['q'])) {
    $sub_query = " and project_id=$current_project_id";
  }

  if (isset($_GET['q'])) {
    $search = trim(filter_input(INPUT_GET, 'q', FILTER_SANITIZE_SPECIAL_CHARS));
    if (!empty($search)) {
      $sub_query .= "  AND MATCH(name) AGAINST ('$search')";
    }
  }

  if (isset($_GET['show_completed']) && $_GET['show_completed'] === '0') {
    $status = 0;
    $sub_query .= " AND task.status=$status";
  }

  $error_message = "";
  $tasks = [];
  $sql = "SELECT * FROM project LEFT JOIN task ON task.project_id=project.id WHERE project.user_id=$user_id $sub_query AND task.project_id IS NOT NULL";
  $result = mysqli_query($connection, $sql);
  if (!$result) {
    $error_message = "Ничего не найдено по вашему запросу ";
  } else {
    $tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
  }

  if (!$tasks) {
    $error_message = "Ничего не найдено по вашему запросу ";
  }

  return [
    'tasks'         => $tasks,
    'error_message' => $error_message
  ];
}

/**
 * Получение всех проектов пользователя
 *
 * @param $connection
 * @param $user_id
 * @param int $status
 * @return array
 */
function get_all_user_projects($connection, $user_id, $status = 0)
{
  $sql = "SELECT * FROM task LEFT JOIN project ON task.project_id=project.id WHERE project.user_id=$user_id AND task.status=$status";
  $result = mysqli_query($connection, $sql);
  if ($result) {
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
  }

  return [];
}

/**
 * Фильтр задач по датам
 *
 * @param $tasks
 * @param $date_filter
 * @return array
 */
function filter_tasks_by_date($tasks, $date_filter)
{
  $task_new = [];
  foreach ($tasks as $task) {
    if (($date_filter === 'expired') &&
      (strtotime($task['deadline']) < (strtotime(date('Y-m-d')) + 86400)) &&
      (strtotime($task['deadline']) !== strtotime(date('Y-m-d')))
    ) {
      $task_new[] = $task;
    }
    if (($date_filter === 'tomorrow') && (strtotime($task['deadline']) === (strtotime(date('Y-m-d')) + 86400))) {
      $task_new[] = $task;
    }
    if (($date_filter === 'today') && (strtotime($task['deadline']) === strtotime(date('Y-m-d')))) {
      $task_new[] = $task;
    }
  }
  if ($date_filter && $date_filter !== 'all') {
    return $task_new;
  }

  return $tasks;
}

/**
 * Получение проектов пользователя
 *
 * @param $connection
 * @param $user_id
 * @return array
 */
function get_user_projects($connection, $user_id)
{
  $sql = "SELECT * FROM project WHERE user_id=$user_id";
  $result = mysqli_query($connection, $sql);

  return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

/**
 * Получение имени пользователя
 *
 * @param $connection
 * @param $user_id
 * @return array
 */
function get_user_name($connection, $user_id)
{
  $sql = "SELECT * FROM users WHERE id=$user_id";
  $result = mysqli_query($connection, $sql);

  return array_column((mysqli_fetch_all($result, MYSQLI_ASSOC)), "name")[0];
}

/**
 * Подсчет количества задач
 *
 * @param $task_count_all
 * @param $project_id
 * @return int
 */
function tasks_count($task_count_all, $project_id)
{
  $count = 0;
  foreach ($task_count_all as $value) {
    if ($value['id'] === $project_id) {
      $count++;
    }
  }

  return $count;
}

/**
 * Функция подсчета оставшегося времени
 *
 * @param $date
 * @return false|float
 */
function custom_date_diff($date)
{
  $ts = time();
  $task_date_str = strtotime($date);

  return floor(($task_date_str - $ts) / 3600);
}
