/**
 * To-Do List — клиентская логика
 * AJAX-запросы к API и манипуляции с DOM
 */

// ============================================
// API-МОДУЛЬ
// ============================================

const API = {
    /**
     * Универсальная функция для запросов к api.php
     * 
     * @param {string} action - действие (task_add, task_list, task_toggle, task_delete)
     * @param {string} method - HTTP-метод (GET, POST)
     * @param {object} body   - тело запроса (для POST)
     * @returns {Promise}
     */
    async request(action, method = 'GET', body = null) {
        let url = `api.php?action=${action}`;

        // Для GET-запросов добавляем параметры в URL
        if (method === 'GET' && body) {
            const params = new URLSearchParams(body).toString();
            url += '&' + params;
        }

        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': window.CSRF_TOKEN || ''
            }
        };

        // @SECURITY_MODULE: место для добавления CSRF-токена в заголовки

        // Для POST-запросов тело в JSON
        if (method === 'POST' && body) {
            options.body = JSON.stringify(body);
        }

        const response = await fetch(url, options);

        // Если ответ не ок — пробрасываем ошибку
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || `Ошибка сервера (${response.status})`);
        }

        return response.json();
    }
};

// ============================================
//    DOM-ЭЛЕМЕНТЫ
//    ============================================

const taskList = document.getElementById('taskList');
const addTaskForm = document.getElementById('addTaskForm');
const taskTitleInput = document.getElementById('taskTitle');
const loadingIndicator = document.getElementById('loadingIndicator');
const errorMessage = document.getElementById('errorMessage');
const filterButtons = document.querySelectorAll('.filter-btn');

// Текущий фильтр
let currentFilter = 'all';

// ============================================
//    ФУНКЦИИ РАБОТЫ С ЗАДАЧАМИ
//    ============================================

/**
 * Загрузка списка задач с сервера
 * 
 * @param {string} filter - 'all' | 'active' | 'completed'
 */
async function loadTasks(filter = 'all') {
    currentFilter = filter;
    showLoading(true);
    hideError();

    try {
        const result = await API.request('task_list', 'GET', { filter: filter });

        if (result.success) {
            renderTasks(result.tasks);
        } else {
            showError(result.error || 'Не удалось загрузить задачи');
        }
    } catch (error) {
        showError(error.message || 'Ошибка соединения с сервером');
    } finally {
        showLoading(false);
    }
}

/**
 * Отрисовка списка задач в DOM
 * 
 * @param {Array} tasks - массив задач
 */
function renderTasks(tasks) {
    taskList.innerHTML = '';

    // Пустой список
    if (tasks.length === 0) {
        taskList.innerHTML = `
            <div class="empty-state">
                <p>Нет задач</p>
                <p class="empty-hint">
                    ${currentFilter === 'all' ? 'Добавьте первую задачу выше' : 
                      currentFilter === 'active' ? 'Все задачи выполнены!' : 
                      'Нет выполненных задач'}
                </p>
            </div>
        `;
        return;
    }

    // Создаём элементы задач
    tasks.forEach(task => {
        const taskItem = document.createElement('div');
        taskItem.className = `task-item ${task.is_completed === '1' || task.is_completed === 1 ? 'completed' : ''}`;
        taskItem.dataset.id = task.id;
        taskItem.innerHTML = `
            <label class="task-checkbox-label">
                <input
                    type="checkbox"
                    class="task-checkbox"
                    ${task.is_completed === '1' || task.is_completed === 1 ? 'checked' : ''}
                >
                <span class="checkmark"></span>
            </label>
            <span class="task-title">${escapeHtml(task.title)}</span>
            <button class="task-delete-btn" title="Удалить задачу">×</button>
        `;

        // Обработчик клика по чекбоксу
        const checkbox = taskItem.querySelector('.task-checkbox');
        checkbox.addEventListener('change', () => toggleTask(task.id));

        // Обработчик клика по кнопке удаления
        const deleteBtn = taskItem.querySelector('.task-delete-btn');
        deleteBtn.addEventListener('click', () => deleteTask(task.id));

        taskList.appendChild(taskItem);
    });
}

/**
 * Добавление новой задачи
 */
async function addTask() {
    const title = taskTitleInput.value.trim();
    if (!title) {
        showError('Введите текст задачи');
        taskTitleInput.focus();
        return;
    }

    hideError();

    try {
        const result = await API.request('task_add', 'POST', { title: title });

        if (result.success) {
            taskTitleInput.value = '';
            taskTitleInput.focus();
            // Перезагружаем список
            await loadTasks(currentFilter);
        } else {
            showError(result.error || 'Не удалось добавить задачу');
        }
    } catch (error) {
        showError(error.message || 'Ошибка соединения с сервером');
    }
}

/**
 * Переключение статуса задачи (выполнена/не выполнена)
 * 
 * @param {number} taskId - ID задачи
 */
async function toggleTask(taskId) {
    hideError();

    try {
        const result = await API.request('task_toggle', 'POST', { task_id: taskId });

        if (result.success) {
            // Если фильтр не "все", перезагружаем список
            if (currentFilter !== 'all') {
                await loadTasks(currentFilter);
            } else {
                // Просто обновляем визуал у элемента
                const taskItem = document.querySelector(`.task-item[data-id="${taskId}"]`);
                if (taskItem) {
                    if (result.is_completed) {
                        taskItem.classList.add('completed');
                        taskItem.querySelector('.task-checkbox').checked = true;
                    } else {
                        taskItem.classList.remove('completed');
                        taskItem.querySelector('.task-checkbox').checked = false;
                    }
                }
            }
        } else {
            showError(result.error || 'Не удалось обновить задачу');
        }
    } catch (error) {
        showError(error.message || 'Ошибка соединения с сервером');
    }
}

/**
 * Удаление задачи
 * 
 * @param {number} taskId - ID задачи
 */
async function deleteTask(taskId) {
    hideError();

    // Анимация удаления — добавляем класс
    const taskItem = document.querySelector(`.task-item[data-id="${taskId}"]`);
    if (taskItem) {
        taskItem.style.opacity = '0.5';
        taskItem.style.transform = 'scale(0.95)';
    }

    try {
        const result = await API.request('task_delete', 'POST', { task_id: taskId });

        if (result.success) {
            // Перезагружаем список, чтобы сохранить актуальное состояние
            await loadTasks(currentFilter);
        } else {
            // Возвращаем визуал, если ошибка
            if (taskItem) {
                taskItem.style.opacity = '1';
                taskItem.style.transform = 'scale(1)';
            }
            showError(result.error || 'Не удалось удалить задачу');
        }
    } catch (error) {
        if (taskItem) {
            taskItem.style.opacity = '1';
            taskItem.style.transform = 'scale(1)';
        }
        showError(error.message || 'Ошибка соединения с сервером');
    }
}

// ============================================
//    ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
//    ============================================

/**
 * Показать/скрыть индикатор загрузки
 * 
 * @param {boolean} show
 */
function showLoading(show) {
    loadingIndicator.style.display = show ? 'block' : 'none';
}

/**
 * Показать сообщение об ошибке
 * 
 * @param {string} message
 */
function showError(message) {
    errorMessage.textContent = message;
    errorMessage.style.display = 'block';
    // Автоматически скрываем через 5 секунд
    clearTimeout(errorMessage._timeout);
    errorMessage._timeout = setTimeout(() => {
        errorMessage.style.display = 'none';
    }, 5000);
}

/**
 * Скрыть сообщение об ошибке
 */
function hideError() {
    errorMessage.style.display = 'none';
    clearTimeout(errorMessage._timeout);
}

/**
 * Экранирование HTML (защита от XSS на клиенте)
 * 
 * @param {string} text
 * @returns {string}
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================
//    ОБРАБОТЧИКИ СОБЫТИЙ
//    ============================================

// Сабмит формы добавления задачи
addTaskForm.addEventListener('submit', function (e) {
    e.preventDefault();
    addTask();
});

// Кнопки фильтрации
filterButtons.forEach(btn => {
    btn.addEventListener('click', function () {
        // Обновляем активную кнопку
        filterButtons.forEach(b => b.classList.remove('active'));
        this.classList.add('active');

        // Загружаем задачи с фильтром
        const filter = this.dataset.filter;
        loadTasks(filter);
    });
});

// ============================================
//    ИНИЦИАЛИЗАЦИЯ
//    ============================================

// При загрузке страницы — загружаем все задачи
document.addEventListener('DOMContentLoaded', () => {
    // Фокус на поле ввода
    taskTitleInput.focus();
});