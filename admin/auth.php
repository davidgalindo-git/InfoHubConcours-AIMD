<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function require_admin(): void
{
  if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
  }
}

