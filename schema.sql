CREATE DATABASE doings_done;
USE doings_done;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  registration_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- CREATE TABLE users (
--   id SERIAL PRIMARY KEY,
--   email VARCHAR(255) NOT NULL UNIQUE,
--   name VARCHAR(100) NOT NULL,
--   password_hash VARCHAR(255) NOT NULL,
--   registration_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
-- );

-- CREATE TABLE projects (
--   id SERIAL PRIMARY KEY,
--   name VARCHAR(255) NOT NULL,
--   author_id INTEGER NOT NULL,
--   created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
--   FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
-- );

-- CREATE TABLE tasks (
--   id SERIAL PRIMARY KEY,
--   title VARCHAR(255) NOT NULL,
--   status SMALLINT NOT NULL DEFAULT 0 CHECK (status IN (0, 1)),
--   created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
--   deadline DATE,
--   file_path VARCHAR(512),
--   author_id INTEGER NOT NULL,
--   project_id INTEGER,
--   FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
--   FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
-- );