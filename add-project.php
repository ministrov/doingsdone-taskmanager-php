<?php
require_once("php/config.php");
global $ROOT_DIRECTORY, $config, $db_config, $error_caption, $error_default_message, $template_path;

if (!isset($_SESSION["user"])) {
    header("location: {$ROOT_DIRECTORY}/guest.php");
    exit();
}

$title = "Дела в порядке | Добавление проекта";
$user = $_SESSION["user"];
$user_id = intval($_SESSION["user"]["id"]);

// Если сайт находится в неактивном состоянии, выходим на страницу с сообщением о техническом обслуживании
ifSiteDisabled($config, $template_path, $title);

// Подключение к MySQL
$link = mysqlConnect($db_config);

// Проверяем наличие ошибок подключения к MySQL и выводим их в шаблоне
ifMysqlConnectError($link, $config, $title, $template_path, $error_caption, $error_default_message);

$link = $link["link"];

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

// ПОЛУЧАЕМ из полей формы необходимые данные от пользователя, ПРОВЕРЯЕМ их и СОХРАНЯЕМ в БД
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ВАЛИДАЦИЯ формы
    $project = $_POST;
    $valid_errors = [];

    if (empty($project["name"])) {
        $valid_errors["name"] = "Это поле должно быть заполнено";
    }

    $validate_length = validateLength($project["name"],
        $config["addLengthRules"]["project"]["min"],
        $config["addLengthRules"]["project"]["max"]
    );

    if ($validate_length !== null) {
        $valid_errors["name"] = $validate_length;
    }

    foreach ($projects as $value) {
        if (isset($project["name"])) {
            if (mb_strtoupper($project["name"]) === mb_strtoupper($value["name"])) {
                $valid_errors["name"] = "Проект с таким названием уже существует";
            }
        }
    }
    // Конец ВАЛИДАЦИИ формы

    // Подсчитываем количество элементов массива с ошибками. Если он не пустой, показываем ошибки вместе с формой
    if (count($valid_errors)) {
        $page_content = includeTemplate($template_path . "form-project.php", [
            "projects" => $projects,
            "tasks_all" => $tasks_all,
            "valid_errors" => $valid_errors
        ]);

        $layout_content = showTemplateLayout($template_path, $page_content, $title, $user);
        dumpAndDie($layout_content);
    }

    // Добавление нового проекта
    $project = dbInsertProject($link, $user_id, $project);
    if ($project["success"] === 0) {
        $project["errorMessage"] = $error_default_message;
        $page_content = showTemplateWithError($template_path, $error_caption, $project["errorMessage"]);
        $layout_content = showTemplateLayout($template_path, $page_content, $title, $user);
        dumpAndDie($layout_content);
    }

    header("Location: {$ROOT_DIRECTORY}/index.php");
    exit();
}

$page_content = includeTemplate($template_path . "form-project.php", [
    "projects" => $projects,
    "tasks_all" => $tasks_all
]);

$layout_content = showTemplateLayout($template_path, $page_content, $title, $user);
print($layout_content);
