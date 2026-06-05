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