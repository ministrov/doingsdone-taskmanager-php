<?php
require_once("php/config.php");
global $ROOT_DIRECTORY;
session_start();

$_SESSION = [];
header("Location: {$ROOT_DIRECTORY}/guest.php");
