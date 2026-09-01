-- Schéma de la base Abricot.co (SQLite)
-- Les dates sont stockées en texte ISO 8601 UTC (ex. 2026-01-15T00:00:00.000Z).

PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS users (
    id        TEXT NOT NULL PRIMARY KEY,
    email     TEXT NOT NULL UNIQUE,
    password  TEXT NOT NULL,
    name      TEXT,
    createdAt TEXT NOT NULL,
    updatedAt TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS projects (
    id          TEXT NOT NULL PRIMARY KEY,
    name        TEXT NOT NULL,
    description TEXT,
    ownerId     TEXT NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    createdAt   TEXT NOT NULL,
    updatedAt   TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS project_members (
    id        TEXT NOT NULL PRIMARY KEY,
    role      TEXT NOT NULL DEFAULT 'CONTRIBUTOR' CHECK (role IN ('ADMIN', 'CONTRIBUTOR')),
    joinedAt  TEXT NOT NULL,
    userId    TEXT NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    projectId TEXT NOT NULL REFERENCES projects (id) ON DELETE CASCADE,
    UNIQUE (userId, projectId)
);

CREATE TABLE IF NOT EXISTS tasks (
    id          TEXT NOT NULL PRIMARY KEY,
    title       TEXT NOT NULL,
    description TEXT,
    status      TEXT NOT NULL DEFAULT 'TODO' CHECK (status IN ('TODO', 'IN_PROGRESS', 'DONE', 'CANCELLED')),
    priority    TEXT NOT NULL DEFAULT 'MEDIUM' CHECK (priority IN ('LOW', 'MEDIUM', 'HIGH', 'URGENT')),
    dueDate     TEXT,
    createdAt   TEXT NOT NULL,
    updatedAt   TEXT NOT NULL,
    projectId   TEXT NOT NULL REFERENCES projects (id) ON DELETE CASCADE,
    creatorId   TEXT NOT NULL REFERENCES users (id)
);

CREATE TABLE IF NOT EXISTS task_assignees (
    id         TEXT NOT NULL PRIMARY KEY,
    assignedAt TEXT NOT NULL,
    taskId     TEXT NOT NULL REFERENCES tasks (id) ON DELETE CASCADE,
    userId     TEXT NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    UNIQUE (taskId, userId)
);

CREATE TABLE IF NOT EXISTS comments (
    id        TEXT NOT NULL PRIMARY KEY,
    content   TEXT NOT NULL,
    createdAt TEXT NOT NULL,
    updatedAt TEXT NOT NULL,
    taskId    TEXT NOT NULL REFERENCES tasks (id) ON DELETE CASCADE,
    authorId  TEXT NOT NULL REFERENCES users (id) ON DELETE CASCADE
);

-- Index sur les clés étrangères les plus utilisées dans les jointures
CREATE INDEX IF NOT EXISTS idx_projects_owner ON projects (ownerId);
CREATE INDEX IF NOT EXISTS idx_members_project ON project_members (projectId);
CREATE INDEX IF NOT EXISTS idx_tasks_project ON tasks (projectId);
CREATE INDEX IF NOT EXISTS idx_assignees_user ON task_assignees (userId);
CREATE INDEX IF NOT EXISTS idx_comments_task ON comments (taskId);
