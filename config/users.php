<?php

function getUsers(): array
{
    $json = $_ENV['APP_USERS'] ?? '[]';

    $users = json_decode($json, true);

    if (!is_array($users) || json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException('Invalid APP_USERS configuration');
    }

    return $users;
}

function authenticateUser(string $username, string $password): ?array
{
    foreach (getUsers() as $user) {
        if (
            $user['active'] &&
            hash_equals($user['username'], $username) &&
            password_verify($password, $user['password'])
        ) {
            return [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ];
        }
    }

    return null;
}

function getUserByUsername(string $username): ?array
{
    foreach (getUsers() as $user) {
        if (
            strtolower($user['username']) === strtolower($username)
            && ($user['active'] ?? false) === true
        ) {
            return $user;
        }
    }

    return null;
}