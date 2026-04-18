<?php
abstract class PluginBase {
    abstract public function renderForm(array $data = []): string;
    abstract public function renderQuestion(array $dataset): string;
    abstract public function checkAnswer(array $dataset, string $answer): bool;
    abstract public function getType(): string;
}
