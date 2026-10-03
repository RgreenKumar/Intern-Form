<?php
/**
 * Auth guards
 */

function require_admin_login() {
    if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
        redirect('public/login.php');
    }
}

function require_candidate_login() {
    if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'CANDIDATE') {
        redirect('public/login.php');
    }
}

function is_logged_in() {
    return !empty($_SESSION['user_id']);
}
