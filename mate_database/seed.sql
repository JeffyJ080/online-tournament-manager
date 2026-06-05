INSERT INTO roles (name, description) VALUES
('super_admin', 'Owner-level full access'),
('admin', 'Manage events, users, venues, payments, tournaments and reports'),
('host', 'Run assigned events and enter results'),
('venue_manager', 'View venue events and stats'),
('player', 'Register for events and view own profile');

INSERT INTO venues (
    name,
    slug,
    city,
    food_deal_description,
    notes,
    status
) VALUES
(
    'Sinkhuis',
    'sinkhuis',
    'Pretoria',
    'Weekly chess night model. Food/drink deal to be confirmed per event.',
    'Core weekly Mate Tournaments venue.',
    'active'
),
(
    'Toni''s Pizza',
    'tonis-pizza',
    'Rietfontein',
    'Monthly knockout chess event venue.',
    'Known for Toni''s Knockout Chess.',
    'active'
);

INSERT INTO events (
    venue_id,
    title,
    slug,
    description,
    event_date,
    start_time,
    entry_fee,
    prize_info,
    format,
    max_players,
    registration_status,
    event_status,
    notes
)
SELECT
    id,
    'Sinkhuis Weekly Chess Night',
    'sinkhuis-weekly-chess-night',
    'Weekly chess night with leaderboard points and a year-end prize pot.',
    '2026-06-10',
    '18:00:00',
    100.00,
    'Nightly prizes plus year-end prize pot',
    'weekly_points',
    32,
    'open',
    'published',
    'Starter seed event.'
FROM venues
WHERE slug = 'sinkhuis';

INSERT INTO events (
    venue_id,
    title,
    slug,
    description,
    event_date,
    start_time,
    entry_fee,
    prize_info,
    format,
    max_players,
    registration_status,
    event_status,
    notes
)
SELECT
    id,
    'Toni''s Knockout Chess',
    'tonis-knockout-chess',
    'Monthly knockout chess event at Toni''s Pizza.',
    '2026-06-25',
    '18:30:00',
    150.00,
    'R600 up for grabs',
    'knockout',
    40,
    'open',
    'published',
    'Starter seed event.'
FROM venues
WHERE slug = 'tonis-pizza';

INSERT INTO event_series (
    venue_id,
    title,
    slug,
    description,
    recurrence_type,
    day_of_week,
    default_start_time,
    default_entry_fee,
    default_prize_info,
    default_format,
    default_max_players,
    status
)
SELECT
    id,
    'Sinkhuis Weekly Chess Night',
    'sinkhuis-weekly-chess-night',
    'Weekly chess night with leaderboard points and year-end prize pot.',
    'weekly',
    3,
    '18:00:00',
    100.00,
    'Nightly prizes plus year-end prize pot',
    'weekly_points',
    32,
    'active'
FROM venues
WHERE slug = 'sinkhuis';

INSERT INTO event_series (
    venue_id,
    title,
    slug,
    description,
    recurrence_type,
    monthly_week,
    monthly_weekday,
    default_start_time,
    default_entry_fee,
    default_prize_info,
    default_format,
    default_max_players,
    status
)
SELECT
    id,
    'Toni''s Knockout Chess',
    'tonis-knockout-chess',
    'Monthly knockout chess event at Toni''s Pizza.',
    'monthly',
    'last',
    4,
    '18:30:00',
    150.00,
    'R600 up for grabs',
    'knockout',
    40,
    'active'
FROM venues
WHERE slug = 'tonis-pizza';