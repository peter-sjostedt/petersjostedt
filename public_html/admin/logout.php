<?php
/**
 * Admin Logout
 */

require_once __DIR__ . '/../includes/config.php';

Session::start();

$userId = Session::getUserId();

if ($userId) {
    Logger::write(Logger::ACTION_LOGOUT, $userId, 'Admin logout');
}

Session::logout();

header('Location: login.php');
exit;
