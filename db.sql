-- Attendly PostgreSQL Schema
-- Run this after creating the database to initialize the schema.

-- Role-based user profiles (admin, faculty, student, parent)
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

-- Student records with enrollment and attendance tracking
-- JSONB data contains: name, rollNo, enrollment, status, enrolledSections (array), attendance (percent)
CREATE TABLE IF NOT EXISTS attendly_students (
    roll_no TEXT PRIMARY KEY,
    data JSONB NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- Course/lecture records with schedule and compliance tracking
-- JSONB data contains: code, title, coordinator, schedule, rooms, totalHours, compliance (percent), absenteeCount
CREATE TABLE IF NOT EXISTS attendly_courses (
    code TEXT PRIMARY KEY,
    data JSONB NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- Leave request records from students
-- JSONB data contains: id, studentName, studentAvatar, rollNo, type, date, reason, status (Pending/Approved/Rejected)
CREATE TABLE IF NOT EXISTS attendly_leaves (
    id TEXT PRIMARY KEY,
    data JSONB NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- Generated academic reports archive
-- JSONB data contains: id, title, type, generatedBy, generatedAt, status, fileSize
CREATE TABLE IF NOT EXISTS attendly_reports (
    id TEXT PRIMARY KEY,
    data JSONB NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- Activity audit log for tracking portal events
CREATE TABLE IF NOT EXISTS attendly_logs (
    id SERIAL PRIMARY KEY,
    message TEXT NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- Initialize role shells for portal access
INSERT INTO attendly_users (role, name, email, avatar, designation, department, bio)
VALUES
('admin', '', '', '', '', '', ''),
('faculty', '', '', '', '', '', ''),
('student', '', '', '', '', '', ''),
('parent', '', '', '', '', '', '')
ON CONFLICT (role) DO NOTHING;
