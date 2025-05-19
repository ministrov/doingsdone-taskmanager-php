<?php
require_once("php/config.php");
global $ROOT_DIRECTORY, $config, $template_path, $db_config, $error_caption, $error_default_message;

$title = "Дела в порядке | Регистрация аккаунта";

// Если сайт находится в неактивном состоянии, выходим на страницу с сообщением о техническом обслуживании
ifSiteDisabled($config, $template_path, $title);

// Подключение к MySQL
$link = mysqlConnect($db_config);

// Проверяем наличие ошибок подключения к MySQL и выводим их в шаблоне
ifMysqlConnectError($link, $config, $title, $template_path, $error_caption, $error_default_message);

$link = $link["link"];

// ПОЛУЧАЕМ из полей формы необходимые данные от пользователя, ПРОВЕРЯЕМ их и СОХРАНЯЕМ в БД
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST;

    $required_fields = ["email", "password", "name"];
    $valid_errors = [];

    $valid_rules = [
        "email" => function ($value) {
            return validateEmail($value);
        },
        "password" => function ($value) use ($config) {
            return validateLength(
                $value,
                $config["registerLengthRules"]["password"]["min"],
                $config["registerLengthRules"]["password"]["max"]
            );
        },
        "name" => function ($value) use ($config) {
            return validateLength(
                $value,
                $config["registerLengthRules"]["name"]["min"],
                $config["registerLengthRules"]["name"]["max"]
            );
        }
    ];

    foreach ($user as $key => $value) {
        if (isset($valid_rules[$key])) {
            $rule = $valid_rules[$key];
            $valid_errors[$key] = $rule($value);
        }

        if (in_array($key, $required_fields) && empty($value)) {
            $valid_errors[$key] = "Это поле должно быть заполнено";
        }
    }

    if (isset($user["email"]) && !$valid_errors["email"]) {
        $email = mysqli_real_escape_string($link, $user["email"]);

        // Поиск в базе данных в таблице users уже используемого e-mail
        $email = dbGetEmail($link, $email);
        if ($email["success"] === 0) {
            $email["errorMessage"] = $error_default_message;
            $page_content = showTemplateWithError($template_path, $error_caption, $email["errorMessage"]);
            $layout_content = showTemplateLayoutGuest($template_path, $page_content, $config, $title);
            dumpAndDie($layout_content);
        }

        if ($email["count"] > 0) {
            $valid_errors["email"] = "Указанный e-mail уже используется другим пользователем";
        }
    }

    // Массив отфильтровываем, чтобы удалить пустые значения и оставить только сообщения об ошибках
    $valid_errors = array_filter($valid_errors);

    if (empty($valid_errors)) {
        // Добавим нового пользователя в БД. Чтобы не хранить пароль в открытом виде преобразуем его в хеш
        $password = password_hash($user["password"], PASSWORD_DEFAULT);

        $user = dbInsertUser($link, [$user["email"], $user["name"], $password]);
        if ($user["success"] === 0) {
            $user["errorMessage"] = $error_default_message;
            $page_content = showTemplateWithError($template_path, $error_caption, $user["errorMessage"]);
            $layout_content = showTemplateLayoutGuest($template_path, $page_content, $config, $title);
            dumpAndDie($layout_content);
        }

        header("Location: {$ROOT_DIRECTORY}/index.php");
        exit();
    }
}

$page_content = includeTemplate($template_path . "form-register.php", [
    "valid_errors" => $valid_errors,
]);

$layout_content = showTemplateLayoutGuest($template_path, $page_content, $config, $title);
print($layout_content);
