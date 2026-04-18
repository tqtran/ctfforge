<?php
class Challenge {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare('SELECT * FROM challenges WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findAll(): array {
        return $this->db->query('SELECT c.*, u.username as author FROM challenges c LEFT JOIN users u ON c.created_by = u.id ORDER BY c.created_at DESC')->fetchAll();
    }

    public function findByAuthor(int $userId): array {
        $stmt = $this->db->prepare('SELECT * FROM challenges WHERE created_by = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function create(string $title, string $description, string $pluginType, string $template, int $points, int $createdBy): int {
        $stmt = $this->db->prepare(
            'INSERT INTO challenges (title, description, plugin_type, template, points, created_by) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$title, $description, $pluginType, $template, $points, $createdBy]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, string $title, string $description, string $template, int $points): bool {
        $stmt = $this->db->prepare(
            'UPDATE challenges SET title=?, description=?, template=?, points=? WHERE id=?'
        );
        return $stmt->execute([$title, $description, $template, $points, $id]);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare('DELETE FROM challenges WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function getDatasets(int $challengeId): array {
        $stmt = $this->db->prepare('SELECT * FROM challenge_datasets WHERE challenge_id = ? ORDER BY id');
        $stmt->execute([$challengeId]);
        return $stmt->fetchAll();
    }

    public function addDataset(int $challengeId, string $dataValue, string $acceptableAnswers, ?string $imagePath = null): int {
        $stmt = $this->db->prepare(
            'INSERT INTO challenge_datasets (challenge_id, data_value, acceptable_answers, image_path) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$challengeId, $dataValue, $acceptableAnswers, $imagePath]);
        return (int)$this->db->lastInsertId();
    }

    public function deleteDatasets(int $challengeId): bool {
        $stmt = $this->db->prepare('DELETE FROM challenge_datasets WHERE challenge_id = ?');
        return $stmt->execute([$challengeId]);
    }
}
