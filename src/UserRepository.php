<?php

declare(strict_types=1);

namespace App;

/**
 * In-memory user store. Swap this class for a real database repository and the
 * rest of the app stays untouched.
 */
final class UserRepository
{
    /** @var list<array{id: int, name: string, email: string, role: string}> */
    private array $users;

    public function __construct()
    {
        $this->users = [
            ['id' => 1, 'name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'role' => 'admin'],
            ['id' => 2, 'name' => 'Alan Turing', 'email' => 'alan@example.com', 'role' => 'engineer'],
            ['id' => 3, 'name' => 'Grace Hopper', 'email' => 'grace@example.com', 'role' => 'engineer'],
        ];
    }

    /** @return list<array{id: int, name: string, email: string, role: string}> */
    public function all(?string $role = null): array
    {
        if ($role === null) {
            return $this->users;
        }

        return array_values(array_filter(
            $this->users,
            static fn (array $user): bool => $user['role'] === $role
        ));
    }

    /** @return array{id: int, name: string, email: string, role: string}|null */
    public function find(int $id): ?array
    {
        foreach ($this->users as $user) {
            if ($user['id'] === $id) {
                return $user;
            }
        }

        return null;
    }

    /** @param array{name: string, email: string, role?: string} $data */
    public function create(array $data): array
    {
        $user = [
            'id' => (max(array_column($this->users, 'id')) ?? 0) + 1,
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'] ?? 'viewer',
        ];

        $this->users[] = $user;

        return $user;
    }
}