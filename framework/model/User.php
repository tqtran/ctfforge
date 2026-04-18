<?php
class User {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public static function availableRoles(): array {
        return ['participant', 'author', 'organizer', 'admin'];
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByUsername(string $username): ?array {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findAll(): array {
        return $this->db->query('SELECT * FROM users ORDER BY created_at DESC, username ASC')->fetchAll();
    }

    public function countAll(): int {
        return (int)$this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public function countByRole(): array {
        $counts = array_fill_keys(self::availableRoles(), 0);
        $rows = $this->db->query('SELECT role, COUNT(*) AS total FROM users GROUP BY role')->fetchAll();
        foreach ($rows as $row) {
            $counts[$row['role']] = (int)$row['total'];
        }
        return $counts;
    }

    public function create(string $username, string $email, string $password, string $role = 'participant'): int {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare(
            'INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$username, $email, $hash, $this->normalizeRole($role)]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, string $username, string $email, string $role, ?string $password = null): bool {
        if ($password !== null && $password !== '') {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $this->db->prepare(
                'UPDATE users SET username = ?, email = ?, role = ?, password_hash = ? WHERE id = ?'
            );
            return $stmt->execute([$username, $email, $this->normalizeRole($role), $hash, $id]);
        }

        $stmt = $this->db->prepare(
            'UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?'
        );
        return $stmt->execute([$username, $email, $this->normalizeRole($role), $id]);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare('DELETE FROM users WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function verifyPassword(array $user, string $password): bool {
        return password_verify($password, $user['password_hash']);
    }

    private function normalizeRole(string $role): string {
        return in_array($role, self::availableRoles(), true) ? $role : 'participant';
    }
}
