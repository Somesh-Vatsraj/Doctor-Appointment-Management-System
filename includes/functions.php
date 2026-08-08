<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e(mixed $value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

function redirect(string $url): void {
    header("Location: " . $url);
    exit;
}

function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function status_badge(string $status): string {
    $classes = [
        'Pending'             => 'bg-warning text-dark',
        'Confirmed'           => 'bg-primary',
        'Completed'           => 'bg-success',
        'Canceled by Patient' => 'bg-danger',
        'Canceled by Doctor'  => 'bg-danger',
        'Rejected'            => 'bg-secondary',
        'Paid'                => 'bg-success',
        'Partially Paid'      => 'bg-info text-dark',
    ];
    $badgeClass = $classes[$status] ?? 'bg-secondary';
    return '<span class="badge ' . $badgeClass . '">' . e($status) . '</span>';
}