<?php

class LiveTimer
{
    private string $storageDirectory;

    public function __construct(?string $storageDirectory = null)
    {
        $preferredDirectory = $storageDirectory
            ?? dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'live_timers';

        if (!$this->ensureDirectory($preferredDirectory)) {
            $preferredDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mate_tournaments_live_timers';
        }

        if (!$this->ensureDirectory($preferredDirectory)) {
            throw new RuntimeException('Live timer storage is not writable.');
        }

        $this->storageDirectory = $preferredDirectory;
    }

    public function state(int $eventId, int $roundNumber, int $defaultSeconds = 1200): array
    {
        return $this->withState($eventId, $roundNumber, $defaultSeconds, function (array $state): array {
            return $state;
        });
    }

    public function start(int $eventId, int $roundNumber, int $defaultSeconds = 1200): array
    {
        return $this->withState($eventId, $roundNumber, $defaultSeconds, function (array $state): array {
            $present = $this->presentState($state);

            if ((int) $present['remaining_seconds'] <= 0) {
                return $state;
            }

            $state['remaining_seconds'] = (int) $present['remaining_seconds'];
            $state['status'] = 'running';
            $state['started_at'] = microtime(true);

            return $state;
        });
    }

    public function pause(int $eventId, int $roundNumber, int $defaultSeconds = 1200): array
    {
        return $this->withState($eventId, $roundNumber, $defaultSeconds, function (array $state): array {
            $present = $this->presentState($state);
            $state['remaining_seconds'] = (int) $present['remaining_seconds'];
            $state['status'] = $state['remaining_seconds'] > 0 ? 'paused' : 'finished';
            $state['started_at'] = null;

            return $state;
        });
    }

    public function reset(int $eventId, int $roundNumber, int $defaultSeconds = 1200): array
    {
        return $this->withState($eventId, $roundNumber, $defaultSeconds, function (array $state): array {
            $state['remaining_seconds'] = (int) $state['duration_seconds'];
            $state['status'] = 'ready';
            $state['started_at'] = null;

            return $state;
        });
    }

    public function setDuration(int $eventId, int $roundNumber, int $seconds): array
    {
        $seconds = max(60, min(14400, $seconds));

        return $this->withState($eventId, $roundNumber, $seconds, function (array $state) use ($seconds): array {
            $state['duration_seconds'] = $seconds;
            $state['remaining_seconds'] = $seconds;
            $state['status'] = 'ready';
            $state['started_at'] = null;

            return $state;
        });
    }

    public function completeRound(int $eventId, int $roundNumber, int $defaultSeconds = 1200): array
    {
        return $this->withState($eventId, $roundNumber, $defaultSeconds, function (array $state): array {
            $present = $this->presentState($state);
            $state['remaining_seconds'] = (int) $present['remaining_seconds'];
            $state['status'] = 'complete';
            $state['started_at'] = null;

            return $state;
        });
    }

    private function withState(
        int $eventId,
        int $roundNumber,
        int $defaultSeconds,
        callable $callback
    ): array {
        if ($eventId <= 0) {
            throw new InvalidArgumentException('A valid event is required for the live timer.');
        }

        $path = $this->storageDirectory . DIRECTORY_SEPARATOR . 'event_' . $eventId . '.json';
        $handle = fopen($path, 'c+');

        if (!$handle || !flock($handle, LOCK_EX)) {
            throw new RuntimeException('Unable to lock live timer state.');
        }

        try {
            rewind($handle);
            $raw = stream_get_contents($handle);
            $decoded = $raw !== false && trim($raw) !== '' ? json_decode($raw, true) : null;
            $state = $this->normalizeState(is_array($decoded) ? $decoded : [], $roundNumber, $defaultSeconds);
            $state = $callback($state);
            $state['updated_at'] = date('c');

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            fflush($handle);

            return $this->presentState($state);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function normalizeState(array $state, int $roundNumber, int $defaultSeconds): array
    {
        $defaultSeconds = max(60, min(14400, $defaultSeconds));
        $duration = max(60, min(14400, (int) ($state['duration_seconds'] ?? $defaultSeconds)));
        $storedRound = (int) ($state['round_number'] ?? 0);

        if ($roundNumber > 0 && $storedRound !== $roundNumber) {
            return $this->newState($roundNumber, $duration);
        }

        $status = (string) ($state['status'] ?? 'ready');

        if (!in_array($status, ['ready', 'running', 'paused', 'finished', 'complete'], true)) {
            $status = 'ready';
        }

        return [
            'round_number' => $roundNumber > 0 ? $roundNumber : $storedRound,
            'duration_seconds' => $duration,
            'remaining_seconds' => max(0, min($duration, (int) ($state['remaining_seconds'] ?? $duration))),
            'status' => $status,
            'started_at' => is_numeric($state['started_at'] ?? null) ? (float) $state['started_at'] : null,
            'updated_at' => $state['updated_at'] ?? null,
        ];
    }

    private function newState(int $roundNumber, int $duration): array
    {
        return [
            'round_number' => $roundNumber,
            'duration_seconds' => $duration,
            'remaining_seconds' => $duration,
            'status' => 'ready',
            'started_at' => null,
            'updated_at' => date('c'),
        ];
    }

    private function presentState(array $state): array
    {
        $remaining = (int) ($state['remaining_seconds'] ?? 0);
        $status = (string) ($state['status'] ?? 'ready');

        if ($status === 'running' && is_numeric($state['started_at'] ?? null)) {
            $elapsed = max(0, (int) floor(microtime(true) - (float) $state['started_at']));
            $remaining = max(0, $remaining - $elapsed);

            if ($remaining === 0) {
                $status = 'finished';
            }
        }

        return [
            'round_number' => (int) ($state['round_number'] ?? 0),
            'duration_seconds' => (int) ($state['duration_seconds'] ?? 1200),
            'remaining_seconds' => $remaining,
            'status' => $status,
            'updated_at' => $state['updated_at'] ?? null,
        ];
    }

    private function ensureDirectory(string $directory): bool
    {
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            return false;
        }

        return is_writable($directory);
    }
}
