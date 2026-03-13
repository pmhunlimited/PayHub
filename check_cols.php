<?php
// We can't rely on the sandbox environment to have a real DB running,
// but I can check the ensure_critical_tables code I wrote.
require 'php-version/includes/functions.php';
// The script might die if DB not connected, but I want to see what's in the file.
