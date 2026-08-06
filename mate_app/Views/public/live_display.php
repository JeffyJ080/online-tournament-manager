<?php
$eventId = (int) ($eventId ?? 0);
$payload = $payload ?? [];
$jsonPayload = json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$roundMinutes = max(1, min(240, (int) ($_GET['minutes'] ?? 20)));
$slideSeconds = max(6, min(60, (int) ($_GET['slides'] ?? 12)));
?>

<style>
    header,
    footer {
        display: none;
    }

    main {
        max-width: none;
        height: 100vh;
        min-height: 100vh;
        padding: 1rem;
        overflow: hidden;
    }

    body {
        overflow: hidden;
        background: #050812;
    }

    body::after {
        position: fixed;
        z-index: 1000;
        inset: 0;
        border: 14px solid transparent;
        background: rgba(216, 45, 45, 0);
        content: '';
        pointer-events: none;
    }

    body.timer-warning::after {
        animation: timer-warning-flash 1.1s steps(1, end) 3;
    }

    @keyframes timer-warning-flash {
        0%, 44% {
            border-color: rgba(255, 76, 76, 0.9);
            background: rgba(210, 28, 28, 0.16);
        }

        45%, 100% {
            border-color: rgba(255, 76, 76, 0.18);
            background: rgba(210, 28, 28, 0);
        }
    }

    .display-shell {
        height: calc(100vh - 2rem);
        min-height: calc(100vh - 2rem);
        display: grid;
        grid-template-rows: auto minmax(0, 1fr) auto;
        gap: 0.75rem;
    }

    .display-top {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 1rem;
        align-items: center;
        padding: 0.85rem 1rem;
        border: 1px solid rgba(216, 226, 240, 0.12);
        border-radius: 8px;
        background: rgba(14, 21, 38, 0.96);
    }

    .display-kicker,
    .slide-kicker {
        color: var(--gold-bright);
        font-size: 0.82rem;
        font-weight: 900;
        letter-spacing: 0.1em;
        text-transform: uppercase;
    }

    .display-title {
        margin: 0.2rem 0 0;
        overflow: hidden;
        max-width: 40ch;
        font-size: clamp(1.7rem, 2.9vw, 3.25rem);
        line-height: 1.08;
        letter-spacing: 0;
        text-wrap: balance;
    }

    .display-meta {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .display-timer {
        min-width: 5.2ch;
        color: var(--gold-bright);
        font-size: clamp(2.2rem, 4.4vw, 4.6rem);
        font-variant-numeric: tabular-nums;
        font-weight: 900;
        line-height: 1;
        text-align: right;
    }

    .display-timer.is-low {
        color: #ff6565;
    }

    .carousel-controls {
        display: flex;
        gap: 0.4rem;
    }

    .carousel-controls button {
        min-height: 34px;
        padding: 0.4rem 0.7rem;
        border: 1px solid rgba(240, 180, 42, 0.42);
        border-radius: 6px;
        background: rgba(240, 180, 42, 0.1);
        color: var(--gold-bright);
        font-weight: 800;
        cursor: pointer;
    }

    .carousel-controls button:disabled {
        cursor: default;
        opacity: 0.38;
    }

    .carousel-viewport {
        min-height: 0;
        overflow: hidden;
        border: 1px solid rgba(216, 226, 240, 0.12);
        border-radius: 8px;
        background: rgba(10, 16, 30, 0.96);
    }

    .slide-track {
        height: 100%;
        display: flex;
        transition: transform 850ms cubic-bezier(0.22, 0.75, 0.25, 1);
        will-change: transform;
    }

    .display-slide {
        min-width: 100%;
        height: 100%;
        flex: 0 0 100%;
        padding: clamp(1rem, 2vw, 2rem);
        overflow: hidden;
    }

    .slide-header {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: end;
        margin-bottom: 1rem;
    }

    .slide-header h2,
    .spotlight-title {
        margin: 0.18rem 0 0;
        font-size: clamp(2rem, 4vw, 4.4rem);
        line-height: 1;
        letter-spacing: 0;
    }

    .display-note {
        color: var(--muted);
        font-size: clamp(0.9rem, 1.2vw, 1.15rem);
        text-align: right;
    }

    .round-grid {
        height: calc(100% - 5rem);
        display: grid;
        grid-template-columns: minmax(310px, 0.72fr) minmax(0, 1.28fr);
        gap: 1rem;
        min-height: 0;
    }

    .display-panel {
        min-height: 0;
        overflow: hidden;
        border: 1px solid rgba(216, 226, 240, 0.1);
        border-radius: 8px;
        background: rgba(18, 27, 47, 0.82);
    }

    .standings-panel {
        display: grid;
        grid-template-rows: auto auto minmax(0, 1fr);
    }

    .display-panel-header {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: center;
        padding: 0.75rem 0.9rem;
        border-bottom: 1px solid rgba(216, 226, 240, 0.08);
    }

    .display-panel h3 {
        margin: 0;
        font-size: clamp(1.2rem, 1.8vw, 1.8rem);
    }

    .display-list {
        display: grid;
    }

    .standings-top {
        position: relative;
        z-index: 1;
        border-bottom: 2px solid rgba(240, 180, 42, 0.28);
        background: rgba(18, 27, 47, 0.98);
        box-shadow: 0 10px 18px rgba(5, 8, 18, 0.24);
    }

    .standings-scroll {
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
        scrollbar-width: none;
        scroll-behavior: smooth;
    }

    .standings-scroll::-webkit-scrollbar {
        display: none;
    }

    .standings-scroll.is-empty {
        display: none;
    }

    .standing-row,
    .leader-row,
    .result-row {
        display: grid;
        gap: 0.75rem;
        align-items: center;
        border-bottom: 1px solid rgba(216, 226, 240, 0.07);
    }

    .standing-row {
        grid-template-columns: 54px minmax(0, 1fr) 80px;
        padding: clamp(0.55rem, 1.1vh, 0.9rem) 0.9rem;
    }

    .pairings-list {
        height: calc(100% - 3.6rem);
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        align-content: start;
        gap: 0.55rem;
        padding: 0.7rem;
        overflow: hidden;
    }

    .match-tile {
        min-width: 0;
        display: grid;
        grid-template-columns: 58px minmax(0, 1fr) auto;
        gap: 0.65rem;
        align-items: center;
        padding: 0.72rem;
        border: 1px solid rgba(216, 226, 240, 0.08);
        border-radius: 6px;
        background: rgba(5, 9, 19, 0.45);
    }

    .match-players {
        min-width: 0;
        display: grid;
        gap: 0.18rem;
    }

    .match-player,
    .display-name {
        overflow: hidden;
        color: var(--text);
        font-size: clamp(1rem, 1.35vw, 1.45rem);
        font-weight: 800;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .match-player + .match-player {
        color: #c1cad8;
    }

    .rank,
    .board,
    .score,
    .leader-points {
        color: var(--gold-bright);
        font-weight: 900;
    }

    .rank,
    .board {
        font-size: clamp(1.25rem, 1.7vw, 1.8rem);
    }

    .score,
    .leader-points {
        font-size: clamp(1.35rem, 2vw, 2rem);
        text-align: right;
    }

    .result-chip {
        max-width: 9rem;
        overflow: hidden;
        color: #dce4ef;
        font-size: 0.78rem;
        font-weight: 800;
        text-align: right;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .leaderboard-stage {
        width: min(1080px, 100%);
        margin: 0 auto;
    }

    .leaderboard-stage .leader-row {
        grid-template-columns: 76px minmax(0, 1fr) minmax(100px, auto);
        padding: clamp(0.72rem, 1.5vh, 1.15rem) 1rem;
    }

    .leaderboard-stage .rank {
        font-size: clamp(1.8rem, 3vw, 3rem);
    }

    .leaderboard-stage .display-name {
        font-size: clamp(1.4rem, 2.4vw, 2.55rem);
    }

    .leader-detail {
        color: var(--muted);
        font-size: 0.92rem;
        text-align: right;
    }

    .spotlight-layout {
        height: 100%;
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(280px, 0.72fr);
        gap: clamp(1.5rem, 4vw, 4rem);
        align-items: center;
    }

    .spotlight-layout.no-poster {
        grid-template-columns: minmax(0, 1fr);
    }

    .spotlight-layout.no-poster .spotlight-copy {
        max-width: 1180px;
    }

    .spotlight-layout.no-poster #spotlight-visual {
        display: none;
    }

    .spotlight-copy {
        max-width: 900px;
    }

    .spotlight-description {
        max-width: 760px;
        margin: 1.2rem 0 1.5rem;
        color: #d6deeb;
        font-size: clamp(1.15rem, 1.9vw, 1.75rem);
        line-height: 1.5;
    }

    .spotlight-facts {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.75rem;
    }

    .spotlight-fact,
    .progress-stat {
        padding: 0.9rem;
        border: 1px solid rgba(216, 226, 240, 0.11);
        border-radius: 8px;
        background: rgba(18, 27, 47, 0.74);
    }

    .spotlight-fact span,
    .progress-stat span {
        display: block;
        color: var(--muted);
        font-size: 0.8rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .spotlight-fact strong,
    .progress-stat strong {
        display: block;
        margin-top: 0.35rem;
        color: var(--text);
        font-size: clamp(1.05rem, 1.6vw, 1.5rem);
    }

    .event-poster {
        width: 100%;
        max-height: 66vh;
        aspect-ratio: 4 / 5;
        border: 1px solid rgba(216, 226, 240, 0.14);
        border-radius: 8px;
        object-fit: cover;
    }

    .results-stage {
        width: min(1180px, 100%);
        margin: 0 auto;
    }

    .result-row {
        grid-template-columns: 120px minmax(0, 1fr) 70px minmax(0, 1fr) 150px;
        padding: clamp(0.7rem, 1.35vh, 1rem) 1rem;
    }

    .result-round {
        color: var(--gold-bright);
        font-weight: 900;
    }

    .result-versus {
        color: var(--muted);
        font-weight: 900;
        text-align: center;
    }

    .progress-stage {
        width: min(1100px, 100%);
        margin: 0 auto;
    }

    .progress-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.8rem;
        margin-bottom: 1.5rem;
    }

    .progress-stat strong {
        color: var(--gold-bright);
        font-size: clamp(2rem, 4vw, 4rem);
    }

    .progress-track {
        height: 30px;
        overflow: hidden;
        border: 1px solid rgba(216, 226, 240, 0.12);
        border-radius: 6px;
        background: rgba(5, 9, 19, 0.65);
    }

    .progress-fill {
        width: 0;
        height: 100%;
        background: var(--gold-bright);
        transition: width 500ms ease;
    }

    .progress-caption {
        display: flex;
        justify-content: space-between;
        margin-top: 0.7rem;
        color: var(--muted);
        font-size: 1rem;
    }

    .empty-display {
        padding: 1.5rem;
        color: var(--muted);
        font-size: 1.1rem;
    }

    .carousel-bar {
        min-height: 42px;
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        gap: 1rem;
        align-items: center;
        padding: 0.35rem 0.5rem;
    }

    .slide-status {
        min-width: 7.5rem;
        color: var(--muted);
        font-size: 0.9rem;
        font-weight: 800;
    }

    .slide-dots {
        display: flex;
        justify-content: center;
        gap: 0.55rem;
    }

    .slide-dot {
        width: 10px;
        height: 10px;
        padding: 0;
        border: 0;
        border-radius: 50%;
        background: rgba(216, 226, 240, 0.28);
        cursor: pointer;
    }

    .slide-dot.is-active {
        background: var(--gold-bright);
    }

    @media (max-width: 980px) {
        body {
            overflow: auto;
        }

        .display-shell {
            height: auto;
            min-height: calc(100vh - 2rem);
        }

        main {
            height: auto;
            overflow: visible;
        }

        .display-top,
        .round-grid,
        .spotlight-layout {
            grid-template-columns: 1fr;
        }

        .display-meta {
            justify-content: space-between;
        }

        .display-slide {
            overflow-y: auto;
        }

        .round-grid {
            height: auto;
        }

        .standings-panel {
            display: block;
        }

        .standings-scroll {
            max-height: 18rem;
        }

        .pairings-list {
            height: auto;
            grid-template-columns: 1fr;
        }

        .spotlight-layout {
            align-items: start;
        }

        .event-poster {
            display: none;
        }

        .result-row {
            grid-template-columns: 88px minmax(0, 1fr) 100px;
        }

        .result-row .result-versus,
        .result-row .result-black {
            display: none;
        }
    }

    @media (max-width: 620px) {
        main {
            padding: 0.55rem;
        }

        .display-shell {
            min-height: calc(100vh - 1.1rem);
        }

        .display-title {
            white-space: normal;
        }

        .display-meta {
            align-items: flex-end;
        }

        .carousel-controls button {
            padding-inline: 0.5rem;
        }

        .spotlight-facts,
        .progress-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .leaderboard-stage .leader-row {
            grid-template-columns: 48px minmax(0, 1fr) 74px;
        }

        .carousel-bar {
            grid-template-columns: 1fr auto;
        }

        .slide-dots {
            display: none;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        body.timer-warning::after {
            animation: none;
            border-color: rgba(255, 76, 76, 0.82);
            background: rgba(210, 28, 28, 0.08);
        }

        .slide-track,
        .progress-fill,
        .standings-scroll {
            transition: none;
            scroll-behavior: auto;
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
        </div>
    </div>

    <div class="carousel-viewport">
        <div class="slide-track" id="slide-track">
            <section class="display-slide" data-slide="Current round">
                <div class="slide-header">
                    <div>
                        <div class="slide-kicker">Live play</div>
                        <h2 id="display-round">Current round</h2>
                    </div>
                    <span class="display-note" id="display-updated">Updated now</span>
                </div>
                <div class="round-grid">
                    <section class="display-panel standings-panel">
                        <div class="display-panel-header">
                            <h3>Standings</h3>
                            <!-- <span class="display-note">Top 3 fixed</span> -->
                        </div>
                        <div class="display-list standings-top" id="standings-top"></div>
                        <div class="standings-scroll" id="standings-scroll" aria-label="Standings from fourth place onward">
                            <div class="display-list" id="standings-rest"></div>
                        </div>
                    </section>
                    <section class="display-panel">
                        <div class="display-panel-header">
                            <h3>Pairings</h3>
                            <span class="display-note">Current boards</span>
                        </div>
                        <div class="pairings-list" id="pairings-list"></div>
                    </section>
                </div>
            </section>

            <section class="display-slide" data-slide="Tournament top 5">
                <div class="slide-header">
                    <div>
                        <div class="slide-kicker">Tournament leaderboard</div>
                        <h2>Top 5 right now</h2>
                    </div>
                    <span class="display-note" id="tournament-round-note"></span>
                </div>
                <div class="leaderboard-stage display-panel" id="tournament-leaders"></div>
            </section>

            <section class="display-slide" data-slide="Overall top 5">
                <div class="slide-header">
                    <div>
                        <div class="slide-kicker">Overall leaderboard</div>
                        <h2 id="overall-title">Top 5 overall</h2>
                    </div>
                    <span class="display-note">Season points</span>
                </div>
                <div class="leaderboard-stage display-panel" id="overall-leaders"></div>
            </section>

            <section class="display-slide" data-slide="Event spotlight">
                <div class="spotlight-layout">
                    <div class="spotlight-copy">
                        <div class="slide-kicker">Event spotlight</div>
                        <h2 class="spotlight-title" id="spotlight-title">Mate Tournaments</h2>
                        <p class="spotlight-description" id="spotlight-description"></p>
                        <div class="spotlight-facts" id="spotlight-facts"></div>
                    </div>
                    <div id="spotlight-visual"></div>
                </div>
            </section>

            <section class="display-slide" data-slide="Recent results">
                <div class="slide-header">
                    <div>
                        <div class="slide-kicker">Just completed</div>
                        <h2>Recent results</h2>
                    </div>
                    <span class="display-note">Latest reported boards</span>
                </div>
                <div class="results-stage display-panel" id="recent-results"></div>
            </section>

            <section class="display-slide" data-slide="Round progress">
                <div class="slide-header">
                    <div>
                        <div class="slide-kicker">Tournament pulse</div>
                        <h2>Round progress</h2>
                    </div>
                    <span class="display-note" id="progress-round-note"></span>
                </div>
                <div class="progress-stage">
                    <div class="progress-stats" id="progress-stats"></div>
                    <div class="progress-track" aria-label="Round completion">
                        <div class="progress-fill" id="progress-fill"></div>
                    </div>
                    <div class="progress-caption">
                        <span id="progress-caption">0 of 0 boards complete</span>
                        <strong id="progress-percent">0%</strong>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="carousel-bar">
        <div class="slide-status" id="slide-status">Waiting for round</div>
        <div class="slide-dots" id="slide-dots"></div>
        <div class="carousel-controls">
            <button type="button" id="slide-previous" title="Previous slide" aria-label="Previous slide">&larr;</button>
            <button type="button" id="slide-toggle" title="Pause slideshow">Pause</button>
            <button type="button" id="slide-next" title="Next slide" aria-label="Next slide">&rarr;</button>
        </div>
    </div>
</section>

<script>
    const eventId = <?= $eventId ?>;
    const defaultRoundSeconds = <?= $roundMinutes * 60 ?>;
    const slideDuration = <?= $slideSeconds * 1000 ?>;
    const warningSeconds = 3 * 60;
    const initialPayload = <?= $jsonPayload ?: '{}' ?>;
    const endpoint = `index.php?page=live-display-data&event=${eventId}`;
    const slides = Array.from(document.querySelectorAll('.display-slide'));
    let timerSeconds = defaultRoundSeconds;
    let timerStatus = 'ready';
    let timerInterval = null;
    let currentSlide = 0;
    let carouselEnabled = false;
    let carouselPaused = false;
    let carouselInterval = null;
    let standingsScrollInterval = null;

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    function renderRankings(targetId, rows, valueKey, valueSuffix = '') {
        const target = document.getElementById(targetId);
        target.innerHTML = rows.length
            ? rows.slice(0, 5).map((row) => `
                <div class="leader-row">
                    <div class="rank">${escapeHtml(row.rank)}</div>
                    <div class="display-name">${escapeHtml(row.name)}</div>
                    <div>
                        <div class="leader-points">${escapeHtml(row[valueKey])}${valueSuffix}</div>
                        ${row.events ? `<div class="leader-detail">${escapeHtml(row.events)} events</div>` : ''}
                    </div>
                </div>
            `).join('')
            : '<div class="empty-display">Leaderboard results will appear here.</div>';
    }

    function standingRows(rows) {
        return rows.map((row) => `
            <div class="standing-row">
                <div class="rank">${escapeHtml(row.rank)}</div>
                <div class="display-name">${escapeHtml(row.name)}</div>
                <div class="score">${escapeHtml(row.score)}</div>
            </div>
        `).join('');
    }

    function renderStandings(standings) {
        const topStandings = standings.slice(0, 3);
        const remainingStandings = standings.slice(3);
        const scrollViewport = document.getElementById('standings-scroll');
        const previousScrollTop = scrollViewport.scrollTop;

        document.getElementById('standings-top').innerHTML = topStandings.length
            ? standingRows(topStandings)
            : '<div class="empty-display">No standings yet.</div>';
        document.getElementById('standings-rest').innerHTML = standingRows(remainingStandings);
        scrollViewport.classList.toggle('is-empty', remainingStandings.length === 0);
        const maximumScroll = Math.max(0, scrollViewport.scrollHeight - scrollViewport.clientHeight);
        scrollViewport.scrollTop = Math.min(previousScrollTop, maximumScroll);
    }

    function startStandingsAutoScroll() {
        if (standingsScrollInterval) {
            return;
        }

        standingsScrollInterval = setInterval(() => {
            if (currentSlide !== 0 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                return;
            }

            const viewport = document.getElementById('standings-scroll');
            const firstRow = viewport.querySelector('.standing-row');
            const maximumScroll = viewport.scrollHeight - viewport.clientHeight;

            if (!firstRow || maximumScroll <= 1) {
                viewport.scrollTop = 0;
                return;
            }

            if (viewport.scrollTop >= maximumScroll - 2) {
                viewport.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }

            viewport.scrollBy({ top: firstRow.getBoundingClientRect().height, behavior: 'smooth' });
        }, 2500);
    }

    function renderDisplay(data) {
        document.getElementById('display-title').textContent = data.event?.title || 'Live Display';
        document.getElementById('display-round').textContent = data.tournament?.round_name || 'Not started';
        document.getElementById('display-updated').textContent = `Updated ${data.updated_at || 'now'}`;
        document.getElementById('tournament-round-note').textContent = data.tournament?.round_name || '';

        const standings = data.standings || [];
        const pairings = data.pairings || [];

        renderStandings(standings);

        document.getElementById('pairings-list').innerHTML = pairings.length
            ? pairings.map((row) => `
                <div class="match-tile">
                    <div class="board">B${escapeHtml(row.board)}</div>
                    <div class="match-players">
                        <div class="match-player">${escapeHtml(row.white)}</div>
                        <div class="match-player">${escapeHtml(row.black)}</div>
                    </div>
                    <div class="result-chip">${escapeHtml(row.result)}</div>
                </div>
            `).join('')
            : '<div class="empty-display">No pairings generated yet.</div>';

        renderRankings('tournament-leaders', standings, 'score');

        const overall = data.overall_leaderboard || {};
        document.getElementById('overall-title').textContent = overall.name || 'Top 5 overall';
        renderRankings('overall-leaders', overall.leaders || [], 'points', ' pts');

        renderSpotlight(data.event || {});
        renderRecentResults(data.recent_results || []);
        renderProgress(data);
        syncTimerState(data.timer || {});
        updateCarouselState(data);
    }

    function renderSpotlight(event) {
        const location = [event.venue, event.city].filter(Boolean).join(', ');
        const description = event.description || `${event.title || 'Tonight\'s tournament'} brings the Mate community together for live competitive play.`;
        document.getElementById('spotlight-title').textContent = event.title || 'Mate Tournaments';
        document.getElementById('spotlight-description').textContent = description;
        document.getElementById('spotlight-facts').innerHTML = [
            ['Venue', location || '-'],
            ['Format', event.format || '-'],
            ['Prize', event.prize || 'Event honours'],
            ['Date', event.date || '-'],
            ['Start', event.time || '-'],
            ['Players', event.capacity ? `${event.players || 0} / ${event.capacity}` : String(event.players || 0)]
        ].map(([label, value]) => `
            <div class="spotlight-fact">
                <span>${escapeHtml(label)}</span>
                <strong>${escapeHtml(value)}</strong>
            </div>
        `).join('');

        const visual = document.getElementById('spotlight-visual');
        const layout = visual.closest('.spotlight-layout');
        layout.classList.toggle('no-poster', !event.poster);
        visual.innerHTML = event.poster
            ? `<img class="event-poster" src="${escapeHtml(event.poster)}" alt="${escapeHtml(event.title || 'Event poster')}">`
            : '';
    }

    function renderRecentResults(results) {
        const target = document.getElementById('recent-results');
        target.innerHTML = results.length
            ? results.map((row) => `
                <div class="result-row">
                    <div class="result-round">${escapeHtml(row.round)} / B${escapeHtml(row.board)}</div>
                    <div class="display-name">${escapeHtml(row.white)}</div>
                    <div class="result-versus">vs</div>
                    <div class="display-name result-black">${escapeHtml(row.black)}</div>
                    <div class="result-chip">${escapeHtml(row.result)}</div>
                </div>
            `).join('')
            : '<div class="empty-display">Completed boards will appear here as results are reported.</div>';
    }

    function renderProgress(data) {
        const stats = data.round_stats || {};
        const total = Number(stats.total || 0);
        const completed = Number(stats.completed || 0);
        const percent = total > 0 ? Math.round((completed / total) * 100) : 0;
        const round = Number(data.tournament?.current_round || 0);
        const totalRounds = Number(data.tournament?.total_rounds || 0);

        document.getElementById('progress-round-note').textContent = data.tournament?.round_name || '';
        document.getElementById('progress-stats').innerHTML = [
            ['Round', totalRounds ? `${round} / ${totalRounds}` : round],
            ['Boards', total],
            ['Complete', completed],
            ['In play', Number(stats.in_play || 0)]
        ].map(([label, value]) => `
            <div class="progress-stat">
                <span>${escapeHtml(label)}</span>
                <strong>${escapeHtml(value)}</strong>
            </div>
        `).join('');
        document.getElementById('progress-fill').style.width = `${percent}%`;
        document.getElementById('progress-caption').textContent = `${completed} of ${total} boards complete`;
        document.getElementById('progress-percent').textContent = `${percent}%`;
    }

    function roundIsComplete(data) {
        const status = String(data.tournament?.round_status || '').toLowerCase();
        const totalBoards = Number(data.round_stats?.total || 0);
        const completedBoards = Number(data.round_stats?.completed || 0);

        return status === 'completed'
            || (totalBoards > 0 && completedBoards >= totalBoards);
    }

    function renderTimer() {
        const minutes = Math.floor(timerSeconds / 60);
        const seconds = timerSeconds % 60;
        const timer = document.getElementById('round-timer');
        const isLow = timerSeconds > 0 && timerSeconds <= warningSeconds;
        timer.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        timer.classList.toggle('is-low', isLow);
        document.body.classList.toggle('timer-warning', isLow);
    }

    function syncTimerState(state) {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }

        timerSeconds = Math.max(0, Number(state.remaining_seconds ?? defaultRoundSeconds));
        timerStatus = String(state.status || 'ready').toLowerCase();
        renderTimer();

        if (timerStatus === 'running' && timerSeconds > 0) {
            timerInterval = setInterval(() => {
                timerSeconds = Math.max(0, timerSeconds - 1);
                renderTimer();

                if (timerSeconds === 0) {
                    clearInterval(timerInterval);
                    timerInterval = null;
                    timerStatus = 'finished';
                }
            }, 1000);
        }
    }

    function buildSlideDots() {
        const dots = document.getElementById('slide-dots');
        dots.innerHTML = slides.map((slide, index) => `
            <button
                type="button"
                class="slide-dot${index === 0 ? ' is-active' : ''}"
                data-slide-index="${index}"
                title="${escapeHtml(slide.dataset.slide)}"
                aria-label="Show ${escapeHtml(slide.dataset.slide)}"
            ></button>
        `).join('');

        dots.querySelectorAll('button').forEach((button) => {
            button.addEventListener('click', () => {
                setSlide(Number(button.dataset.slideIndex));
                restartCarousel();
            });
        });
    }

    function setSlide(index) {
        currentSlide = (index + slides.length) % slides.length;
        document.getElementById('slide-track').style.transform = `translateX(-${currentSlide * 100}%)`;
        document.querySelectorAll('.slide-dot').forEach((dot, dotIndex) => {
            dot.classList.toggle('is-active', dotIndex === currentSlide);
        });
        document.getElementById('slide-status').textContent = carouselEnabled
            ? `${currentSlide + 1} / ${slides.length} - ${slides[currentSlide].dataset.slide}`
            : 'Waiting for round';
    }

    function startCarousel() {
        clearInterval(carouselInterval);

        if (!carouselEnabled || carouselPaused) {
            return;
        }

        carouselInterval = setInterval(() => setSlide(currentSlide + 1), slideDuration);
    }

    function restartCarousel() {
        if (carouselEnabled && !carouselPaused) {
            startCarousel();
        }
    }

    function updateCarouselState(data) {
        const currentRound = Number(data.tournament?.current_round || 0);
        const isComplete = roundIsComplete(data);
        const timerHasStarted = ['running', 'paused', 'finished'].includes(timerStatus);
        const hasStarted = currentRound > 0
            && timerHasStarted
            && !isComplete;
        const wasEnabled = carouselEnabled;
        carouselEnabled = hasStarted;

        ['slide-previous', 'slide-toggle', 'slide-next'].forEach((id) => {
            document.getElementById(id).disabled = !carouselEnabled;
        });

        if (!carouselEnabled) {
            clearInterval(carouselInterval);
            setSlide(0);
            document.getElementById('slide-status').textContent = isComplete
                ? 'Round complete'
                : (currentRound > 0 ? 'Ready for timer' : 'Waiting for round');
        } else if (!wasEnabled) {
            setSlide(0);
            startCarousel();
        } else {
            setSlide(currentSlide);
        }
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

    document.getElementById('slide-previous').addEventListener('click', () => {
        setSlide(currentSlide - 1);
        restartCarousel();
    });
    document.getElementById('slide-next').addEventListener('click', () => {
        setSlide(currentSlide + 1);
        restartCarousel();
    });
    document.getElementById('slide-toggle').addEventListener('click', () => {
        carouselPaused = !carouselPaused;
        document.getElementById('slide-toggle').textContent = carouselPaused ? 'Play' : 'Pause';
        document.getElementById('slide-toggle').title = carouselPaused ? 'Resume slideshow' : 'Pause slideshow';
        startCarousel();
    });

    buildSlideDots();
    renderDisplay(initialPayload);
    startStandingsAutoScroll();
    setInterval(refreshDisplay, 2000);
</script>
