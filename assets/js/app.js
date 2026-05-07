const API = {
    async request(endpoint, method='GET', data=null) {

        // @SECURITY_MODULE (CSRF)

        let options = {
            method,
            headers: {'Content-Type': 'application/json'} // отправляем JSON
        };

        if (data) options.body = JSON.stringify(data); // тело запроса

        let res = await fetch(endpoint, options); // fetch

        if (!res.ok) throw new Error("Ошибка сети");

        return res.json(); // парсим JSON
    }
};

// загрузка задач
async function loadTasks(filter='all') {
    let res = await API.request(`/api/task/list?filter=${filter}`);
    renderTasks(res.data);
}

// отрисовка списка
function renderTasks(tasks) {
    const list = document.getElementById('taskList');
    list.innerHTML = '';

    if (!tasks.length) {
        list.innerHTML = '<p>Нет задач</p>';
        return;
    }

    tasks.forEach(t => {
        let li = document.createElement('li');

        li.innerHTML = `
            <input type="checkbox" ${t.is_completed ? 'checked':''}>
            <span style="${t.is_completed ? 'text-decoration:line-through':''}">
                ${t.title}
            </span>
            <button>×</button>
        `;

        // события
        li.querySelector('input').onclick = () => toggleTask(t.id);
        li.querySelector('button').onclick = () => deleteTask(t.id);

        list.appendChild(li);
    });
}

// добавление задачи
async function addTask() {
    let input = document.getElementById('taskInput');
    await API.request('/api/task/add','POST',{title:input.value});
    input.value='';
    loadTasks();
}

// переключение статуса
async function toggleTask(id) {
    await API.request('/api/task/toggle','POST',{id});
    loadTasks();
}

// удаление задачи
async function deleteTask(id) {
    await API.request('/api/task/delete','POST',{id});
    loadTasks();
}

// инициализация
document.addEventListener('DOMContentLoaded', () => {
    loadTasks();

    document.getElementById('taskForm').onsubmit = e => {
        e.preventDefault(); // отмена перезагрузки
        addTask();
    };

    document.querySelectorAll('.filters button').forEach(btn => {
        btn.onclick = () => loadTasks(btn.dataset.filter);
    });
});