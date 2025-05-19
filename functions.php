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
 * Получение проектов пользователя
 *
 * @param $connection
 * @param $user_id
 * @return array
 */

function get_user_projects($connection, $user_id)
{
  $sql = "SELECT * FROM projects WHERE author_id='$user_id'";
  $result = mysqli_query($connection, $sql);

  return mysqli_fetch_all($result, MYSQLI_ASSOC);
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
  $sql = "SELECT * FROM tasks LEFT JOIN projects ON tasks.project_id=projects.id WHERE projects.author_id=$user_id AND tasks.status=$status";
  $result = mysqli_query($connection, $sql);
  if ($result) {
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
  }

  return [];
}
