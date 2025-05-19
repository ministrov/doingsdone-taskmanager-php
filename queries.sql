/* Добавление новых записей в таблицу users */
INSERT INTO dd_users (email, name, password)
VALUES ("maria@yandex.ru", "Мария", "$2y$10$MyfA.jDDhuq3TXu3uk4E5eSggtOEhZ1CP8.Q2FyMlvEjyVVxAi9Je"),/*12345678*/
       ("alex@gmail.com", "Александр", "$2y$10$nIaJeKVgIAAzER0B0kS50ez3u7vurFd5ovs4DOlt/LKrV94/uAD0S");/*87654321*/

/* Добавление новых записей в таблицу projects */
INSERT INTO dd_projects (user_id, name)
VALUES (1, "Входящие"),
       (1, "Учёба"),
       (1, "Работа"),
       (1, "Домашние дела"),
       (1, "Авто"),
       (1, "Ремонт"),
       (2, "Главное"),
       (2, "Проект"),
       (2, "Учёба"),
       (2, "Игра"),
       (2, "Туризм");

/* Добавление новых записей в таблицу tasks */
INSERT INTO dd_tasks (user_id, project_id, title, deadline)
VALUES (1, 3, "Собеседование в IT компании", "2024-12-11"),
       (1, 3, "Выполнить тестовое задание", "2024-12-09"),
       (1, 2, "Сделать задание первого раздела", "2024-12-04"),
       (1, 2, "Сделать задание второго раздела", "2024-12-07"),
       (1, 1, "Встреча с другом", "2024-12-06"),
       (1, 1, "Сходить в кино с друзьями", "2024-12-08"),
       (1, 4, "Купить корм для кота", "2024-12-09"),
       (1, 4, "Заказать пиццу", "2024-12-10"),
       (1, 5, "Сменить резину", "2024-12-01"),
       (1, 5, "Купить машину", "2024-01-14"),
       (2, 8, "Сдать проект", "2024-12-09"),
       (2, 8, "Выполнить тестовое задание", "2024-12-11"),
       (2, 9, "Купить книгу по MySQL", "2024-12-06"),
       (2, 7, "Снять квартиру", "2024-12-07"),
       (2, 10, "Футбол", "2024-12-08"),
       (2, 10, "Заказать пиццу", "2024-12-08"),
       (2, 10, "Встретиться с друзьями", "2024-12-10"),
       (2, 7, "Купить новый ноутбук", "2024-12-10"),
       (2, 7, "Купить корм для собаки", "2024-12-06");

/* Добавить новый проект в таблицу projects для пользователя с user_id = 2 */
INSERT INTO dd_projects (user_id, name)
VALUES (2, "Учёба");

/* Добавить новую задачу в таблицу tasks для пользователя с user_id = 2 */
INSERT INTO dd_tasks (user_id, project_id, title, deadline)
VALUES (2, 10, "Записаться на курсы", "2024-12-03");

/* Получить все записи из таблицы users */
SELECT *
FROM dd_users;
/* Получить список из всех имён и паролей
 */
SELECT name, password
FROM dd_users;

/* Получит список из всех проектов в алфавитном порядке */
SELECT *
FROM dd_projects
ORDER BY name ASC;

/* Получить список из всех проектов для одного пользователя */
SELECT name
FROM dd_projects
WHERE user_id = 2;

/* Пометить задачу как выполненную */
UPDATE dd_tasks
SET status = 1
WHERE title = "Заказать пиццу";

/* Обновить название задачи по её идентификатору */
UPDATE dd_tasks
SET title = "Сделать задание третьего раздела"
WHERE id = 3;

/* Получить список из всех задач для одного проекта */
SELECT *
FROM dd_tasks
       LEFT JOIN dd_projects ON dd_projects.id = dd_tasks.project_id
WHERE dd_projects.name LIKE "%авто%";

SELECT *
FROM dd_tasks
       LEFT JOIN dd_projects ON dd_projects.id = dd_tasks.project_id
WHERE dd_projects.id = 3;

SELECT *
FROM dd_tasks
       LEFT JOIN dd_projects ON dd_projects.id = dd_tasks.project_id
WHERE dd_projects.name LIKE "Учёба";

SELECT dd_tasks.user_id,
       dd_tasks.project_id,
       dd_projects.name AS project_name,
       dd_tasks.title   AS task_title,
       dd_tasks.deadline,
       dd_tasks.status,
       dd_tasks.created_at
FROM dd_tasks
       LEFT JOIN dd_projects ON dd_projects.id = dd_tasks.project_id
WHERE dd_projects.name = "Учёба";

/* Получить все записи из всех таблиц */
SELECT *
FROM dd_tasks
       LEFT JOIN dd_projects ON dd_tasks.project_id = dd_projects.id
       LEFT JOIN dd_users ON dd_tasks.user_id = dd_users.id;

SELECT *
FROM dd_tasks t
       LEFT JOIN dd_projects p ON t.project_id = p.id
       LEFT JOIN dd_users u ON t.user_id = u.id;

/* Получить по всем пользователям данные о проектах и задачах в них */
SELECT dd_users.name    AS user_name,
       dd_projects.name AS project_name,
       dd_tasks.*
FROM dd_tasks
       LEFT JOIN dd_projects ON dd_tasks.project_id = dd_projects.id
       LEFT JOIN dd_users ON dd_tasks.user_id = dd_users.id;

/* Пометить задачи как выполненные */
UPDATE dd_tasks
SET status = 1
WHERE deadline = "2024-11-06";