<section class="content__side">
    <h2 class="content__side-heading">Проекты</h2>

    <nav class="main-navigation">
        <ul class="main-navigation__list">
            <?php foreach ($projects as $item): ?>

                <?php $class_name = isset($item["id"]) && isset($_GET["project_id"]) && $item["id"] === intval($_GET["project_id"]) ?
                    "main-navigation__list-item--active" : ""; ?>
                <li class="main-navigation__list-item <?= $class_name; ?>">

                    <a class="main-navigation__list-item-link" href="?project_id=<?= $item["id"]; ?>">
                        <?php if (isset($item["name"])): ?>
                            <?= htmlspecialchars($item["name"]); ?>
                        <?php endif; ?>
                    </a>
                    <span class="main-navigation__list-item-count">
                        <?= getCountTasksProject($tasks_all, $item); ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <a class="button button--transparent button--plus content__side-button" href="add-project.php">Добавить проект</a>
</section>

<main class="content__main">
    <h2 class="content__main-heading">Список задач</h2>

    <!-- Поиск по задачам -->
    <form class="search-form" action="<?= $ROOT_DIRECTORY; ?>" method="get" autocomplete="off">
        <label>
            <input class="search-form__input" type="text" name="query" value="<?= htmlspecialchars(getGetVal("query")); ?>"
                   placeholder="Поиск по задачам">
        </label>
        <input class="search-form__submit" type="submit" name="" value="Искать">
    </form>

    <div class="search-result">
        <ul class="search-result__list">
            <?php foreach ($search_tasks as $item): ?>
                <li class="search-result__item">

                    <?php if (isset($item["project_id"])): ?>
                    <a class="search-result__link" href="?project_id=<?= $item["project_id"]; ?>">
                        <?php endif; ?>
                        <?php if (isset($item["title"])): ?>
                            <?= htmlspecialchars($item["title"]); ?>
                        <?php endif; ?>
                    </a>
                    <span class="search-result__text">
                        <?php if (isset($item["project"])): ?>
                            <?= htmlspecialchars($item["project"]); ?>
                        <?php endif; ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>

        <p class="error-message"><?= $search_tasks_message ?></p>
    </div>

    <div class="tasks-controls">
        <nav class="tasks-switch">
            <?php $class_name = !isset($_GET["tab"]) ? "tasks-switch__item--active" : ""; ?>
            <a class="tasks-switch__item <?= $class_name; ?>" href="<?= $ROOT_DIRECTORY; ?>">Все задачи</a>

            <?php $class_name = isset($_GET["tab"]) && $_GET["tab"] == "today" ? "tasks-switch__item--active" : ""; ?>
            <a class="tasks-switch__item <?= $class_name; ?>" href="?tab=today">Повестка дня</a>

            <?php $class_name = isset($_GET["tab"]) && $_GET["tab"] == "tomorrow" ? "tasks-switch__item--active" : ""; ?>
            <a class="tasks-switch__item <?= $class_name; ?>" href="?tab=tomorrow">Завтра</a>

            <?php $class_name = isset($_GET["tab"]) && $_GET["tab"] == "past" ? "tasks-switch__item--active" : ""; ?>
            <a class="tasks-switch__item <?= $class_name; ?>" href="?tab=past">Просроченные</a>
        </nav>

        <label class="checkbox">
            <a href="<?= $url . $url_link ?>">
                <input class="checkbox__input visually-hidden" type="checkbox"
                    <?php if ($show_complete_tasks == 1): ?>
                        checked
                    <?php endif; ?>
                >
                <span class="checkbox__text">Показывать выполненные</span>
            </a>
        </label>
    </div>

    <table class="tasks">
        <?php foreach ($tasks as $item): ?>
            <?php if (isset($item["status"]) && $item["status"] && $show_complete_tasks == 0): ?>
                <?php continue; ?>
            <?php endif; ?>

            <?php $class_name1 = isset($item["status"]) && $item["status"] ? "task--completed" : ""; ?>
            <?php $class_name2 = isset($item["hours_until_end"]) && $item["hours_until_end"] <= 24 && $item["status"] == 0 ?
                "task--important" : ""; ?>
            <tr class="tasks__item task <?= $class_name1; ?> <?= $class_name2; ?>">

                <td class="task__select">
                    <label class="checkbox task__checkbox">
                        <?php if (isset($item["id"])): ?>
                        <a href="?task_id=<?= $item["id"]; ?><?= $tabs; ?><?= $show_complete_tasks_url; ?>">
                            <?php endif; ?>
                            <input class="checkbox__input visually-hidden" type="checkbox"
                                <?php if (isset($item["status"]) && $item["status"]): ?>
                                    checked
                                <?php endif; ?>
                            >
                            <span class="checkbox__text">
                                <?php if (isset($item["title"])): ?>
                                    <?= htmlspecialchars($item["title"]); ?>
                                <?php endif; ?>
                            </span>
                        </a>
                    </label>
                </td>

                <td class="task__file">
                    <?php if (isset($item["file"])): ?>
                        <a class="download-link" href="<?= $ROOT_DIRECTORY . $item["file"]; ?>" target="_blank">Файл</a>
                    <?php endif; ?>
                </td>

                <td class="task__date">
                    <?php if (isset($item["deadline"])): ?>
                        <?= htmlspecialchars(date("d.m.Y", strtotime($item["deadline"]))); ?>
                    <?php endif; ?>
                </td>
                <td class="task__controls"></td>
            </tr>
        <?php endforeach; ?>
    </table>
</main>
