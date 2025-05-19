<?php
require_once("php/config.php");
global $config, $template_path, $ROOT_DIRECTORY;

$title = "Дела в порядке | Гостевая страница";

// Если сайт находится в неактивном состоянии, выходим на страницу с сообщением о техническом обслуживании
ifSiteDisabled($config, $template_path, $title);

$page_content = includeTemplate(($config["templatePath"] . "guest.php"), []);

$layout_content = includeTemplate($template_path . "layout.php", [
    "page_content" => $page_content,
    "config" => $config,
    "title" => $title,
    "ROOT_DIRECTORY" => $ROOT_DIRECTORY,
]);

print($layout_content);
