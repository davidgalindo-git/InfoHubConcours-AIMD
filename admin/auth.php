<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/auth.php';

function require_admin(): void
{
  auth_expire_timed_suspensions_global();
  auth_refresh_user();
  $isRoleAdmin = !empty($_SESSION['user']) && (string)$_SESSION['user']['role'] === 'admin';
  if (empty($_SESSION['admin_logged_in']) && !$isRoleAdmin) {
    header('Location: login.php');
    exit;
  }
}

