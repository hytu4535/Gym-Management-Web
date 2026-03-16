<?php
// Backward-compatible endpoint: reuse centralized register/cancel transaction logic.
$_POST['action'] = 'cancel';
require __DIR__ . '/class-register-process.php';
