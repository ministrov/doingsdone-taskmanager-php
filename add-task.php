<?php
require_once("php/config.php");
global $config, $template_path, $db_config, $error_caption, $error_default_message;

if (!isset($_SESSION["user"])) {
    header("location: guest.php");
    exit();
}

$title = "Дела в порядке | Добавление задачи";
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
    $required_fields = ["title", "project_id"];
    $valid_errors = [];

    // Создаём массив с ID проектов, и заносим его для проверки в функцию validateValue
    $projects_ids = [];

    foreach ($projects as $key => $value) {
        $projects_ids[] = $value["id"];
    }

    $valid_rules = [
        "title" => function ($value) use ($config) {
            return validateLength($value,
                $config["addLengthRules"]["title"]["min"],
                $config["addLengthRules"]["title"]["max"]
            );
        },
        "project_id" => function ($value) use ($projects_ids) {
            return validateValue($value, $projects_ids);
        }
    ];

    $fields = [
        "title" => FILTER_DEFAULT,
        "project_id" => FILTER_DEFAULT,
        "deadline" => FILTER_DEFAULT,
        "file" => FILTER_DEFAULT
    ];

    // В массиве $task будут все значения полей из перечисленных в массиве $fields, если в форме не нашлось
    // необходимого поля, то оно добавится со значением NULL
    $task = filter_input_array(INPUT_POST, $fields, true);

    // Применяем функции валидации ко всем полям формы. Результат работы функций записывается в массив ошибок
    foreach ($task as $key => $value) {
        if (isset($valid_rules[$key])) {
            $rule = $valid_rules[$key];
            $valid_errors[$key] = $rule($value);
        }

        if (in_array($key, $required_fields) && empty($value)) {
            $valid_errors[$key] = "Это поле должно быть заполнено";
        }
    }

    // Массив отфильтровываем, чтобы удалить пустые значения и оставить только сообщения об ошибках
    $valid_errors = array_filter($valid_errors);

    // Проверяем ввёл ли пользователь дату выполнения задачи и проверяем её на соответствие формату и текущей дате
    if (isset($_POST["deadline"])) {
        $data = $_POST["deadline"];

        if (isDateValid($data) === false) {
            $valid_errors["deadline"] = "Введите дату в формате ГГГГ-ММ-ДД";
        } else {
            if ($data < date("Y-m-d")) {
                $valid_errors["deadline"] = "Дата выполнения задачи должна быть больше или равна текущей";
            } else {
                // Добавляем дату выполнения задачи в наш массив $task
                $task["deadline"] = $data;
            }
        }
    }

    // Проверяем загрузил ли пользователь файл, получаем имя файла и его размер
    if (isset($_FILES["file"]) && $_FILES["file"]["name"] !== "") {

        $file_white_list = [
            "image/jpeg",
            "image/png",
            "image/gif",
            "application/pdf",
            "application/msword",
            "text/plain"
        ];

        $file_type = mime_content_type($_FILES["file"]["tmp_name"]);
        $file_name = $_FILES["file"]["name"];
        $file_size = $_FILES["file"]["size"];
        $tmp_name = $_FILES["file"]["tmp_name"];

        if (!in_array($file_type, $file_white_list)) {
            $valid_errors["file"] = "Загрузите файл в формате .jpg, .png, .gif, .pdf, .doc или .txt";
        } else {
            if ($file_size > 300000) {
                $valid_errors["file"] = "Максимальный размер файла: 300Кб";
            } else {
                // Сохраняем его в папке «uploads» и формируем ссылку на скачивание
                $file_path = $config["filePath"];
                $file_url = "/uploads/" . $file_name;

                // Перемещает загруженный файл по новому адресу
                move_uploaded_file($tmp_name, $file_path . $file_name);

                // Добавляем название файла в наш массив $task
                $task["file"] = $file_url;
            }
        }
    }
    // Конец ВАЛИДАЦИИ формы

    // Подсчитываем количество элементов массива с ошибками. Если он не пустой, показываем ошибки вместе с формой
    if (count($valid_errors)) {
        $page_content = includeTemplate($template_path . "form-task.php", [
            "projects" => $projects,
            "tasks_all" => $tasks_all,
            "valid_errors" => $valid_errors
        ]);

        $layout_content = showTemplateLayout($template_path, $page_content, $title, $user);
        dumpAndDie($layout_content);
    }

    // Добавление новой задачи
    $task = dbInsertTask($link, $user_id, $task);
    if ($task["success"] === 0) {
        $task["errorMessage"] = $error_default_message;
        $page_content = showTemplateWithError($template_path, $error_caption, $task["errorMessage"]);
        $layout_content = showTemplateLayout($template_path, $page_content, $title, $user);
        dumpAndDie($layout_content);
    }

    header("Location: index.php");
    exit();
}

$page_content = includeTemplate($template_path . "form-task.php", [
    "projects" => $projects,
    "tasks_all" => $tasks_all
]);

$layout_content = showTemplateLayout($template_path, $page_content, $title, $user);
print($layout_content);
