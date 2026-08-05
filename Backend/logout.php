<?php
/**
 * logout.php — Destroy session and redirect to login
 */
require_once '../includes/auth.php';
auth_logout(); // handles session destroy + redirect
