<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/homepage.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/public/index.php');
}

verifyCsrf();
$result = subscribeNewsletter(trim($_POST['email'] ?? ''));
flash($result['ok'] ? 'newsletter_success' : 'newsletter_error', $result['message']);
redirect('/public/index.php#newsletter');
