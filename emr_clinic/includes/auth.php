<?php
// auth.php

// Authentication helper functions

function isLoggedIn() {
    return isset($_SESSION['user']) && !empty($_SESSION['user']);
}

function login($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user'] = $user;
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
}

function requireRole($allowedRoles) {
    if (!isLoggedIn()) {
        header('Location: ../auth/login.php');
        exit();
    }
    
    $userRole = $_SESSION['user']['role'];
    if (!in_array($userRole, $allowedRoles)) {
        header('Location: ../index.php?error=unauthorized');
        exit();
    }
}

function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

function getCurrentUserId() {
    return $_SESSION['user']['id'] ?? null;
}

function getCurrentUserRole() {
    return $_SESSION['user']['role'] ?? null;
}