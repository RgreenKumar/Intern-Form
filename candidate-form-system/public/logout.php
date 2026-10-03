<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$_SESSION = [];
session_destroy();
redirect('public/index.php');
