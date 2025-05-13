/*
 Добавление новых записей в таблицу users
*/

INSERT INTO users (email, name, password)
VALUES ("vasilisa@yandex.ru", "Василиса", "rnjnfv"),
        ("nikifor@gmail.com", "Никифор", "zpltcm");

/*
 Добавление новых записей в таблицу projects
*/

INSERT INTO projects (user_id, name)
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