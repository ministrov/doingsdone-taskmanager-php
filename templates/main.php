<section class="content__side">
  <h2 class="content__side-heading">Проекты</h2>
  <nav class="main-navigation">
    <ul class="main-navigation__list">
      <?php
      $project_id = isset($_GET["project_id"]) ? $_GET["project_id"] : 0;
      foreach ($user_projects as $project): ?>
        <li class="main-navigation__list-item
                    <?php if ($project["id"] === $project_id): ?>
                        main-navigation__list-item--active
                    <?php endif; ?>">
          <a class="main-navigation__list-item-link"
            href="/?project_id=<?= $project['id'] ?>"><?= htmlspecialchars($project['title']) ?></a>
          <span class="main-navigation__list-item-count"><?= tasks_count($all_user_tasks, $project['id']) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  </nav>
  <a class="button button--transparent button--plus content__side-button" href="add_project.php" target="project_add">Добавить проект</a>
</section>

<main class="content__main">
  <h2 class="content__main-heading">Список задач</h2>
  <form class="search-form" action="/" method="get" autocomplete="off">
    <label>
      <input class="search-form__input" type="text" name="q" value=" <?= trim(filter_input(INPUT_GET, 'q')) ?>"
        placeholder="Поиск по задачам">
    </label>
    <input class="search-form__submit" type="submit" name="" value="Искать">
  </form>

  <div class="tasks-controls">
    <nav class="tasks-switch">
      <a href="/?filter=all&show_completed=0&project_id=<?= $project_id ?>" class="tasks-switch__item
            <?php if ($id_task_time === 'all' || $id_task_time === '' || !$id_task_time) : ?> tasks-switch__item--active<?php endif; ?>">Все
        задачи</a>

      <a href="/?filter=today&show_completed=0&project_id=<?= $project_id ?>" class="tasks-switch__item
            <?php if ($id_task_time === 'today') : ?> tasks-switch__item--active<?php endif; ?>">Повестка дня</a>

      <a href="/?filter=tomorrow&show_completed=0&project_id=<?= $project_id ?>" class="tasks-switch__item
            <?php if ($id_task_time === 'tomorrow') : ?> tasks-switch__item--active <?php endif; ?>">Завтра</a>

      <a href="/?filter=expired&show_completed=0&project_id=<?= $project_id ?>" class="tasks-switch__item
            <?php if ($id_task_time === 'expired') : ?>  tasks-switch__item--active<?php endif; ?>">Просроченные</a>
    </nav>

    <label class="checkbox">
      <input class="checkbox__input visually-hidden show_completed" type="checkbox" <?php
                                                                                    if ($show_complete_tasks === 1): ?>checked <?php
                                                                                    endif; ?>>

      <span class="checkbox__text">Показывать выполненные</span>
    </label>
  </div>
  <table class="tasks">
    <?php
    foreach ($tasks as $task):
      if ($show_complete_tasks === 0 && $task['status'] === 0) {
        continue;
      }
    ?>
      <tr class="tasks__item task <?php if ($task['status'] === '1') : ?> task--completed
            <?php endif;
                                  if (custom_date_diff($task['deadline']) <= 24): ?> task--important <?php endif; ?>">
        <td class="task__select">
          <label class="checkbox task__checkbox">
            <input class="checkbox__input visually-hidden task__checkbox" type="checkbox" value="<?= $task['id'] ?>"
              data-project="<?= $project_id ?>" <?= $task['status'] === '1' ? 'checked' : '' ?>>
            <span class="checkbox__text"><?= htmlspecialchars($task['name']) ?></span>
          </label>
        </td>
        <td class="task__file">
          <?php if (isset($task['file']) && $task['file']): ?>
            <a class="download-link" href="<?= "/uploads/" . $task['file'] ?>"><?= $task['file'] ?></a>
          <?php endif; ?>
        </td>
        <td class="task__date">
          <?= date("d.m.Y", strtotime($task["deadline"])) ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <p class="error-message"><?= $error_message ?></p>
  </table>
</main>