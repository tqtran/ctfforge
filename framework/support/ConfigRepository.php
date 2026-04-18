<?php
class ConfigRepository {
    public function __construct(
        private string $configFile,
        private string $templateFile,
        private string $secretsFile,
        private string $secretsExampleFile
    ) {}

    public function loadConfig(): array {
        return SimpleYaml::parseFile($this->configFile);
    }

    public function loadTemplate(): array {
        return SimpleYaml::parseFile($this->templateFile);
    }

    public function loadSecrets(): array {
        if (!$this->hasSecretsFile()) {
            throw new RuntimeException($this->missingSecretsMessage());
        }

        return SimpleYaml::parseFile($this->secretsFile);
    }

    public function saveConfig(array $config): void {
        $this->write($this->configFile, $config);
    }

    public function saveSecrets(array $secrets): void {
        $this->write($this->secretsFile, $secrets);
    }

    public function hasSecretsFile(): bool {
        return file_exists($this->secretsFile);
    }

    public function missingSecretsMessage(): string {
        return 'Database credentials not configured. Copy framework/secrets/db.yaml.example to framework/secrets/db.yaml and fill in your credentials.';
    }

    public static function getValue(array $data, string $path, mixed $default = null): mixed {
        $current = $data;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    public static function setValue(array &$data, string $path, mixed $value): void {
        $segments = explode('.', $path);
        $current =& $data;

        foreach ($segments as $index => $segment) {
            if ($index === count($segments) - 1) {
                $current[$segment] = $value;
                return;
            }

            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current =& $current[$segment];
        }
    }

    public static function normalizeBoolean(mixed $value): bool {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string)$value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function write(string $path, array $data): void {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        file_put_contents($path, SimpleYaml::dump($data), LOCK_EX);
    }
}
