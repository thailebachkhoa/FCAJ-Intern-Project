<?php

/**
 * Auth Middleware Class
 * Handles session verification and role-based access control
 */
class Auth
{
    /**
     * Check if user is logged in
     * @return bool
     */
    public static function check()
    {
        return isset($_SESSION['user']) && !empty($_SESSION['user']);
    }

    /**
     * Get current logged-in user
     * @return array|null
     */
    public static function user()
    {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Get user ID
     * @return int|null
     */
    public static function id()
    {
        return $_SESSION['user']['id'] ?? null;
    }

    /**
     * Get user role
     * @return string|null
     */
    public static function role()
    {
        return $_SESSION['user']['role'] ?? null;
    }

    /**
     * Check if user has specific role
     * @param string $role
     * @return bool
     */
    public static function hasRole($role)
    {
        return self::role() === $role;
    }

    /**
     * Check if user is admin
     * @return bool
     */
    public static function isAdmin()
    {
        return self::hasRole('admin');
    }

    /**
     * Check if user is member
     * @return bool
     */
    public static function isMember()
    {
        return self::hasRole('member');
    }

    /**
     * Check if user is guest
     * @return bool
     */
    public static function isGuest()
    {
        return !self::check();
    }

    /**
     * Get user status
     * @return string|null
     */
    public static function status()
    {
        return $_SESSION['user']['status'] ?? null;
    }

    /**
     * Check if user is active
     * @return bool
     */
    public static function isActive()
    {
        return self::status() === 'active';
    }

    /**
     * Verify if user is locked
     * @return bool
     */
    public static function isLocked()
    {
        return self::status() === 'locked';
    }

    /**
     * Set user session
     * @param array $user
     */
    public static function setUser($user)
    {
        $_SESSION['user'] = $user;
    }

    /**
     * Logout current user
     */
    public static function logout()
    {
        session_destroy();
    }
}