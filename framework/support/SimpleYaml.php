<?php
class SimpleYaml {
    public static function parseFile(string $path): array {
        if (!file_exists($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            throw new RuntimeException('Unable to read YAML file: ' . $path);
        }

        $index = 0;
        $data = self::parseBlock($lines, $index, 0);
        return is_array($data) ? $data : [];
    }

    public static function dump(array $data): string {
        return rtrim(self::dumpValue($data, 0), "\n") . "\n";
    }

    private static function parseBlock(array $lines, int &$index, int $indent): array {
        $result = [];
        $mode = null;
        $lineCount = count($lines);

        while ($index < $lineCount) {
            $rawLine = rtrim($lines[$index], "\r\n");
            if (trim($rawLine) === '' || preg_match('/^\s*#/', $rawLine)) {
                $index++;
                continue;
            }

            $currentIndent = strspn($rawLine, ' ');
            if ($currentIndent < $indent) {
                break;
            }
            if ($currentIndent > $indent) {
                throw new RuntimeException('Invalid YAML indentation near: ' . trim($rawLine));
            }

            $trimmed = trim($rawLine);
            if (str_starts_with($trimmed, '- ')) {
                if ($mode === null) {
                    $mode = 'list';
                }
                if ($mode !== 'list') {
                    throw new RuntimeException('Mixed YAML structures are not supported.');
                }
                $result[] = self::parseListItem($lines, $index, $indent);
                continue;
            }

            if ($mode === null) {
                $mode = 'map';
            }
            if ($mode !== 'map') {
                throw new RuntimeException('Mixed YAML structures are not supported.');
            }

            [$key, $value] = self::parseMappingEntry($lines, $index, $indent);
            $result[$key] = $value;
        }

        return $result;
    }

    private static function parseListItem(array $lines, int &$index, int $indent): mixed {
        $line = trim($lines[$index]);
        $rest = substr($line, 2);
        $index++;

        if ($rest === '') {
            return self::parseChildBlock($lines, $index, $indent + 2);
        }

        if (preg_match('/^([A-Za-z0-9_.-]+):(.*)$/', $rest, $matches)) {
            $item = [];
            $key = $matches[1];
            $valueText = ltrim($matches[2]);
            $item[$key] = $valueText === ''
                ? self::parseChildBlock($lines, $index, $indent + 2)
                : self::parseScalar($valueText);

            if ($valueText !== '' && self::hasChildBlock($lines, $index, $indent + 2)) {
                $child = self::parseBlock($lines, $index, $indent + 2);
                if (!self::isList($child)) {
                    foreach ($child as $childKey => $childValue) {
                        $item[$childKey] = $childValue;
                    }
                }
            }

            return $item;
        }

        return self::parseScalar($rest);
    }

    private static function parseMappingEntry(array $lines, int &$index, int $indent): array {
        $line = trim($lines[$index]);
        if (!preg_match('/^([A-Za-z0-9_.-]+):(.*)$/', $line, $matches)) {
            throw new RuntimeException('Invalid YAML mapping entry: ' . $line);
        }

        $key = $matches[1];
        $valueText = ltrim($matches[2]);
        $index++;

        if ($valueText === '') {
            return [$key, self::parseChildBlock($lines, $index, $indent + 2)];
        }

        return [$key, self::parseScalar($valueText)];
    }

    private static function parseChildBlock(array $lines, int &$index, int $indent): mixed {
        if (!self::hasChildBlock($lines, $index, $indent)) {
            return [];
        }

        return self::parseBlock($lines, $index, $indent);
    }

    private static function hasChildBlock(array $lines, int $index, int $indent): bool {
        $lineCount = count($lines);
        while ($index < $lineCount) {
            $rawLine = rtrim($lines[$index], "\r\n");
            if (trim($rawLine) === '' || preg_match('/^\s*#/', $rawLine)) {
                $index++;
                continue;
            }

            return strspn($rawLine, ' ') >= $indent;
        }

        return false;
    }

    private static function parseScalar(string $value): mixed {
        $value = trim($value);
        if ($value === '' || $value === 'null' || $value === '~') {
            return null;
        }

        if ($value === 'true') {
            return true;
        }
        if ($value === 'false') {
            return false;
        }

        if (preg_match('/^-?\d+$/', $value)) {
            return (int)$value;
        }
        if (preg_match('/^-?\d+\.\d+$/', $value)) {
            return (float)$value;
        }

        $first = $value[0];
        $last = $value[strlen($value) - 1];
        if ($first === "'" && $last === "'") {
            return str_replace("''", "'", substr($value, 1, -1));
        }
        if ($first === '"' && $last === '"') {
            return stripcslashes(substr($value, 1, -1));
        }

        return $value;
    }

    private static function dumpValue(mixed $value, int $indent): string {
        if (!is_array($value)) {
            return str_repeat(' ', $indent) . self::dumpScalar($value) . "\n";
        }

        $yaml = '';
        if (self::isList($value)) {
            foreach ($value as $item) {
                if (is_array($item)) {
                    if ($item === []) {
                        $yaml .= str_repeat(' ', $indent) . "- {}\n";
                        continue;
                    }

                    $first = true;
                    foreach ($item as $key => $childValue) {
                        if ($first && !is_array($childValue)) {
                            $yaml .= str_repeat(' ', $indent) . '- ' . $key . ': ' . self::dumpScalar($childValue) . "\n";
                        } else {
                            $yaml .= str_repeat(' ', $indent + 2) . $key . ':';
                            if (is_array($childValue)) {
                                $yaml .= "\n" . self::dumpValue($childValue, $indent + 4);
                            } else {
                                $yaml .= ' ' . self::dumpScalar($childValue) . "\n";
                            }
                        }
                        $first = false;
                    }
                } else {
                    $yaml .= str_repeat(' ', $indent) . '- ' . self::dumpScalar($item) . "\n";
                }
            }
            return $yaml;
        }

        foreach ($value as $key => $childValue) {
            $yaml .= str_repeat(' ', $indent) . $key . ':';
            if (is_array($childValue)) {
                $yaml .= "\n" . self::dumpValue($childValue, $indent + 2);
            } else {
                $yaml .= ' ' . self::dumpScalar($childValue) . "\n";
            }
        }

        return $yaml;
    }

    private static function dumpScalar(mixed $value): string {
        if ($value === null) {
            return 'null';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }

        $string = (string)$value;
        if ($string === '') {
            return "''";
        }
        if (preg_match('/^[A-Za-z0-9_\/.:@%-]+$/', $string)) {
            return $string;
        }

        return "'" . str_replace("'", "''", $string) . "'";
    }

    private static function isList(array $value): bool {
        return array_keys($value) === range(0, count($value) - 1);
    }
}
