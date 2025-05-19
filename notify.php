<?php
require_once "vendor/autoload.php";
require_once "php/config.php";
global $message_caption, $db_config, $config, $template_path, $error_caption, $error_default_message;

$title = "Дела в порядке | Отправка e-mail рассылки";

// Подключение к MySQL
$link = mysqlConnect($db_config);

// Проверяем наличие ошибок подключения к MySQL и выводим их в шаблоне
ifMysqlConnectError($link, $config, $title, $template_path, $error_caption, $error_default_message);

$link = $link["link"];

// Список ID пользователей, у которых есть невыполненные задачи, срок выполнения которых равен текущему дню
$users_ids = dbGetUsersIds($link);
if ($users_ids["success"] === 0) {
    $page_content = showTemplateWithError($template_path, $error_caption, $users_ids["errorMessage"]);
    $layout_content = showTemplateLayoutGuest($template_path, $page_content, $config, $title);
    dumpAndDie($layout_content);
}

if ($users_ids["count"] === 0) {
    $message = "Нет задач для отправки рассылки";
    $page_content = showTemplateWithMessage($template_path, $message_caption, $message);
    $layout_content = showTemplateLayoutGuest($template_path, $page_content, $config, $title);
    dumpAndDie($layout_content);
}

$users_ids = $users_ids["data"];

foreach ($users_ids as $value) {
    // Список невыполненных задач для каждого найденного пользователя
    $tasks_user = dbGetTasksUser($link, $value["user_id"]);
    if ($tasks_user["success"] === 0) {
        $page_content = showTemplateWithError($template_path, $error_caption, $tasks_user["errorMessage"]);
        $layout_content = showTemplateLayoutGuest($template_path, $page_content, $config, $title);
        dumpAndDie($layout_content);
    }

    $tasks_user = $tasks_user["data"];

    // Список данных о каждом найденном пользователе для отправки e-mail рассылки
    $data_user = dbGetDataUser($link, $value["user_id"]);
    if ($data_user["success"] === 0) {
        $page_content = showTemplateWithError($template_path, $data_user["errorCaption"], $data_user["errorMessage"]);
        $layout_content = showTemplateLayoutGuest($template_path, $page_content, $config, $title);
        dumpAndDie($layout_content);
    }

    $data_user = $data_user["data"];

    $recipient = [];
    $recipient[$data_user["email"]] = $data_user["name"];

    $message_content = includeTemplate($template_path . "email-notify.php", [
        "dataUser" => $data_user,
        "tasksUser" => $tasks_user
    ]);

    $mail_send_result = mailSendMessage($yandex_mail_config, $recipient, $message_content);
    if ($mail_send_result["success"] === 0) {
        $page_content = showTemplateWithError($template_path, $error_caption, $mail_send_result["errorMessage"]);
        $layout_content = showTemplateLayoutGuest($template_path, $page_content, $config, $title);
        dumpAndDie($layout_content);
    }

    $message = "Рассылка успешно отправлена!";
}

$page_content = showTemplateWithMessage($template_path, $message_caption, $message);
$layout_content = showTemplateLayoutGuest($template_path, $page_content, $config, $title);
print($layout_content);
