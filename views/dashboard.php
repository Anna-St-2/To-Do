<?php require 'header.php'; ?> <!-- подключаем общий header -->

<div class="top">
    <!-- приветствие пользователя -->
    <span>
        Здравствуйте, <?= $_SESSION['username'] ?> 
        <!-- @SECURITY_MODULE (XSS: htmlspecialchars) -->
    </span>

    <!-- кнопка выхода -->
    <a href="index.php?route=logout">Выйти</a>
</div>

<div class="container">

    <!-- форма добавления задачи -->
    <form id="taskForm">
        <input 
            id="taskInput" 
            placeholder="Введите новую задачу" 
            required
        >
        <button>Добавить</button>
    </form>

    <!-- фильтры -->
    <div class="filters">
        <button data-filter="all">Все</button>
        <button data-filter="active">Активные</button>
        <button data-filter="completed">Выполненные</button>
    </div>

    <!-- список задач -->
    <ul id="taskList">
        <!-- сюда JS вставляет задачи -->
    </ul>

    <!-- состояние "пусто" (управляется JS) -->
    <div id="emptyState" style="display:none;">
        Нет задач
    </div>

</div>

<?php require 'footer.php'; ?> <!-- подключаем footer -->