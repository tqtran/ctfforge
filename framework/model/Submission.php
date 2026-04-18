<?php
class Submission {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create(int $userId, int $challengeId, int $datasetId, string $answer, bool $isCorrect): int {
        $stmt = $this->db->prepare(
            'INSERT INTO submissions (user_id, challenge_id, dataset_id, answer, is_correct) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $challengeId, $datasetId, $answer, $isCorrect ? 1 : 0]);
        return (int)$this->db->lastInsertId();
    }

    public function findByUser(int $userId): array {
        $stmt = $this->db->prepare(
            'SELECT s.*, c.title as challenge_title, c.points FROM submissions s 
             JOIN challenges c ON s.challenge_id = c.id 
             WHERE s.user_id = ? ORDER BY s.submitted_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function countCorrect(int $userId): int {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM submissions WHERE user_id = ? AND is_correct = 1');
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public function totalPoints(int $userId): int {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(c.points), 0) FROM submissions s 
             JOIN challenges c ON s.challenge_id = c.id 
             WHERE s.user_id = ? AND s.is_correct = 1'
        );
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public function hasCorrectSubmission(int $userId, int $challengeId): bool {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM submissions WHERE user_id = ? AND challenge_id = ? AND is_correct = 1'
        );
        $stmt->execute([$userId, $challengeId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function getStatsByChallenge(int $challengeId): array {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as total, SUM(is_correct) as correct FROM submissions WHERE challenge_id = ?'
        );
        $stmt->execute([$challengeId]);
        return $stmt->fetch();
    }
}
