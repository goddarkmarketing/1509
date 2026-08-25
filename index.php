<?php
declare(strict_types=1);

$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$target = ($base === '' ? '' : $base) . '/public/index.php';
header('Location: ' . $target, true, 302);
exit;
