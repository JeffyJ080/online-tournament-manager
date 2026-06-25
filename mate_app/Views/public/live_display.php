<?php
$eventId = (int) ($eventId ?? 0);
$payload = $payload ?? [];
$jsonPayload = json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$roundMinutes = max(1, min(240, (int) ($_GET['minutes'] ?? 20)));
?>

<style>
    header,
    footer {
        display: none;
    }

    main {
        max-width: none;
        min-height: 100vh;
        padding: 1.25rem;
    }

    body {
        background: #060914;
    }

    .display-shell {
        min-height: calc(100vh - 2.5rem);
        display: grid;
        grid-template-rows: auto 1fr;
        gap: 1rem;
    }

    .display-top {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 1rem;
        align-items: end;
        padding: 1rem 1.2rem;
        border: 1px solid rgba(216, 226, 240, 0.1);
        border-radius: 8px;
        background: rgba(14, 21, 38, 0.92);
    }

    .display-kicker {
        color: var(--gold-bright);
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        font-size: 0.85rem;
    }

    .display-title {
        margin: 0.3rem 0 0;
        font-size: clamp(2.4rem, 5vw, 5.5rem);
        line-height: 1;
        letter-spacing: 0;
    }

    .display-meta {
        color: var(--muted);
        font-size: clamp(1rem, 1.4vw, 1.35rem);
        text-align: right;
    }

    .display-timer {
        margin-top: 0.6rem;
        color: var(--gold-bright);
        font-size: clamp(2.4rem, 5vw, 5.2rem);
        font-weight: 900;
        line-height: 1;
    }

    .timer-controls {
        display: flex;
        justify-content: flex-end;
        gap: 0.45rem;
        margin-top: 0.55rem;
    }

    .timer-controls button {
        min-height: 36px;
        padding: 0.45rem 0.75rem;
        border: 1px solid rgba(240, 180, 42, 0.45);
        border-radius: 8px;
        background: rgba(240, 180, 42, 0.1);
        color: var(--gold-bright);
        font-weight: 800;
        cursor: pointer;
    }

    .display-grid {
        display: grid;
        grid-template-columns: minmax(380px, 0.85fr) minmax(520px, 1.15fr);
        gap: 1rem;
        min-height: 0;
    }

    .display-panel {
        overflow: hidden;
        border: 1px solid rgba(216, 226, 240, 0.1);
        border-radius: 8px;
        background: rgba(14, 21, 38, 0.92);
    }

    .display-panel-header {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.9rem 1rem;
        border-bottom: 1px solid rgba(216, 226, 240, 0.08);
    }

    .display-panel h2 {
        margin: 0;
        font-size: clamp(1.4rem, 2vw, 2.1rem);
    }

    .display-note {
        color: var(--muted);
        font-size: 0.95rem;
    }

    .display-list {
        display: grid;
    }

    .standing-row,
    .pairing-row {
        display: grid;
        gap: 0.75rem;
        align-items: center;
        padding: 0.8rem 1rem;
        border-bottom: 1px solid rgba(216, 226, 240, 0.07);
    }

    .standing-row {
        grid-template-columns: 64px minmax(0, 1fr) 90px;
    }

    .pairing-row {
        grid-template-columns: 82px minmax(0, 1fr) 80px minmax(0, 1fr) 130px;
    }

    .rank,
    .board {
        color: var(--gold-bright);
        font-weight: 900;
        font-size: 1.5rem;
    }

    .display-name {
        min-width: 0;
        overflow: hidden;
        color: var(--text);
        font-size: clamp(1.25rem, 1.6vw, 1.8rem);
        font-weight: 800;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .score {
        color: var(--gold-bright);
        font-size: clamp(1.4rem, 1.9vw, 2.2rem);
        font-weight: 900;
        text-align: right;
    }

    .versus {
        color: var(--muted);
        font-weight: 900;
        text-align: center;
    }

    .result {
        color: var(--text);
        font-weight: 800;
        text-align: right;
    }

    .empty-display {
        padding: 2rem;
        color: var(--muted);
        font-size: 1.2rem;
    }

    @media (max-width: 980px) {
        .display-top,
        .display-grid {
            grid-template-columns: 1fr;
        }

        .display-meta {
            text-align: left;
        }

        .timer-controls {
            justify-content: flex-start;
        }

        .pairing-row {
            grid-template-columns: 64px minmax(0, 1fr);
        }

        .pairing-row .versus,
        .pairing-row .result {
            display: none;
        }
    }
</style>

<section class="display-shell">
    <div class="display-top">
        <div>
            <div class="display-kicker">Mate Tournaments Live</div>
            <h1 class="display-title" id="display-title">Live Display</h1>
        </div>
        <div class="display-meta">
            <div class="display-timer" id="round-timer">20:00</div>
            <div class="timer-controls">
                <button type="button" id="timer-start">Start</button>
                <button type="button" id="timer-pause">Pause</button>
                <button type="button" id="timer-reset">Reset</button>
            </div>
        </div>
    </div>

    <div class="display-grid">
        <section class="display-panel">
            <div class="display-panel-header">
                <h2>Standings</h2>
                <span class="display-note" id="display-round">-</span>
            </div>
            <div class="display-list" id="standings-list"></div>
        </section>

        <section class="display-panel">
            <div class="display-panel-header">
                <h2>Pairings</h2>
                <span class="display-note">Current round</span>
            </div>
            <div class="display-list" id="pairings-list"></div>
        </section>
    </div>
</section>

<script>
    const eventId = <?= $eventId ?>;
    const defaultRoundSeconds = <?= $roundMinutes * 60 ?>;
    const timerKey = `mate-live-timer-${eventId}`;
    const initialPayload = <?= $jsonPayload ?: '{}' ?>;
    const endpoint = `index.php?page=live-display-data&event=${eventId}`;
    let timerSeconds = defaultRoundSeconds;
    let timerRunning = false;
    let timerInterval = null;

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    function renderDisplay(data) {
        document.getElementById('display-title').textContent = data.event?.title || 'Live Display';
        document.getElementById('display-round').textContent = data.tournament?.round_name || 'Not started';

        const standings = data.standings || [];
        const pairings = data.pairings || [];

        document.getElementById('standings-list').innerHTML = standings.length
            ? standings.map((row) => `
                <div class="standing-row">
                    <div class="rank">${escapeHtml(row.rank)}</div>
                    <div class="display-name">${escapeHtml(row.name)}</div>
                    <div class="score">${escapeHtml(row.score)}</div>
                </div>
            `).join('')
            : '<div class="empty-display">No standings yet.</div>';

        document.getElementById('pairings-list').innerHTML = pairings.length
            ? pairings.map((row) => `
                <div class="pairing-row">
                    <div class="board">B${escapeHtml(row.board)}</div>
                    <div class="display-name">${escapeHtml(row.white)}</div>
                    <div class="versus">vs</div>
                    <div class="display-name">${escapeHtml(row.black)}</div>
                    <div class="result">${escapeHtml(row.result)}</div>
                </div>
            `).join('')
            : '<div class="empty-display">No pairings generated yet.</div>';
    }

    function loadTimerState() {
        try {
            const saved = JSON.parse(localStorage.getItem(timerKey) || '{}');

            if (Number.isInteger(saved.seconds)) {
                timerSeconds = saved.seconds;
            }
        } catch (error) {
            timerSeconds = defaultRoundSeconds;
        }
    }

    function saveTimerState() {
        localStorage.setItem(timerKey, JSON.stringify({
            seconds: timerSeconds
        }));
    }

    function renderTimer() {
        const minutes = Math.floor(timerSeconds / 60);
        const seconds = timerSeconds % 60;
        document.getElementById('round-timer').textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }

    function startTimer() {
        if (timerRunning) {
            return;
        }

        timerRunning = true;
        timerInterval = setInterval(() => {
            timerSeconds = Math.max(0, timerSeconds - 1);
            saveTimerState();
            renderTimer();

            if (timerSeconds === 0) {
                pauseTimer();
            }
        }, 1000);
    }

    function pauseTimer() {
        timerRunning = false;

        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
    }

    function resetTimer() {
        pauseTimer();
        timerSeconds = defaultRoundSeconds;
        saveTimerState();
        renderTimer();
    }

    async function refreshDisplay() {
        try {
            const response = await fetch(endpoint, { cache: 'no-store' });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();

            if (payload.ok) {
                renderDisplay(payload.data);
            }
        } catch (error) {
            return;
        }
    }

    document.getElementById('timer-start').addEventListener('click', startTimer);
    document.getElementById('timer-pause').addEventListener('click', pauseTimer);
    document.getElementById('timer-reset').addEventListener('click', resetTimer);

    loadTimerState();
    renderTimer();
    renderDisplay(initialPayload);
    setInterval(refreshDisplay, 5000);
</script>
