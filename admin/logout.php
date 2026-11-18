<?php
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';

vereis_post();
uitloggen();
header('Location: login.php', true, 303);
exit;
