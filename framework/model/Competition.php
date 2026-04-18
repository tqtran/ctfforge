<?php
class Competition {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare('SELECT * FROM competitions WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findAll(): array {
        return $this->db->query('SELECT comp.*, u.username as creator FROM competitions comp LEFT JOIN users u ON comp.created_by = u.id ORDER BY comp.created_at DESC')->fetchAll();
    }

    public function findActive(): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM competitions WHERE (start_time IS NULL OR start_time <= NOW()) AND (end_time IS NULL OR end_time >= NOW()) ORDER BY created_at DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create(string $name, string $description, ?string $startTime, ?string $endTime, int $createdBy): int {
        $stmt = $this->db->prepare(
            'INSERT INTO competitions (name, description, start_time, end_time, created_by) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $description, $startTime, $endTime, $createdBy]);
        return (int)$this->db->lastInsertId();
    }

    public function getChallenges(int $competitionId): array {
        $stmt = $this->db->prepare(
            'SELECT ch.* FROM challenges ch 
             JOIN competition_challenges cc ON ch.id = cc.challenge_id 
             WHERE cc.competition_id = ?'
        );
        $stmt->execute([$competitionId]);
        return $stmt->fetchAll();
    }

    public function addChallenge(int $competitionId, int $challengeId): bool {
        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO competition_challenges (competition_id, challenge_id) VALUES (?, ?)'
        );
        return $stmt->execute([$competitionId, $challengeId]);
    }

    public function removeChallenge(int $competitionId, int $challengeId): bool {
        $stmt = $this->db->prepare(
            'DELETE FROM competition_challenges WHERE competition_id = ? AND challenge_id = ?'
        );
        return $stmt->execute([$competitionId, $challengeId]);
    }

    public function countChallenges(int $competitionId): int {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM competition_challenges WHERE competition_id = ?');
        $stmt->execute([$competitionId]);
        return (int)$stmt->fetchColumn();
    }
}
