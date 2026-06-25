-- Run this on existing Mate Tournaments databases before going live.
-- The main schema already includes venue_manager_user_id on venues.

INSERT IGNORE INTO roles (name, description) VALUES
('event_manager', 'Manage assigned event operations and tournament execution');

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_password_resets_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

UPDATE events
SET event_date = '2026-06-24',
    registration_status = 'open',
    event_status = 'published'
WHERE slug = 'sinkhuis-weekly-chess-night'
  AND event_date < CURDATE();

CREATE TABLE IF NOT EXISTS leaderboard_seasons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venue_id INT NOT NULL,
    series_id INT NULL,
    name VARCHAR(160) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    starts_on DATE NOT NULL,
    ends_on DATE NOT NULL,
    status ENUM('active', 'inactive', 'archived') NOT NULL DEFAULT 'active',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_leaderboard_seasons_venue
        FOREIGN KEY (venue_id) REFERENCES venues(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_leaderboard_seasons_series
        FOREIGN KEY (series_id) REFERENCES event_series(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS leaderboard_point_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    season_id INT NOT NULL,
    placement_from INT NOT NULL,
    placement_to INT NOT NULL,
    points INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_leaderboard_point_rules_season
        FOREIGN KEY (season_id) REFERENCES leaderboard_seasons(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS leaderboard_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    season_id INT NOT NULL,
    event_id INT NOT NULL,
    tournament_id INT NOT NULL,
    event_registration_id INT NOT NULL,
    player_name VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL,
    placement INT NOT NULL,
    tournament_score DECIMAL(4,1) NOT NULL DEFAULT 0.0,
    points INT NOT NULL DEFAULT 0,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_leaderboard_results_season
        FOREIGN KEY (season_id) REFERENCES leaderboard_seasons(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_leaderboard_results_event
        FOREIGN KEY (event_id) REFERENCES events(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_leaderboard_results_tournament
        FOREIGN KEY (tournament_id) REFERENCES tournaments(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_leaderboard_results_registration
        FOREIGN KEY (event_registration_id) REFERENCES event_registrations(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    UNIQUE KEY unique_leaderboard_tournament_player (
        season_id,
        tournament_id,
        event_registration_id
    )
);

INSERT IGNORE INTO leaderboard_seasons (
    venue_id,
    series_id,
    name,
    slug,
    starts_on,
    ends_on,
    status,
    notes
)
SELECT
    venues.id,
    event_series.id,
    'Sinkhuis 2026 Season',
    'sinkhuis-2026-season',
    '2026-01-01',
    '2026-12-31',
    'active',
    'Calendar-year placement points season.'
FROM venues
INNER JOIN event_series ON event_series.venue_id = venues.id
WHERE venues.slug = 'sinkhuis'
  AND event_series.slug = 'sinkhuis-weekly-chess-night';

DELETE leaderboard_point_rules
FROM leaderboard_point_rules
INNER JOIN leaderboard_seasons
    ON leaderboard_point_rules.season_id = leaderboard_seasons.id
WHERE leaderboard_seasons.slug = 'sinkhuis-2026-season';

INSERT INTO leaderboard_point_rules (season_id, placement_from, placement_to, points)
SELECT id, 1, 1, 10 FROM leaderboard_seasons WHERE slug = 'sinkhuis-2026-season'
UNION ALL
SELECT id, 2, 2, 7 FROM leaderboard_seasons WHERE slug = 'sinkhuis-2026-season'
UNION ALL
SELECT id, 3, 3, 5 FROM leaderboard_seasons WHERE slug = 'sinkhuis-2026-season'
UNION ALL
SELECT id, 4, 4, 3 FROM leaderboard_seasons WHERE slug = 'sinkhuis-2026-season'
UNION ALL
SELECT id, 5, 6, 2 FROM leaderboard_seasons WHERE slug = 'sinkhuis-2026-season'
UNION ALL
SELECT id, 7, 999, 1 FROM leaderboard_seasons WHERE slug = 'sinkhuis-2026-season';
