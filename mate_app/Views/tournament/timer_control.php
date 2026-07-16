<?php
$event = $event ?? [];
$tournament = $tournament ?? null;
$latestRound = $latestRound ?? null;
$timerState = $timerState ?? [];
$eventId = (int) ($event['id'] ?? 0);
$roundNumber = (int) ($tournament['current_round'] ?? 0);
$roundComplete = ($latestRound['status'] ?? '') === 'completed';
$durationMinutes = max(1, (int) round(((int) ($timerState['duration_seconds'] ?? 1200)) / 60));
$jsonTimerState = json_encode($timerState, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$timerPollingEnabled = $timerPollingEnabled ?? true;
?>

<style>
    .timer-remote-shell {
        width: min(620px, 100%);
        margin: 0 auto;
        padding: 1rem 0 2rem;
    }

    .timer-remote-header {
        margin-bottom: 1.25rem;
    }

    .timer-remote-header h1 {
        margin: 0.25rem 0 0.45rem;
        font-size: clamp(1.75rem, 7vw, 3rem);
        line-height: 1.08;
        letter-spacing: 0;
    }

    .timer-remote-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem 1rem;
        color: var(--muted);
    }

    .timer-remote-panel {
        padding: clamp(1rem, 4vw, 1.5rem);
        border: 1px solid rgba(216, 226, 240, 0.12);
        border-radius: 8px;
        background: rgba(14, 21, 38, 0.96);
    }

    .timer-remote-status {
        color: var(--gold-bright);
        font-size: 0.82rem;
        font-weight: 900;
        letter-spacing: 0.08em;
        text-align: center;
        text-transform: uppercase;
    }

    .timer-remote-clock {
        margin: 0.35rem 0 1.25rem;
        color: var(--gold-bright);
        font-size: clamp(4.5rem, 20vw, 8rem);
        font-variant-numeric: tabular-nums;
        font-weight: 900;
        line-height: 1;
        text-align: center;
    }

    .timer-remote-clock.is-low {
        color: #ff6565;
    }

    .timer-remote-actions {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.65rem;
    }

    .timer-remote-actions form,
    .timer-duration-form {
        margin: 0;
    }

    .timer-remote-actions button {
        width: 100%;
        min-height: 54px;
        font-size: 1rem;
        font-weight: 900;
    }

    .timer-duration-form {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 0.65rem;
        align-items: end;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(216, 226, 240, 0.1);
    }

    .timer-duration-form label {
        display: grid;
        gap: 0.35rem;
        color: var(--muted);
        font-size: 0.85rem;
        font-weight: 800;
    }

    .timer-duration-form input {
        min-height: 46px;
    }

    .timer-remote-state {
        min-height: 1.5rem;
        margin: 0.9rem 0 0;
        color: var(--muted);
        text-align: center;
    }

    .timer-remote-links {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        margin-top: 1rem;
    }

    @media (max-width: 440px) {
        .timer-remote-actions {
            grid-template-columns: 1fr;
        }

        .timer-duration-form {
            grid-template-columns: 1fr;
        }
    }
</style>

<section class="timer-remote-shell">
    <div class="timer-remote-header">
        <div class="hero-kicker">Live Timer Remote</div>
        <h1><?= htmlspecialchars($event['title'] ?? 'Tournament Timer') ?></h1>
        <div class="timer-remote-meta">
            <span><?= htmlspecialchars($latestRound['name'] ?? ($roundNumber > 0 ? 'Round ' . $roundNumber : 'No round ready')) ?></span>
            <span><?= htmlspecialchars($event['venue_name'] ?? '') ?></span>
        </div>
    </div>

    <section class="timer-remote-panel">
        <div class="timer-remote-status" id="remote-timer-status">
            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $timerState['status'] ?? 'ready'))) ?>
        </div>
        <div class="timer-remote-clock" id="remote-timer-clock">20:00</div>

        <div class="timer-remote-actions">
            <?php foreach (['start' => 'Start', 'pause' => 'Pause', 'reset' => 'Reset'] as $action => $label): ?>
                <form method="POST" action="index.php?page=timer-control-update">
                    <input type="hidden" name="event_id" value="<?= $eventId ?>">
                    <input type="hidden" name="timer_action" value="<?= $action ?>">
                    <button
                        class="btn<?= $action === 'start' ? '' : ' btn-outline' ?>"
                        type="submit"
                        <?= $action === 'start' && ($roundNumber <= 0 || $roundComplete) ? 'disabled' : '' ?>
                    ><?= $label ?></button>
                </form>
            <?php endforeach; ?>
        </div>

        <form class="timer-duration-form" method="POST" action="index.php?page=timer-control-update">
            <input type="hidden" name="event_id" value="<?= $eventId ?>">
            <input type="hidden" name="timer_action" value="set_duration">
            <label for="duration-minutes">
                Round duration in minutes
                <input
                    id="duration-minutes"
                    type="number"
                    name="duration_minutes"
                    min="1"
                    max="240"
                    step="1"
                    value="<?= $durationMinutes ?>"
                    required
                >
            </label>
            <button class="btn btn-outline" type="submit">Set</button>
        </form>

        <p class="timer-remote-state" id="remote-timer-note">
            <?php if ($roundComplete): ?>
                Round complete
            <?php elseif ($roundNumber <= 0): ?>
                Waiting for pairings
            <?php else: ?>
                Ready for round <?= $roundNumber ?>
            <?php endif; ?>
        </p>
    </section>

    <div class="timer-remote-links">
        <a class="btn btn-outline" href="index.php?page=live-display&event=<?= $eventId ?>" target="_blank">Open Second Screen</a>
        <a class="btn btn-outline" href="index.php?page=tournament-manager&id=<?= $eventId ?>">Tournament Manager</a>
    </div>
</section>

<script>
    const timerEventId = <?= $eventId ?>;
    const timerEndpoint = `index.php?page=live-display-data&event=${timerEventId}`;
    const remotePollingEnabled = <?= $timerPollingEnabled ? 'true' : 'false' ?>;
    let remoteTimer = <?= $jsonTimerState ?: '{}' ?>;
    let remoteSeconds = Number(remoteTimer.remaining_seconds || 0);
    let remoteStatus = String(remoteTimer.status || 'ready');

    function renderRemoteTimer() {
        const minutes = Math.floor(remoteSeconds / 60);
        const seconds = remoteSeconds % 60;
        const clock = document.getElementById('remote-timer-clock');
        clock.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        clock.classList.toggle('is-low', remoteSeconds > 0 && remoteSeconds <= 180);
        document.getElementById('remote-timer-status').textContent = remoteStatus.replaceAll('_', ' ');
    }

    async function refreshRemoteTimer() {
        try {
            const response = await fetch(timerEndpoint, { cache: 'no-store' });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();

            if (!payload.ok || !payload.data?.timer) {
                return;
            }

            remoteTimer = payload.data.timer;
            remoteSeconds = Number(remoteTimer.remaining_seconds || 0);
            remoteStatus = String(remoteTimer.status || 'ready');
            renderRemoteTimer();

            const roundComplete = String(payload.data.tournament?.round_status || '').toLowerCase() === 'completed';
            document.getElementById('remote-timer-note').textContent = roundComplete
                ? 'Round complete'
                : `${payload.data.tournament?.round_name || 'Round'} ready`;
        } catch (error) {
            return;
        }
    }

    renderRemoteTimer();
    setInterval(() => {
        if (remoteStatus === 'running' && remoteSeconds > 0) {
            remoteSeconds--;
            renderRemoteTimer();
        }
    }, 1000);
    if (remotePollingEnabled) {
        setInterval(refreshRemoteTimer, 2000);
    }
</script>
