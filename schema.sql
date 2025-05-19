CREATE DATABASE doingsdone_db DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci;
USE doingsdone_db;

CREATE TABLE `users`
(
  `id`       INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name`     varchar(50)  NOT NULL,
  `email`    varchar(50)  NOT NULL UNIQUE,
  `password` varchar(150) NOT NULL,
  `data`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `project`
(
  `id`      INT         NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `title`   varchar(50) NOT NULL,
  `user_id` INT
);

CREATE TABLE `task`
(
  `id`         INT         NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name`       varchar(50) NOT NULL,
  `user_id`    INT         NOT NULL,
  `project_id` INT         NOT NULL,
  `status`     TINYINT(1)  NOT NULL DEFAULT '0',
  `deadline`   TIMESTAMP   NULL,
  `file`       varchar(50),
  `created_at` TIMESTAMP            DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX search_by_name on `users` (name);
CREATE INDEX search_by_project on `project` (title);
CREATE INDEX search_by_task on `task` (NAME);
CREATE FULLTEXT INDEX task_search ON task (NAME);