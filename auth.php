<?php
require_once("php/config.php");
global $db_config, $config, $template_path, $error_caption, $error_default_message, $ROOT_DIRECTORY;

$title = "Дела в порядке | Авторизация на сайте";

// Если сайт находится в неактивном состоянии, выходим на страницу с сообщением о техническом обслуживании
ifSiteDisabled($config, $template_path, $title);

// Подключение к MySQL
$link = mysqlConnect($db_config);

// Проверяем наличие ошибок подключения к MySQL и выводим их в шаблоне
ifMysqlConnectError($link, $config, $title, $template_path, $error_caption, $error_default_message);

$link = $link["link"];

// ПОЛУЧАЕМ из полей формы необходимые данные от пользователя, ПРОВЕРЯЕМ их и СОХРАНЯЕМ в БД
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_guest = $_POST;

    $required_fields = ["email", "password"];
    $valid_errors = [];
    $valid_error_message = "";

    if (isset($user_guest["email"]) && !filter_var($user_guest["email"], FILTER_VALIDATE_EMAIL)) {
        $valid_errors["email"] = "E-mail введён некорректно";
    }

    foreach ($required_fields as $field) {
        if (empty($user_guest[$field])) {
            $valid_errors[$field] = "Это поле должно быть заполнено";
        }
    }

    if (count($valid_errors)) {
        $valid_error_message = "Пожалуйста, исправьте ошибки в форме";
    } else {
        if (isset($user_guest["email"])) {
            $email = mysqli_real_escape_string($link, $user_guest["email"]);

            // Поиск в базе данных в таблице users пользователя с переданным e-mail
            $user = dbGetUser($link, $email);
            if ($user["success"] === 0) {
                $user["errorMessage"] = $error_default_message;
                $page_content = showTemplateWithError($template_path, $error_caption, $user["errorMessage"]);
                $layout_content = showTemplateLayoutGuest($template_path, $page_content, $config, $title);
                dumpAndDie($layout_content);
            }

            $user = $user["data"];

            // Проверяем, соответствует ли переданный пароль хешу
            if (password_verify($user_guest["password"], $user["password"])) {
                $_SESSION["user"] = $user;
                header("Location: {$ROOT_DIRECTORY}/index.php");
                exit();
            }

            $valid_error_message = "Вы ввели неверный email/пароль";
        }
    }
}

$page_content = includeTemplate($template_path . "form-auth.php", [
    "valid_error_message" => $valid_error_message,
    "valid_errors" => $valid_errors,
    "ROOT_DIRECTORY" => $ROOT_DIRECTORY,
]);

$layout_content = showTemplateLayoutGuest($template_path, $page_content, $config, $title);
print($layout_content);
