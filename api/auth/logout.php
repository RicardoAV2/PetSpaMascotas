<?php
/**
 * LOGOUT
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Logger.php';

Auth::setConnection($conn);
Auth::logout();

header('Location: /petspa/public/login.php?logout=1');
exit();
?>
