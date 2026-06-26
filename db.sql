-- Attendly PostgreSQL schema
-- Run this after creating the database to initialize the schema.

CREATE TABLE IF NOT EXISTS attendly_users (
    role TEXT PRIMARY KEY,
    name TEXT DEFAULT '',
    email TEXT DEFAULT '',
    avatar TEXT DEFAULT '',
    designation TEXT DEFAULT '',
    department TEXT DEFAULT '',
    bio TEXT DEFAULT '',
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS attendly_students (
    roll_no TEXT PRIMARY KEY,
    data JSONB NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS attendly_courses (
    code TEXT PRIMARY KEY,
    data JSONB NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS attendly_leaves (
    id TEXT PRIMARY KEY,
    data JSONB NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS attendly_reports (
    id TEXT PRIMARY KEY,
    data JSONB NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS attendly_logs (
    id SERIAL PRIMARY KEY,
    message TEXT NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

INSERT INTO attendly_users (role, name, email, avatar, designation, department, bio)
VALUES
('admin', '', '', '', '', '', ''),
('faculty', '', '', '', '', '', ''),
('student', '', '', '', '', '', ''),
('parent', '', '', '', '', '', '')
on conflict do nothing;
