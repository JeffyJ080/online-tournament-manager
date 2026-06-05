CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive', 'banned') NOT NULL DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_users_role
        FOREIGN KEY (role_id) REFERENCES roles(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);

CREATE TABLE players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    real_name VARCHAR(120) NOT NULL,
    display_name VARCHAR(80) NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(120) NULL,
    rating_category ENUM('beginner', 'casual', 'standard') NOT NULL DEFAULT 'beginner',
    starting_rating INT NOT NULL DEFAULT 800,
    current_rating INT NOT NULL DEFAULT 800,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_players_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NULL,
    entity_id INT NULL,
    description TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_audit_logs_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

CREATE TABLE venues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL UNIQUE,
    address VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    contact_person VARCHAR(120) NULL,
    contact_email VARCHAR(120) NULL,
    contact_phone VARCHAR(30) NULL,
    venue_manager_user_id INT NULL,
    food_deal_description TEXT NULL,
    notes TEXT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_venues_manager_user
        FOREIGN KEY (venue_manager_user_id) REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venue_id INT NOT NULL,
    title VARCHAR(160) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    description TEXT NULL,
    event_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NULL,
    entry_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    prize_info VARCHAR(255) NULL,
    format ENUM('knockout', 'swiss', 'round_robin', 'weekly_points', 'team', 'doubles') NOT NULL DEFAULT 'knockout',
    max_players INT NOT NULL DEFAULT 32,
    registration_status ENUM('open', 'closed', 'full', 'cancelled') NOT NULL DEFAULT 'open',
    event_status ENUM('draft', 'published', 'running', 'completed', 'cancelled') NOT NULL DEFAULT 'draft',
    poster_path VARCHAR(255) NULL,
    notes TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_events_venue
        FOREIGN KEY (venue_id) REFERENCES venues(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_events_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

CREATE TABLE event_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    player_id INT NULL,
    user_id INT NULL,

    full_name VARCHAR(120) NOT NULL,
    display_name VARCHAR(80) NULL,
    email VARCHAR(120) NOT NULL,
    phone VARCHAR(30) NULL,

    rating_category ENUM('beginner', 'casual', 'standard') NOT NULL DEFAULT 'beginner',

    registration_status ENUM(
        'pending',
        'confirmed',
        'cancelled',
        'waitlisted',
        'checked_in',
        'no_show'
    ) NOT NULL DEFAULT 'pending',

    payment_status ENUM(
        'unpaid',
        'paid_cash',
        'paid_eft',
        'proof_uploaded',
        'verified',
        'refunded',
        'comped'
    ) NOT NULL DEFAULT 'unpaid',

    payment_method ENUM('cash', 'eft', 'comped', 'other') NOT NULL DEFAULT 'cash',

    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    checked_in_at DATETIME NULL,
    notes TEXT NULL,

    CONSTRAINT fk_event_registrations_event
        FOREIGN KEY (event_id) REFERENCES events(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_event_registrations_player
        FOREIGN KEY (player_id) REFERENCES players(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    CONSTRAINT fk_event_registrations_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

CREATE TABLE event_series (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venue_id INT NOT NULL,
    title VARCHAR(160) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    description TEXT NULL,

    recurrence_type ENUM('none', 'weekly', 'monthly', 'custom') NOT NULL DEFAULT 'none',

    -- For weekly events: 1 = Monday, 2 = Tuesday, 3 = Wednesday, etc.
    day_of_week TINYINT NULL,

    -- For monthly events: example 25 for fixed date monthly.
    day_of_month TINYINT NULL,

    -- For events like "last Thursday of the month"
    monthly_week ENUM('first', 'second', 'third', 'fourth', 'last') NULL,
    monthly_weekday TINYINT NULL,

    default_start_time TIME NULL,
    default_end_time TIME NULL,
    default_entry_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    default_prize_info VARCHAR(255) NULL,
    default_format ENUM('knockout', 'swiss', 'round_robin', 'weekly_points', 'team', 'doubles') NOT NULL DEFAULT 'knockout',
    default_max_players INT NOT NULL DEFAULT 32,

    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_event_series_venue
        FOREIGN KEY (venue_id) REFERENCES venues(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);

ALTER TABLE events
ADD COLUMN series_id INT NULL AFTER id,
ADD CONSTRAINT fk_events_series
    FOREIGN KEY (series_id) REFERENCES event_series(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE;

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_id INT NOT NULL,
    event_id INT NOT NULL,
    user_id INT NULL,
    player_id INT NULL,

    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_method ENUM('cash', 'eft', 'comped', 'other') NOT NULL DEFAULT 'cash',
    payment_status ENUM(
        'unpaid',
        'paid_cash',
        'paid_eft',
        'proof_uploaded',
        'verified',
        'refunded',
        'comped'
    ) NOT NULL DEFAULT 'unpaid',

    verified_by INT NULL,
    verified_at DATETIME NULL,
    notes TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_payments_registration
        FOREIGN KEY (registration_id) REFERENCES event_registrations(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_payments_event
        FOREIGN KEY (event_id) REFERENCES events(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_payments_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    CONSTRAINT fk_payments_player
        FOREIGN KEY (player_id) REFERENCES players(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    CONSTRAINT fk_payments_verified_by
        FOREIGN KEY (verified_by) REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);