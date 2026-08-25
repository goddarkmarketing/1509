<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/student_auth.php';
studentLogout();
redirect('/public/index.php');
