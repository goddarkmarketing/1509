<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/student_auth.php';
require_once dirname(__DIR__) . '/includes/student_account.php';

requireStudentLogin(studentStartLearningUrl());
redirect('/public/profile.php?tab=courses');
