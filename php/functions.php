<?php

$ROOT_DIRECTORY = "/doingsdone";
//$ROOT_DIRECTORY = "";

/**
 * Подключает шаблон, передает туда данные и возвращает итоговый HTML контент
 * @param string $name Путь к файлу шаблона относительно папки с шаблонами
 * @param array $data Ассоциативный массив с данными для шаблона
 * @return string Итоговый HTML
 */
function includeTemplate(string $name, array $data = [])
{
    $result = "";

    if (!is_readable($name)) {
        return $result;
    }

    ob_start();
    extract($data);
    require $name;

    $result = ob_get_clean();

    return $result;
}

/**
 * Выполняет подключение к MySQL
 * @param array $db_config Ассоциативный массив с параметрами для подключения к БД
 * @return array $result Ассоциативный массив с информацией по ресурсу соединения
 */
function mysqlConnect(array $db_config): array
{
    try {
        // Установка перехвата ошибок: MYSQLI_REPORT_ERROR — Заносит в протокол ошибки вызовов функций mysqli
        // MYSQLI_REPORT_STRICT — Вместо сообщений об ошибках выбрасывает исключение mysqli_sql_exception
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $link = mysqli_init();
        // Устанавливает преобразование целочисленных значений и чисел с плавающей запятой из столбцов таблицы в PHP числа
        mysqli_options($link, MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
        mysqli_real_connect($link, $db_config["host"], $db_config["user"], $db_config["password"],
            $db_config["database"]);

        // Кодировка при работе с MySQL
        mysqli_set_charset($link, "utf8");
        $result = [
            "success" => 1,
            "link" => $link
        ];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * Показывает страницу с сообщением о техническом обслуживании, если сайт находится в неактивном состоянии
 * @param array $config Двумерный массив с параметрами сайта
 * @param string $template_path Путь к папке с шаблонами
 * @param string $title Название страницы сайта
 */
function ifSiteDisabled(array $config, string $template_path, string $title)
{
    global $ROOT_DIRECTORY;
    if (isset($config["enable"]) && $config["enable"] === false) {
        $_SESSION = [];
        $page_content = includeTemplate(($config["templatePath"] . "off.php"), []);

        $layout_content = includeTemplate($template_path . "layout.php", [
            "page_content" => $page_content,
            "config" => $config,
            "title" => $title,
            "ROOT_DIRECTORY" => $ROOT_DIRECTORY,
        ]);
        dumpAndDie($layout_content);
    }
}

/**
 * Показывает страницу с сообщением об ошибке подключения к MySQL
 * @param array $link mysqli Ассоциативный массив с информацией по ресурсу соединения
 * @param array $config Двумерный массив с параметрами сайта
 * @param string $title Название страницы сайта
 * @param string $template_path Путь к папке с шаблонами
 * @param string $error_caption Заголовок ошибки
 * @param string $error_default_message Текст ошибки
 */
function ifMysqlConnectError(
    array $link,
    array $config,
    string $title,
    string $template_path,
    string $error_caption,
    string $error_default_message
) {
    if ($link["success"] === 0) {
        $page_content = showTemplateWithError($template_path, $error_caption, $error_default_message);
        $layout_content = showTemplateLayoutGuest($template_path, $page_content, $config, $title);
        dumpAndDie($layout_content);
    }
}

/**
 * Создает подготовленное выражение на основе готового SQL-запроса и переданных данных
 * @param $link mysqli Ресурс соединения
 * @param string $sql SQL-запрос с плейсхолдерами вместо значений
 * @param array $data Массив с данными для вставки на место плейсхолдеров
 * @return false|mysqli_stmt Подготовленное выражение
 */
function dbGetPrepareStmt($link, string $sql, array $data = [])
{
    $stmt = mysqli_prepare($link, $sql);

    if ($stmt === false) {
        $error_msg = "Не удалось инициализировать подготовленное выражение: " . mysqli_error($link);
        die($error_msg);
    }
    if ($data) {
        $types = "";
        $stmt_data = [];

        foreach ($data as $value) {
            $type = "s";

            if (is_int($value)) {
                $type = "i";
            } else {
                if (is_string($value)) {
                    $type = "s";
                } else {
                    if (is_double($value)) {
                        $type = "d";
                    }
                }
            }

            if ($type) {
                $types .= $type;
                $stmt_data[] = $value;
            }
        }
        $values = array_merge([$stmt, $types], $stmt_data);

        $func = "mysqli_stmt_bind_param";
        $func(...$values);

        if (mysqli_errno($link) > 0) {
            $error_msg = "Не удалось связать подготовленное выражение с параметрами: " . mysqli_error($link);
            die($error_msg);
        }
    }

    return $stmt;
}

/**
 * SQL-запрос при регистрации пользователя для поиска в базе данных уже используемого e-mail
 * @param $link mysqli Ресурс соединения
 * @param string $email E-mail переданный при аутентификации
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbGetEmail($link, string $email): array
{
    $sql = "SELECT id FROM dd_users WHERE email = '$email'";
    try {
        $email_result = mysqli_query($link, $sql);
        $result = [
            "success" => 1,
            "count" => mysqli_num_rows($email_result)
        ];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * SQL-запрос на добавление нового пользователя в базу данных
 * @param $link mysqli Ресурс соединения
 * @param array $data Массив с данными для вставки на место плейсхолдеров
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbInsertUser($link, array $data = []): array
{
    $sql = "INSERT INTO dd_users (email, name, password) VALUES (?, ?, ?)";
    try {
        // Формируем подготовленное выражение на основе SQL-запроса, ресурс соединения и массива со значениями
        $stmt = dbGetPrepareStmt($link, $sql, $data);
        // Выполняем полученное выражение
        mysqli_stmt_execute($stmt);
        $result = ["success" => 1];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * SQL-запрос при аутентификации пользователя для поиска в базе данных пользователя с переданным e-mail
 * @param $link mysqli Ресурс соединения
 * @param string $email E-mail переданный при аутентификации
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbGetUser($link, string $email): array
{
    $sql = "SELECT * FROM dd_users WHERE email = '$email'";
    try {
        $user_result = mysqli_query($link, $sql);
        $user = mysqli_fetch_array($user_result, MYSQLI_ASSOC);
        $result = [
            "success" => 1,
            "data" => $user
        ];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * SQL-запрос для получения списка проектов у текущего пользователя
 * @param $link mysqli Ресурс соединения
 * @param int $user_id Id текущего пользователя
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbGetProjects($link, int $user_id): array
{
    $sql = "SELECT id, name FROM dd_projects WHERE user_id = " . $user_id;
    try {
        $projects_result = mysqli_query($link, $sql);
        $projects = mysqli_fetch_all($projects_result, MYSQLI_ASSOC);
        $result = [
            "success" => 1,
            "data" => $projects,
            "count" => mysqli_num_rows($projects_result)
        ];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * SQL-запрос на добавление нового проекта у текущего пользователя
 * @param $link mysqli Ресурс соединения
 * @param int $user_id Id текущего пользователя
 * @param array $data Массив с данными для вставки на место плейсхолдеров
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbInsertProject($link, int $user_id, array $data = []): array
{
    $sql = "INSERT INTO dd_projects (user_id, name) VALUES ($user_id, ?)";
    try {
        // Формируем подготовленное выражение на основе SQL-запроса, ресурс соединения и массива со значениями
        $stmt = dbGetPrepareStmt($link, $sql, $data);
        // Выполняем полученное выражение
        mysqli_stmt_execute($stmt);
        $result = ["success" => 1];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * SQL-запрос для получения списка всех задач у текущего пользователя
 * @param $link mysqli Ресурс соединения
 * @param int $user_id Id текущего пользователя
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbGetTasks($link, int $user_id): array
{
    $sql = <<<SQL
    SELECT t.id, t.user_id, p.name AS project, t.title, t.file, t.deadline, t.status
    FROM dd_tasks t
    LEFT JOIN dd_projects p ON t.project_id = p.id
    LEFT JOIN dd_users u ON t.user_id = u.id
    WHERE t.user_id = $user_id ORDER BY t.id DESC
SQL;
    try {
        $tasks_result = mysqli_query($link, $sql);
        $tasks = mysqli_fetch_all($tasks_result, MYSQLI_ASSOC);
        $result = [
            "success" => 1,
            "data" => $tasks,
            "count" => mysqli_num_rows($tasks_result)
        ];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * SQL-запрос на добавление новой задачи у текущего пользователя
 * @param $link mysqli Ресурс соединения
 * @param int $user_id Id текущего пользователя
 * @param array $data Массив с данными для вставки на место плейсхолдеров
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbInsertTask($link, int $user_id, array $data = []): array
{
    $sql = "INSERT INTO dd_tasks (user_id, title, project_id, deadline, file) VALUES ($user_id, ?, ?, ?, ?)";
    try {
        // Формируем подготовленное выражение на основе SQL-запроса, ресурс соединения и массива со значениями
        $stmt = dbGetPrepareStmt($link, $sql, $data);
        // Выполняем полученное выражение
        mysqli_stmt_execute($stmt);
        $result = ["success" => 1];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * SQL-запрос для получения списка всех задач у текущего пользователя для каждого проекта
 * @param $link mysqli Ресурс соединения
 * @param int $project_id Id выбранного проекта текущего пользователя
 * @param int $user_id Id текущего пользователя
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbGetTasksProject($link, int $project_id, int $user_id): array
{
    $sql = <<<SQL
    SELECT t.id, t.user_id, p.name AS project, t.title, t.file, t.deadline, t.status
    FROM dd_tasks t
    LEFT JOIN dd_projects p ON t.project_id = p.id
    LEFT JOIN dd_users u ON t.user_id = u.id
    WHERE t.user_id = $user_id and p.id = $project_id ORDER BY t.id DESC;
SQL;
    try {
        $tasks_result = mysqli_query($link, $sql);
        $tasks = mysqli_fetch_all($tasks_result, MYSQLI_ASSOC);
        $result = [
            "success" => 1,
            "data" => $tasks,
            "count" => mysqli_num_rows($tasks_result)
        ];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * SQL-запрос для получения списка задач, найденных по поисковому запросу с использование FULLTEXT поиска MySQL
 * @param $link mysqli Ресурс соединения
 * @param int $user_id Id текущего пользователя
 * @param array $data Массив с данными для вставки на место плейсхолдеров
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbGetSearchTasks($link, int $user_id, array $data = []): array
{
    $sql = <<<SQL
    SELECT t.id, t.user_id, p.id AS project_id, p.name AS project, t.title
    FROM dd_tasks t
    LEFT JOIN dd_projects p ON t.project_id = p.id
    LEFT JOIN dd_users u ON t.user_id = u.id
    WHERE t.user_id = $user_id and MATCH(title) AGAINST(?) ORDER BY t.id DESC
SQL;
    try {
        $stmt = dbGetPrepareStmt($link, $sql, $data);
        mysqli_stmt_execute($stmt);
        $search_tasks_result = mysqli_stmt_get_result($stmt);
        $search_tasks = mysqli_fetch_all($search_tasks_result, MYSQLI_ASSOC);
        $result = [
            "success" => 1,
            "data" => $search_tasks,
            "count" => mysqli_num_rows($search_tasks_result)
        ];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * SQL-запрос для получения данных для блока сортировки задач у текущего пользователя
 * @param $link mysqli Ресурс соединения
 * @param int $user_id Id текущего пользователя
 * @param array $filter Ассоциативный массив с фильтрами (задачи на сегодня, на завтра, просроченные)
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbGetFilterTasks($link, int $user_id, array $filter = []): array
{
    switch ($filter["tab"]) {
        case "today":
            // SQL-запрос для получения списка задач «Повестка дня»
            $sql = <<<SQL
            SELECT t.id, t.user_id, p.name AS project, t.title, t.file, t.deadline, t.status
            FROM dd_tasks t
            LEFT JOIN dd_projects p ON t.project_id = p.id
            LEFT JOIN dd_users u ON t.user_id = u.id
            WHERE DATE(t.deadline) = DATE(NOW()) and t.user_id = $user_id ORDER BY t.id DESC
SQL;
            break;
        case "tomorrow":
            // SQL-запрос для получения списка задач на «Завтра»
            $sql = <<<SQL
            SELECT t.id, t.user_id, p.name AS project, t.title, t.file, t.deadline, t.status
            FROM dd_tasks t
            LEFT JOIN dd_projects p ON t.project_id = p.id
            LEFT JOIN dd_users u ON t.user_id = u.id
            WHERE DATE (t.deadline) = DATE(DATE_ADD(NOW(), INTERVAL 24 HOUR)) and t.user_id = $user_id ORDER BY t.id DESC
SQL;
            break;
        case "past":
            // SQL-запрос для получения списка «Просроченные»
            $sql = <<<SQL
            SELECT t.id, t.user_id, p.name AS project, t.title, t.file, t.deadline, t.status
            FROM dd_tasks t
            LEFT JOIN dd_projects p ON t.project_id = p.id
            LEFT JOIN dd_users u ON t.user_id = u.id
            WHERE DATE(t.deadline) < DATE(NOW()) and t.user_id = $user_id ORDER BY t.id DESC
SQL;
            break;
    }
    try {
        $filter_result = mysqli_query($link, $sql);
        $filter_tasks = mysqli_fetch_all($filter_result, MYSQLI_ASSOC);
        $result = [
            "success" => 1,
            "data" => $filter_tasks
        ];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * SQL-запрос для получения статуса выбранной задачи у текущего пользователя
 * @param $link mysqli Ресурс соединения
 * @param int $task_id Id выбранной задачи текущего пользователя
 * @param int $user_id Id текущего пользователя
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbGetStatusTask($link, int $task_id, int $user_id): array
{
    $sql = "SELECT id, status FROM dd_tasks WHERE id = $task_id and user_id = " . $user_id;
    try {
        $status_task_result = mysqli_query($link, $sql);
        $status_task = mysqli_fetch_assoc($status_task_result);
        $result = [
            "success" => 1,
            "data" => $status_task
        ];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * SQL-запрос на cмену статуса выполнения задачи у текущего пользователя
 * @param $link mysqli Ресурс соединения
 * @param int $status Статус выбранной задачи
 * @param int $task_id Id выбранной задачи текущего пользователя
 * @param int $user_id Id текущего пользователя
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbChangeStatusTask($link, int $status, int $task_id, int $user_id): array
{
    $sql = "UPDATE dd_tasks SET status = $status WHERE id = $task_id and user_id = " . $user_id;
    try {
        mysqli_query($link, $sql);
        $result = ["success" => 1];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * SQL-запрос на получение всех ID пользователей, у которых есть невыполненные задачи, срок которых равен текущему дню
 * @param $link mysqli Ресурс соединения
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbGetUsersIds($link): array
{
    $sql = "SELECT user_id FROM dd_tasks WHERE DATE(deadline) = DATE(NOW()) and status = 0 GROUP BY user_id";
    try {
        $users_ids_result = mysqli_query($link, $sql);
        $users_ids = mysqli_fetch_all($users_ids_result, MYSQLI_ASSOC);
        $result = [
            "success" => 1,
            "data" => $users_ids,
            "count" => mysqli_num_rows($users_ids_result)
        ];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * SQL-запрос на получение данных по невыполненным задачам для каждого найденного пользователя
 * @param $link mysqli Ресурс соединения
 * @param int $value Значением ID найденного пользователя
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbGetTasksUser($link, int $value): array
{
    $sql = "SELECT title, deadline FROM dd_tasks WHERE DATE(deadline) = DATE(NOW()) and status = 0 and user_id = $value";
    try {
        $tasks_user_result = mysqli_query($link, $sql);
        $tasks_user = mysqli_fetch_all($tasks_user_result, MYSQLI_ASSOC);
        $result = [
            "success" => 1,
            "data" => $tasks_user
        ];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * SQL-запрос на получение данных о каждом найденном пользователе для отправки e-mail рассылки
 * @param $link mysqli Ресурс соединения
 * @param int $value Значением ID найденного пользователя
 * @return array $result Ассоциативный массив с информацией по SQL-запросу
 */
function dbGetDataUser($link, int $value): array
{
    $sql = "SELECT email, name FROM dd_users WHERE id = $value";
    try {
        $data_user_result = mysqli_query($link, $sql);
        $data_user = mysqli_fetch_assoc($data_user_result);
        $result = [
            "success" => 1,
            "data" => $data_user
        ];
    } catch (Exception $ex) {
        $result = [
            "success" => 0,
            "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
        ];
    }

    return $result;
}

/**
 * Показывает шаблон с информацией об ошибке выполнения SQL-запроса
 * @param string $template_path Путь к папке с шаблонами
 * @param string $error_caption Заголовок ошибки
 * @param string $error_message Текст ошибки
 * @return string HTML контент
 */
function showTemplateWithError(string $template_path, string $error_caption, string $error_message)
{
    return includeTemplate($template_path . "inform.php", [
        "message_caption" => $error_caption,
        "message" => $error_message
    ]);
}

/**
 * Показывает шаблон с информацией о результате выполненного действия (поиска в БД или отправки сообщения)
 * @param string $template_path Путь к папке с шаблонами
 * @param string $message_caption Заголовок сообщения
 * @param string $message Текст сообщения
 * @return string HTML контент
 */
function showTemplateWithMessage(string $template_path, string $message_caption, string $message)
{
    return includeTemplate($template_path . "inform.php", [
        "message_caption" => $message_caption,
        "message" => $message
    ]);
}

/**
 * Показывает шаблон лейаута для зарегистрированного пользователя
 * @param string $template_path Путь к папке с шаблонами
 * @param string $page_content Содержание контентной части
 * @param string $title Название страницы
 * @param array $user Данные текущего пользователя
 * @return string HTML контент
 */
function showTemplateLayout(string $template_path, string $page_content, string $title, array $user = [])
{
    global $ROOT_DIRECTORY;
    return includeTemplate($template_path . "layout.php", [
        "page_content" => $page_content,
        "title" => $title,
        "user" => $user,
        "ROOT_DIRECTORY" => $ROOT_DIRECTORY,
    ]);
}

/**
 * Показывает шаблон лейаута для НЕзарегистрированного пользователя
 * @param string $template_path Путь к папке с шаблонами
 * @param string $page_content Содержание контентной части
 * @param array $config Двумерный массив с параметрами сайта
 * @param string $title Название страницы сайта
 * @return string HTML контент
 */
function showTemplateLayoutGuest(string $template_path, string $page_content, array $config, string $title)
{
    global $ROOT_DIRECTORY;
    return includeTemplate($template_path . "layout.php", [
        "page_content" => $page_content,
        "config" => $config,
        "title" => $title,
        "user" => [],
        "ROOT_DIRECTORY" => $ROOT_DIRECTORY,
    ]);
}

/**
 * Показывает страницу с информацией о результате поиска в БД заданных параметров
 * @param string $template_path Путь к папке с шаблонами
 * @param string $message_caption Заголовок сообщения
 * @param string $message Текст сообщения
 * @param string $title Название страницы сайта
 * @param array $user Данные текущего пользователя
 */
function ifErrorResultSearch(string $template_path, string $message_caption, string $message, string $title, array $user)
{
    $page_content = showTemplateWithMessage($template_path, $message_caption, $message);
    $layout_content = showTemplateLayout($template_path, $page_content, $title, $user);
    dumpAndDie($layout_content);
}

/**
 * Отправляет подготовленное электронное сообщение (e-mail рассылку)
 * @param array $mail_config Ассоциативный массив с данными для доступа к SMTP-серверу и параметрами сообщения
 * @param array $recipient Ассоциативный массив с данными получателя в виде [e-mail => имя]
 * @param string $message_content Сообщение с HTML форматированием
 * @return array $result E-mail рассылка
 */
// function mailSendMessage(array $mail_config, array $recipient, string $message_content): array
// {
//     try {
//         // Конфигурация транспорта, отвечает за способ отправки. Содержит параметры доступа к SMTP-серверу
//         $transport = (new Swift_SmtpTransport($mail_config["domain"], $mail_config["port"]))
//             ->setUsername($mail_config["userName"])
//             ->setPassword($mail_config["password"])
//             ->setEncryption($mail_config["encryption"]);

//         // Объект библиотеки SwiftMailer, отвечает за отправку сообщений. Передаём туда созданный объект с SMTP-сервером
//         $mailer = new Swift_Mailer($transport);

//         // Формирование сообщения. Содержит параметры сообщения: текст, тему, отправителя и получателя
//         $message = (new Swift_Message($mail_config["subject"]))
//             ->setFrom([$mail_config["userName"] => $mail_config["userCaption"]])
//             ->setBcc($recipient)
//             ->setBody($message_content, "text/html");

//         // Отправка сообщения
//         $result = [
//             "success" => 1,
//             "mailerMessage" => $mailer->send($message)
//         ];
//     } catch (Exception $ex) {
//         $result = [
//             "success" => 0,
//             "errorMessage" => implode(" | ", [$ex->getLine(), $ex->getMessage(), $ex->getCode()])
//         ];
//     }

//     return $result;
// }

/**
 * Получает значение параметра запроса без обращения к $_POST
 * INPUT_POST — константа для поиска в POST-параметрах
 * @param mixed $name Название параметра, значение которого получаем
 * @return mixed
 */
function getPostVal($name)
{
    return filter_input(INPUT_POST, $name, FILTER_SANITIZE_SPECIAL_CHARS);
}

/**
 * Получает значение параметра запроса без обращения к $_GET
 * INPUT_GET — константа для поиска в GET-параметрах
 * @param mixed $name Название параметра, значение которого получаем
 * @return mixed
 */
function getGetVal($name)
{
    return filter_input(INPUT_GET, $name, FILTER_SANITIZE_SPECIAL_CHARS);
}

/**
 * Проверяет e-mail на корректность
 * @param string $value Значение поля ввода
 * @return string|null
 */
function validateEmail(string $value)
{
    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
        return "E-mail введён некорректно";
    }

    return null;
}

/**
 * Проверяет, присутствует ли в массиве значение
 * @param mixed $value Искомое значение
 * @param array $values_list Массив значений
 * @return string|null
 */
function validateValue($value, array $values_list)
{
    if (!in_array($value, $values_list)) {
        return "Выберите проект из раскрывающегося списка";
    }

    return null;
}

/**
 * Проверяет длину поля
 * @param string $value Значение поля ввода
 * @param int $min Минимальное значение символов
 * @param int $max Максимальное значение символов
 * @return string|null
 */
function validateLength(string $value, int $min, int $max)
{
    if ($value) {
        $length = mb_strlen($value);
        if ($length < $min or $length > $max) {
            return "Поле должно содержать от $min до $max символов";
        }
    }

    return null;
}

/**
 * Проверяет переданную дату на соответствие формату "ГГГГ-ММ-ДД"
 * Примеры использования:
 * is_date_valid("2019-01-01"); // true
 * is_date_valid("2016-02-29"); // true
 * is_date_valid("2019-04-31"); // false
 * is_date_valid("10.10.2010"); // false
 * is_date_valid("10/10/2010"); // false
 * @param string $date Дата в виде строки
 * @return bool true при совпадении с форматом "ГГГГ-ММ-ДД", иначе false
 */
function isDateValid(string $date): bool
{
    $format_to_check = "Y-m-d";
    $date_time_obj = date_create_from_format($format_to_check, $date);

    return $date_time_obj !== false && array_sum(date_get_last_errors()) === 0;
}

/**
 * Подсчитывает количество задач внутри каждого проекта
 * @param array $tasks Двумерный массив с данными для задач проекта
 * @param array $item Двумерный массив с названиями проектов
 * @return int $count Количество задач внутри проекта
 */
function getCountTasksProject(array $tasks, array $item)
{
    $count = 0;

    foreach ($tasks as $task) {
        if (isset($task["project"]) && isset($item["name"]) && $task["project"] == $item["name"]) {
            $count++;
        }
    }

    return $count;
}

/**
 * Рассчитывает оставшееся время (в часах) до даты окончания выполнения задачи
 * с помощью метки времени unixtime
 * @param array $tasks Двумерный массив с данными для задач проекта
 * @return array Итоговый двумерный массив
 */
function addHoursUntilEndTask(array $tasks): array
{
    foreach ($tasks as $task_key => $task) {
        if (isset($task["deadline"])) {
            $ts_end = strtotime($task["deadline"]);
            $ts_now = time();
            $ts_diff = $ts_end - $ts_now;
            $hours_until_end = floor($ts_diff / 3600);
            $tasks[$task_key]["hours_until_end"] = $hours_until_end;
        }
    }

    return $tasks;
}

/**
 * Выводит информацию в удобочитаемом виде (предназначение — отладка кода)
 * @param mixed $value Ассоциативный или двумерный массив с данными
 */
function debug($value)
{
    print("<pre>");
    print_r($value);
    print("</pre>");
}

/**
 * Выводит значение и завершает работу
 * @param mixed $value
 */
function dumpAndDie($value)
{
    die($value);
}
