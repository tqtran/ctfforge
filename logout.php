<?php
require_once __DIR__ . '/framework/bootstrap.php';
session_destroy();
redirect(APP_URL . '/index.php');
