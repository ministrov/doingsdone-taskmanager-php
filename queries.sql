/*
 Добавление новых записей в таблицу users
*/

INSERT INTO users (email, name, password_hash, registration_date)
VALUES ("vasilisa@yandex.ru", "Василиса", "rnjnfv", "11-02-2019"),
        ("nikifor@gmail.com", "Никифор", "zpltcm", "11-02-2019");

/*
 Добавление новых записей в таблицу projects
*/

INSERT INTO projects (author_id, name, created_at)
VALUES (1, "Входящие", "11-02-2019"),
       (1, "Учёба", "11-02-2019"),
       (1, "Работа", "11-02-2019"),
       (1, "Домашние дела", "11-02-2019"),
       (1, "Авто", "11-02-2019"),
       (1, "Ремонт", "11-02-2019"),
       (2, "Главное", "11-02-2019"),
       (2, "Проект", "11-02-2019"),
       (2, "Учёба", "11-02-2019"),
       (2, "Игра", "11-02-2019"),
       (2, "Туризм", "11-02-2019");


/*
 Добавление новых записей в таблицу tasks
*/

INSERT INTO tasks (user_id, project_id, title, deadline)
VALUES (1, 3, "Собеседование в IT компании", "2019-12-11"),
       (1, 3, "Выполнить тестовое задание", "2019-12-09"),
       (1, 2, "Сделать задание первого раздела", "2019-12-04"),
       (1, 2, "Сделать задание второго раздела", "2019-12-07"),
       (1, 1, "Встреча с другом", "2019-12-06"),
       (1, 1, "Сходить в кино с друзьями", "2019-12-08"),
       (1, 4, "Купить корм для кота", "2019-12-09"),
       (1, 4, "Заказать пиццу", "2019-12-10"),
       (1, 5, "Сменить резину", "2019-12-01"),
       (1, 5, "Купить машину", "2020-01-14"),
       (2, 8, "Сдать проект", "2019-12-09"),
       (2, 8, "Выполнить тестовое задание", "2019-12-11"),
       (2, 9, "Купить книгу по MySQL", "2019-12-06"),
       (2, 7, "Снять квартиру", "2019-12-07"),
       (2, 10, "Футбол", "2019-12-08"),
       (2, 10, "Заказать пиццу", "2019-12-08"),
       (2, 10, "Встретиться с друзьями", "2019-12-10"),
       (2, 7, "Купить новый ноутбук", "2019-12-10"),
       (2, 7, "Купить корм для собаки", "2019-12-06");

/*
 Добавить новый проект в таблицу projects для пользователя с user_id = 2
*/

INSERT INTO projects (user_id, name)
VALUES (2, "Учёба");

/*
 Добавить новую задачу в таблицу tasks для пользователя с user_id = 2
*/

INSERT INTO tasks (user_id, project_id, title, deadline)
VALUES (2, 10, "Записаться на курсы", "2019-12-03");

/*
 Получить все записи из таблицы users
*/

SELECT * FROM users;

/*
 Получить список из всех имён и паролей
*/

SELECT name, password FROM users;

/*
 Получит список из всех проектов в алфавитном порядке
*/

SELECT * FROM projects ORDER BY name ASC;

/*
 Получить список из всех проектов для одного пользователя
*/

SELECT name FROM projects
WHERE user_id = 2;

/*
 Пометить задачу как выполненную
 */
UPDATE tasks SET status = 1
WHERE title = "Заказать пиццу";

/*
 Обновить название задачи по её идентификатору
 */
UPDATE tasks SET title = "Сделать задание третьего раздела"
WHERE id = 3;

/*
 Получить список из всех задач для одного проекта
 */
SELECT * FROM tasks
LEFT JOIN projects ON projects.id = tasks.project_id
WHERE projects.name LIKE "%авто%";

SELECT * FROM tasks
LEFT JOIN projects ON projects.id = tasks.project_id
WHERE projects.id = 3;

SELECT * FROM tasks
LEFT JOIN projects ON projects.id = tasks.project_id
WHERE projects.name LIKE "Учёба";

SELECT tasks.user_id,
       tasks.project_id,
       projects.name AS project_name,
       tasks.title AS task_title,
       tasks.deadline,
       tasks.status,
       tasks.created_at
FROM tasks
LEFT JOIN projects ON projects.id = tasks.project_id
WHERE projects.name = "Учёба";

/*
 Получить все записи из всех таблиц
 */
SELECT * FROM  tasks
LEFT JOIN projects ON tasks.project_id = projects.id
LEFT JOIN users ON tasks.user_id = users.id;

SELECT * FROM  tasks t
LEFT JOIN projects p ON t.project_id = p.id
LEFT JOIN users u ON t.user_id = u.id;

/*
 Получить по всем пользователям данные о проектах и задачах в них
 */
SELECT
    users.name AS user_name,
    projects.name AS project_name,
    tasks.*
FROM tasks
LEFT JOIN projects ON tasks.project_id = projects.id
LEFT JOIN users ON tasks.user_id = users.id;

/*
 Пометить задачи как выполненные
 */
UPDATE tasks SET status = 1
WHERE deadline = "2019-11-06";