<?php
/**
 * Partner Logout
 */

require_once __DIR__ . '/../includes/config.php';

Session::start();

if (Session::isLoggedIn()) {
    $userId = Session::getUserId();
    Logger::write('LOGOUT', $userId, 'Partner logout');
    Session::logout();
}

header('Location: login.php');
exit;
