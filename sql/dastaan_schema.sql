-- =============================================================
-- NAAQ\u015a\u0126 DATABASE SCHEMA
-- =============================================================
-- This file can be imported into phpMyAdmin or run via MySQL CLI.
-- It creates the database, defines all tables, establishes the
-- relationships between them, and inserts demo data for the website.
--
-- Why this structure is useful:
-- 1. A website like NAAQ\u015a\u0126 needs users, admins, categories, services,
--    gallery items, team members, events, bookings, and inquiries.
-- 2. Relationships are stored with foreign keys so MySQL keeps data
--    consistent and prevents orphaned records.
-- 3. Indexes help the application search/filter records faster.
-- =============================================================

-- Create the database if it does not already exist.
-- This is non-destructive and will preserve any existing data already in the database.
CREATE DATABASE IF NOT EXISTS naaqsh
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE naaqsh;

-- NOTE:
-- We intentionally do not drop existing tables or delete existing data.
-- If a table already exists, MySQL will skip creation and the import remains safe.

-- =============================================================
-- 1. USERS
-- =============================================================
-- Stores registered customers who can create events and make bookings.
-- Passwords must never be stored in plaintext. We store a secure bcrypt hash.
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive', 'banned') NOT NULL DEFAULT 'active',
    email_verified_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_status (status),
    KEY idx_users_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- 2. ADMINS
-- =============================================================
-- Stores backend administrators who manage the site content and services.
CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    role ENUM('super_admin', 'manager') NOT NULL DEFAULT 'manager',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admins_username (username),
    KEY idx_admins_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- 3. CATEGORIES
-- =============================================================
-- Each service belongs to a category. This creates the required 1-to-many
-- relationship: one category can contain many services.
CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    description TEXT DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug),
    KEY idx_categories_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- 4. SERVICES
-- =============================================================
-- Services are linked to a category and may be booked in one or more events.
-- Relationship: categories 1-to-many services
CREATE TABLE IF NOT EXISTS services (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    image VARCHAR(255) DEFAULT NULL,
    duration_hours SMALLINT UNSIGNED DEFAULT NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_services_slug (slug),
    KEY idx_services_category (category_id),
    KEY idx_services_active (is_active),
    CONSTRAINT fk_services_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- 5. GALLERY
-- =============================================================
-- Portfolio images showing the studio's past work.
CREATE TABLE IF NOT EXISTS gallery (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    caption TEXT DEFAULT NULL,
    uploaded_by INT UNSIGNED DEFAULT NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_gallery_featured (is_featured),
    KEY idx_gallery_uploaded_by (uploaded_by),
    CONSTRAINT fk_gallery_admin
        FOREIGN KEY (uploaded_by) REFERENCES admins(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- 6. TEAM MEMBERS
-- =============================================================
-- A small directory of staff members and designers.
CREATE TABLE IF NOT EXISTS team_members (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(150) NOT NULL,
    role VARCHAR(100) NOT NULL,
    bio TEXT DEFAULT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_team_members_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- 7. EVENTS
-- =============================================================
-- Events represent a celebration or project created by a user.
-- Relationship: users 1-to-many events
CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    event_date DATE NOT NULL,
    venue VARCHAR(255) DEFAULT NULL,
    guest_count INT UNSIGNED DEFAULT 0,
    budget DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status ENUM('draft', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'draft',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_events_user (user_id),
    KEY idx_events_date (event_date),
    KEY idx_events_status (status),
    CONSTRAINT fk_events_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT chk_events_guest_count CHECK (guest_count >= 0),
    CONSTRAINT chk_events_budget CHECK (budget >= 0.00)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- 8. BOOKINGS
-- =============================================================
-- Bookings join a user, an event, and a service.
-- Relationships:
--   users 1-to-many bookings
--   events 1-to-many bookings
--   services 1-to-many bookings
CREATE TABLE IF NOT EXISTS bookings (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NOT NULL,
    service_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    total_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_bookings_user (user_id),
    KEY idx_bookings_event (event_id),
    KEY idx_bookings_service (service_id),
    KEY idx_bookings_status (status),
    CONSTRAINT fk_bookings_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_bookings_event
        FOREIGN KEY (event_id) REFERENCES events(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_bookings_service
        FOREIGN KEY (service_id) REFERENCES services(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT chk_bookings_quantity CHECK (quantity > 0),
    CONSTRAINT chk_bookings_total_price CHECK (total_price >= 0.00)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- 9. INQUIRIES
-- =============================================================
-- Contact form messages or customer questions.
-- user_id is optional to support both registered and guest inquiries.
CREATE TABLE IF NOT EXISTS inquiries (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED DEFAULT NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    subject VARCHAR(200) DEFAULT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'replied', 'closed') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_inquiries_user (user_id),
    KEY idx_inquiries_email (email),
    KEY idx_inquiries_status (status),
    CONSTRAINT fk_inquiries_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- 10. EVENT INSPIRATIONS
-- =============================================================
-- Customer inspiration photos linked to specific events.
-- Supports multiple photos per event across categories (Venue, Decoration, Dress, etc.).
-- Relationship: events 1-to-many event_inspirations
CREATE TABLE IF NOT EXISTS event_inspirations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    event_id INT UNSIGNED NOT NULL,
    category VARCHAR(100) NOT NULL DEFAULT 'Other',
    image_path VARCHAR(255) NOT NULL,
    caption TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_event_inspirations_event (event_id),
    KEY idx_event_inspirations_category (category),
    CONSTRAINT fk_event_inspirations_event
        FOREIGN KEY (event_id) REFERENCES events(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- DEMO / SEED DATA
-- =============================================================
-- These rows help the project look realistic in a classroom demo.
-- They use example names and placeholder email addresses, not real private data.

INSERT INTO admins (username, password_hash, full_name, role, status)
VALUES
    ('admin', '$2y$10$R1olaeoU5ImBKEoDA8pjKOh1a825M2ZVF548XlU52V.KrKg.gmLaa', 'NAAQ\u015a\u0126 Admin', 'super_admin', 'active')
ON DUPLICATE KEY UPDATE
    password_hash = VALUES(password_hash),
    full_name = VALUES(full_name),
    role = VALUES(role),
    status = VALUES(status);

INSERT INTO users (full_name, email, phone, password_hash, status)
VALUES
    ('Sana Khan', 'sana.khan@example.com', '+923001112233', '$2y$10$7OC5OpHXbCq9a4bB2SMFq.Q617KsancA2/lAydvFcKtGrNSY7OJAC', 'active'),
    ('Areeb Malik', 'areeb.malik@example.com', '+923004445566', '$2y$10$7OC5OpHXbCq9a4bB2SMFq.Q617KsancA2/lAydvFcKtGrNSY7OJAC', 'active')
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name),
    phone = VALUES(phone),
    password_hash = VALUES(password_hash),
    status = VALUES(status);

INSERT INTO categories (name, slug, description, is_active)
VALUES
    ('Wedding Planning', 'wedding-planning', 'Full-service planning for weddings, nikah ceremonies, and family events.', 1),
    ('Event Styling', 'event-styling', 'Decor, floral styling, venue setup, and overall visual direction.', 1),
    ('Photography', 'photography', 'Candid and cinematic photography for intimate and large gatherings.', 1),
    ('Corporate Events', 'corporate-events', 'Professional coordination for launches, conferences, and business events.', 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    is_active = VALUES(is_active);

INSERT INTO services (category_id, title, slug, description, price, image, duration_hours, is_featured, is_active)
VALUES
    (1, 'Wedding Planning Package', 'wedding-planning-package', 'End-to-end planning, guest coordination, timeline management, and vendor support.', 95000.00, 'wedding-planning.jpg', 120, 1, 1),
    (1, 'Nikah & Reception Coordination', 'nikah-reception-coordination', 'Coordination for the ceremony, guest flow, and reception setup with a dedicated planner.', 75000.00, 'nikah-reception.jpg', 96, 0, 1),
    (2, 'Luxury Event Styling', 'luxury-event-styling', 'Signature floral arrangements, candle lighting, and a complete event visual theme.', 68000.00, 'luxury-styling.jpg', 48, 1, 1),
    (2, 'Stage & Backdrop Design', 'stage-backdrop-design', 'Custom stage, entrance, and backdrop designs tailored to the event mood.', 54000.00, 'stage-design.jpg', 36, 0, 1),
    (3, 'Wedding Photography', 'wedding-photography', 'Coverage with editorial storytelling, candid moments, and a curated image gallery.', 89000.00, 'wedding-photography.jpg', 72, 1, 1),
    (4, 'Corporate Event Management', 'corporate-event-management', 'Planning for launches, seminars, and executive hospitality events.', 82000.00, 'corporate-event.jpg', 60, 0, 1)
ON DUPLICATE KEY UPDATE
    category_id = VALUES(category_id),
    description = VALUES(description),
    price = VALUES(price),
    image = VALUES(image),
    duration_hours = VALUES(duration_hours),
    is_featured = VALUES(is_featured),
    is_active = VALUES(is_active);

INSERT INTO gallery (title, image_path, caption, uploaded_by, is_featured)
VALUES
    ('Signature Wedding Setup', 'gallery/wedding-setup.jpg', 'A luxury wedding venue styled with floral installations and warm ambient lighting.', 1, 1),
    ('Bride and Groom Portraits', 'gallery/portrait-session.jpg', 'Editorial portraits captured in natural light for a premium wedding story.', 1, 1),
    ('Corporate Launch Stage', 'gallery/corporate-stage.jpg', 'Modern event design for a product launch and networking reception.', 1, 0),
    ('Luxury Reception Styling', 'gallery/reception-styling.jpg', 'Elegant table styling and guest experience details designed for elegance.', 1, 0)
ON DUPLICATE KEY UPDATE
    caption = VALUES(caption),
    uploaded_by = VALUES(uploaded_by),
    is_featured = VALUES(is_featured);

INSERT INTO team_members (full_name, role, bio, image_path, is_active)
VALUES
    ('Ayesha Noor', 'Creative Director', 'Ayesha leads wedding concepts, color palettes, and visual storytelling for every event.', 'team/ayesha-noor.jpg', 1),
    ('Bilal Hassan', 'Event Manager', 'Bilal coordinates timelines, logistics, and vendor delivery for high-pressure events.', 'team/bilal-hassan.jpg', 1),
    ('Maryam Zafar', 'Photography Lead', 'Maryam specializes in editorial and documentary photography for intimate and grand occasions.', 'team/maryam-zafar.jpg', 1),
    ('Hassan Raza', 'Styling Consultant', 'Hassan curates floral compositions, guest experience details, and luxury venue styling.', 'team/hassan-raza.jpg', 1)
ON DUPLICATE KEY UPDATE
    role = VALUES(role),
    bio = VALUES(bio),
    image_path = VALUES(image_path),
    is_active = VALUES(is_active);

-- Sample inquiry messages from website visitors.
INSERT INTO inquiries (user_id, full_name, email, phone, subject, message, status)
VALUES
    (1, 'Sana Khan', 'sana.khan@example.com', '+923001112233', 'Wedding planning details', 'I would like more information about wedding packages and a venue styling consultation.', 'new'),
    (NULL, 'Guest Visitor', 'guest@example.com', '+923004445566', 'Photography package', 'Please share a quote for a full-day photography package for our family event.', 'new')
ON DUPLICATE KEY UPDATE
    user_id = VALUES(user_id),
    phone = VALUES(phone),
    subject = VALUES(subject),
    message = VALUES(message),
    status = VALUES(status);

-- Sample event and booking records that demonstrate the relationship flow.
INSERT INTO events (user_id, title, event_type, event_date, venue, guest_count, budget, status, notes)
VALUES
    (1, 'Spring Wedding Celebration', 'wedding', '2026-10-16', 'Lahore Grand Hall', 320, 350000.00, 'confirmed', 'Main family event with ceremony, dinner, and live music.'),
    (2, 'Corporate Networking Evening', 'corporate', '2026-11-08', 'Karachi Business Center', 180, 210000.00, 'draft', 'Need stage setup, branding, and guest welcome management.')
ON DUPLICATE KEY UPDATE
    event_type = VALUES(event_type),
    event_date = VALUES(event_date),
    venue = VALUES(venue),
    guest_count = VALUES(guest_count),
    budget = VALUES(budget),
    status = VALUES(status),
    notes = VALUES(notes);

INSERT INTO bookings (user_id, event_id, service_id, quantity, total_price, status, notes)
VALUES
    (1, 1, 1, 1, 95000.00, 'confirmed', 'Full wedding planning package selected.'),
    (1, 1, 3, 1, 68000.00, 'confirmed', 'Luxury styling for reception and mandap area.'),
    (2, 2, 6, 1, 82000.00, 'pending', 'Corporate event management package for launch event.')
ON DUPLICATE KEY UPDATE
    event_id = VALUES(event_id),
    service_id = VALUES(service_id),
    quantity = VALUES(quantity),
    total_price = VALUES(total_price),
    status = VALUES(status),
    notes = VALUES(notes);

-- =============================================================
-- END OF SEED DATA
-- =============================================================
-- The demo records above are intentionally simple and realistic for a university project.
-- Replace them with real data when you move the project to production.
-- =============================================================
