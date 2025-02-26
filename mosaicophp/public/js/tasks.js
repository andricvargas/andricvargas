// Variables globales
let currentItemId = null;

// Funciones principales
function openTaskModal(itemId, itemName) {
    console.log('Abriendo modal para item:', itemId, itemName);
    currentItemId = itemId;
    const modal = document.getElementById('taskModal');
    const modalTitle = document.getElementById('modalTitle');
    
    if (!modal || !modalTitle) {
        console.error('No se encontraron elementos del modal');
        return;
    }
    
    modalTitle.textContent = `Tareas - ${itemName}`;
    modal.style.display = 'block';
    
    // Limpiar el input de nueva tarea
    const newTaskInput = document.getElementById('newTask');
    if (newTaskInput) {
        newTaskInput.value = '';
    }
    
    // Cargar las tareas existentes
    loadTasks(itemId);
}

function closeTaskModal() {
    const modal = document.getElementById('taskModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

async function loadTasks(itemId) {
    try {
        const response = await fetch(`${BASE_URL}/api/items/${itemId}/tasks`);
        const tasks = await response.json();
        updateTaskList(tasks);
    } catch (error) {
        console.error('Error cargando tareas:', error);
        showMessage('Error al cargar las tareas', false);
    }
}

function updateTaskList(tasks) {
    const taskList = document.getElementById('taskList');
    if (!taskList) return;

    taskList.innerHTML = tasks.map(task => `
        <div class="task-item ${task.completed ? 'completed' : ''}">
            <input type="checkbox" 
                   ${task.completed ? 'checked' : ''} 
                   onchange="toggleTask(${task.id}, this.checked)">
            <span class="task-description" 
                  onclick="editTask(this, ${task.id})"
                  title="Haz clic para editar">${task.description}</span>
            <span class="delete-task" onclick="deleteTask(${task.id})">✖</span>
        </div>
    `).join('');
}

async function addTask() {
    const input = document.getElementById('newTask');
    if (!input) return;

    const description = input.value.trim();
    if (!description) return;

    try {
        const response = await fetch(`${BASE_URL}/api/items/${currentItemId}/tasks`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ description })
        });

        if (!response.ok) throw new Error('Error al agregar la tarea');

        const tasks = await response.json();
        updateTaskList(tasks);
        input.value = '';
    } catch (error) {
        console.error('Error:', error);
        showMessage('Error al agregar la tarea', false);
    }
}

function editTask(element, taskId) {
    const currentText = element.textContent;
    const input = document.createElement('input');
    input.type = 'text';
    input.value = currentText;
    input.className = 'task-edit-input';
    
    element.parentNode.replaceChild(input, element);
    input.focus();
    
    input.onblur = () => finishEditing(input, element, taskId);
    input.onkeydown = (e) => {
        if (e.key === 'Enter') {
            input.blur();
        } else if (e.key === 'Escape') {
            element.textContent = currentText;
            input.parentNode.replaceChild(element, input);
        }
    };
}

async function finishEditing(input, element, taskId) {
    const newDescription = input.value.trim();
    if (!newDescription) {
        input.parentNode.replaceChild(element, input);
        return;
    }

    try {
        const response = await fetch(`${BASE_URL}/api/tasks/${taskId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                description: newDescription
            })
        });

        if (!response.ok) throw new Error('Error al actualizar la tarea');

        const tasks = await response.json();
        updateTaskList(tasks);
    } catch (error) {
        console.error('Error:', error);
        showMessage('Error al actualizar la tarea', false);
        input.parentNode.replaceChild(element, input);
    }
}

async function toggleTask(taskId, completed) {
    try {
        const response = await fetch(`${BASE_URL}/api/tasks/${taskId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ completed })
        });

        if (!response.ok) throw new Error('Error al actualizar la tarea');

        const tasks = await response.json();
        updateTaskList(tasks);
    } catch (error) {
        console.error('Error:', error);
        showMessage('Error al actualizar la tarea', false);
    }
}

async function deleteTask(taskId) {
    if (!confirm('¿Está seguro de eliminar esta tarea?')) return;

    try {
        const response = await fetch(`${BASE_URL}/api/tasks/${taskId}`, {
            method: 'DELETE'
        });

        if (!response.ok) throw new Error('Error al eliminar la tarea');

        const tasks = await response.json();
        updateTaskList(tasks);
    } catch (error) {
        console.error('Error:', error);
        showMessage('Error al eliminar la tarea', false);
    }
}

function toggleCompletedTasks() {
    const showCompleted = document.getElementById('showCompleted');
    if (!showCompleted) return;

    const taskItems = document.querySelectorAll('.task-item.completed');
    taskItems.forEach(item => {
        item.classList.toggle('show-completed', showCompleted.checked);
    });
}

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar el input de nueva tarea
    const newTaskInput = document.getElementById('newTask');
    if (newTaskInput) {
        newTaskInput.addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                addTask();
            }
        });
    }

    // Manejar la tecla Escape para cerrar el modal
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeTaskModal();
        }
    });
}); 