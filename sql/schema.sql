DROP
  DATABASE test_db;

CREATE
  DATABASE test_db DEFAULT CHARACTER SET 'utf8' DEFAULT COLLATE 'utf8_general_ci';

USE
  test_db;

CREATE TABLE dd_projects
(
  id      INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT       NOT NULL,
  name    CHAR(128) NOT NULL,
  KEY (user_id),
  KEY (name)
);

CREATE TABLE dd_tasks
(
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT       NOT NULL,
  project_id INT       NOT NULL,
  title      CHAR(255) NOT NULL,
  file       CHAR(255),
  deadline   DATE      NULL,
  status     BOOL      DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (user_id),
  KEY (project_id),
  INDEX (title),
  INDEX (deadline),
  KEY (status)
);

CREATE TABLE dd_users
(
  id         INT AUTO_INCREMENT PRIMARY KEY,
  email      VARCHAR(128) NOT NULL UNIQUE,
  name       CHAR(128)    NOT NULL,
  password   CHAR(64)     NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (name)
);

/*
 FULLTEXT — это специальный вид индекса, поддерживаемый в MySQL.
 Добавим этот индекс нужным полям, чтобы организовать  по ним полнотекстовый поиск
 */
CREATE
  FULLTEXT INDEX task_search ON dd_tasks (title);
