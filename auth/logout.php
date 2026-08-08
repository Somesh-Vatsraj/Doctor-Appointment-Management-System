<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

session_unset();
session_destroy();
session_start();
set_flash('info', 'You have been successfully logged out.');
redirect('login.php');