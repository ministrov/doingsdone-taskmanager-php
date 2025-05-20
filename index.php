<?php
require_once("php/config.php");
global $ROOT_DIRECTORY, $message_caption, $config, $template_path, $db_config, $error_caption, $error_default_message;

if (!isset($_SESSION["user"])) {
    header("location: {$ROOT_DIRECTORY}/guest.php");
    exit();
}

$title = "Дела в порядке";
$user = $_SESSION["user"];
$user_id = intval($_SESSION["user"]["id"]);

// Если сайт находится в неактивном состоянии, выходим на страницу с сообщением о техническом обслуживании
ifSiteDisabled($config, $template_path, $title);

// Подключение к MySQL
$link = mysqlConnect($db_config);

// Проверяем наличие ошибок подключения к MySQL и выводим их в шаблоне
ifMysqlConnectError($link, $config, $title, $template_path, $error_caption, $error_default_message);

$link = $link["link"];
$projects = [];
$tasks = [];

// Список проектов у текущего пользователя
$projects = dbGetProjects($link, $user_id);
if ($projects["success"] === 0) {
    $projects["errorMessage"] = $error_default_message;
    $page_content = showTemplateWithError($template_path, $error_caption, $projects["errorMessage"]);
    $layout_content = showTemplateLayout($template_path, $page_content, $title, $user);
    dumpAndDie($layout_content);
}

$projects = $projects["data"];

// Список всех задач у текущего пользователя
$tasks_all = dbGetTasks($link, $user_id);
if ($tasks_all["success"] === 0) {
    $tasks_all["errorMessage"] = $error_default_message;
    $page_content = showTemplateWithError($template_path, $error_caption, $tasks_all["errorMessage"]);
    $layout_content = showTemplateLayout($template_path, $page_content, $title, $user);
    dumpAndDie($layout_content);
}

$tasks_all = $tasks_all["data"];

// Список всех задач у текущего пользователя для каждого проекта
$tasks = dbGetTasks($link, $user_id);
if ($tasks["success"] === 0) {
    $tasks["errorMessage"] = $error_default_message;
    $page_content = showTemplateWithError($template_path, $error_caption, $tasks["errorMessage"]);
    $layout_content = showTemplateLayout($template_path, $page_content, $title, $user);
    dumpAndDie($layout_content);
}

$tasks = $tasks["data"];
$tasks = addHoursUntilEndTask($tasks);

if (isset($_GET["project_id"])) {
    $project_id = intval($_GET["project_id"]);

    $tasks = dbGetTasksProject($link, $project_id, $user_id);
    if ($tasks["success"] === 0) {
        $tasks["errorMessage"] = $error_default_message;
        $page_content = showTemplateWithError($template_path, $error_caption, $tasks["errorMessage"]);
        $layout_content = showTemplateLayout($template_path, $page_content, $title, $user);
        dumpAndDie($layout_content);
    }

    // Проверяем для текущего ID проекта из адресной строки его существование в массиве проектов
    $current_project_id = false;
    foreach ($projects as $key => $value) {
        if ($project_id === $value["id"]) {
            $current_project_id = true;
            break;
        }
    }

    if ($current_project_id === false) {
        http_response_code(404);
        $message = "Не найдено проекта с таким ID";
        ifErrorResultSearch($template_path, $message_caption, $message, $title, $user);
    }

    if ($tasks["count"] == 0) {
        http_response_code(404);
        $message = "Не найдено ни одной задачи для данного проекта";
        ifErrorResultSearch($template_path, $message_caption, $message, $title, $user);
    }

    $tasks = $tasks["data"];
    $tasks = addHoursUntilEndTask($tasks);
}

// Список задач, найденных по поисковому запросу с использование FULLTEXT поиска MySQL
$search = "";
$search_tasks = [];

if (isset($_GET["query"])) {
    $search = trim($_GET["query"]);

    if ($search) {
        $search_tasks = dbGetSearchTasks($link, $user_id, [$search]);
        if ($search_tasks["success"] === 0) {
            $search_tasks["errorMessage"] = $error_default_message;
            $page_content = showTemplateWithError($template_path, $error_caption, $search_tasks["errorMessage"]);
            $layout_content = showTemplateLayout($template_path, $page_content, $title, $user);
            dumpAndDie($layout_content);
        }

        if ($search_tasks["count"] == 0) {
            http_response_code(404);
            $search_tasks_message = "Ничего не найдено по вашему запросу";
        }

        $search_tasks = $search_tasks["data"];
    }
}

// Блок сортировки задач (задачи на сегодня, на завтра, просроченные)
$url = "";
$url_link = "";

if (isset($_GET["show_completed"])) {
    $show_complete_tasks = intval($_GET["show_completed"]);
    $_GET["show_completed"] = intval(!($show_complete_tasks));
}

// Возвращает информацию о path в виде ассоциативного массива
$script_name = pathinfo(__FILE__, PATHINFO_BASENAME);
// Преобразует ассоциативный массив в строку запроса
$query = http_build_query($_GET);
$url = "/" . $script_name . "?" . $query;

if (mb_strpos($url, "show_completed") === false) {
    $reverse_complete_tasks = intval(!$show_complete_tasks);
    $url_link = "&show_completed=$reverse_complete_tasks";
}

$filter = $_GET;
$filter_white_list = ["today", "tomorrow", "past"];

if (isset($filter["tab"]) && in_array($filter["tab"], $filter_white_list)) {
    $tasks = dbGetFilterTasks($link, $user_id, $filter);
    if ($tasks["success"] === 0) {
        $tasks["errorMessage"] = $error_default_message;
        $page_content = showTemplateWithError($template_path, $error_caption, $tasks["errorMessage"]);
        $layout_content = showTemplateLayout($template_path, $page_content, $title, $user);
        dumpAndDie($layout_content);
    }

    $tasks = $tasks["data"];
    $tasks = addHoursUntilEndTask($tasks);
}

// Смена статуса выполнения задачи (выполнена -> не выполнена, не выполнена -> выполнена)
$task_id = "";
$task_status = [];
$tabs = "";

// Для сохранения состояния блоков фильтров, выбранных пользователем
if (isset($_GET["tab"]) && in_array($_GET["tab"], $filter_white_list)) {
    $tabs .= "&tab=" . $_GET["tab"];
}

if (isset($_GET["task_id"])) {
    $task_id = intval($_GET["task_id"]);

    $status_task = dbGetStatusTask($link, $task_id, $user_id);
    if ($status_task["success"] === 0) {
        $status_task["errorMessage"] = $error_default_message;
        $page_content = showTemplateWithError($template_path, $error_caption, $status_task["errorMessage"]);
        $layout_content = showTemplateLayout($template_path, $page_content, $title, $user);
        dumpAndDie($layout_content);
    }

    $status_task = $status_task["data"];

    if (isset($status_task["status"])) {
        $status = 0;
        if ($status_task["status"] === 0) {
            $status = 1;
        }

        $change_status_task = dbChangeStatusTask($link, $status, $task_id, $user_id);
        if ($change_status_task["success"] === 0) {
            $change_status_task["errorMessage"] = $error_default_message;
            $page_content = showTemplateWithError($template_path, $error_caption, $change_status_task["errorMessage"]);
            $layout_content = showTemplateLayout($template_path, $page_content, $title, $user);
            dumpAndDie($layout_content);
        }

        $redirect_tab = "";

        if (isset($_GET["tab"]) && in_array($_GET["tab"], $filter_white_list)) {
            $redirect_tab .= "?tab=" . $_GET["tab"];
        }

        $redirect_tab_part = "&";
        if ($redirect_tab === "") {
            $redirect_tab_part = "?";
        }

        $redirect_tab .= "{$redirect_tab_part}show_completed=$show_complete_tasks";

        $header_location = "Location: index.php";
        if ($redirect_tab !== "") {
            $header_location .= $redirect_tab;
        }

        header($header_location);
        exit();
    }
}

$show_complete_tasks_url = "&show_completed=$show_complete_tasks";

$page_content = includeTemplate($template_path . "main.php", [
  "tasks" => $tasks,
  "projects" => $projects,
  "tasks_all" => $tasks_all,
  "search_tasks" => $search_tasks,
  "search_tasks_message" => $search_tasks_message,
  "tabs" => $tabs,
  "url" => $url,
  "url_link" => $url_link,
  "show_complete_tasks" => $show_complete_tasks,
  "show_complete_tasks_url" => $show_complete_tasks_url,
  "ROOT_DIRECTORY" => $ROOT_DIRECTORY,
]);

$layout_content = showTemplateLayout($template_path, $page_content, $title, $user);
print($layout_content);
