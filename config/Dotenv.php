<?php
/**
 * Lightweight .env file parser.
 * No external dependencies required.
 */
class Dotenv
{
    /**
     * Load environment variables from a .env file.
     *
     * @param string $path Path to the .env file
     * @param bool   $override Whether to override existing env vars
     *
     * @throws RuntimeException If the file cannot be read
     */
    public static function load(string $path, bool $override = false): void
    {
        if (!file_exists($path)) {
            throw new RuntimeException("Environment file not found: {$path}");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            // Find the first = sign
            $position = strpos($line, '=');
            if ($position === false) {
                continue;
            }

            $key   = trim(substr($line, 0, $position));
            $value = trim(substr($line, $position + 1));

            // Remove surrounding quotes
            if (strlen($value) > 1) {
                $quote = $value[0];
                if (($quote === '"' || $quote === "'") && $value[strlen($value) - 1] === $quote) {
                    $value = substr($value, 1, -1);
                }
            }

            // Handle escaped newlines in double-quoted strings
            $value = str_replace('\n', "\n", $value);

            if ($override || !array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
            }

            // Also make available via getenv/putenv for non-PHP processes
            if ($override || getenv($key) === false) {
                putenv("{$key}={$value}");
            }
        }
    }

    /**
     * Get an environment variable with a default fallback.
     *
     * @param string     $key
     * @param mixed      $default
     *
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}
