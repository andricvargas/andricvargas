<!-- app/views/index.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mosaico de Porcentajes</title>
    <link rel="icon" type="image/x-icon" href="<?php echo $base_path; ?>/public/src/misc/favicon.ico">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            width: 100%;
        }

        .controls {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }

        .controls input {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .controls button {
            padding: 8px 16px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .controls button:hover {
            background-color: #45a049;
        }

        .layout-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            height: 600px;
            align-items: flex-start;
        }

        .table-container {
            flex: 1;
            min-width: 300px;
            max-width: 500px;
            overflow-y: auto;
            /*max-height: 600px;*/
        }

        .mosaic-container {
            flex: 2;
            min-width: 400px;
            width: 600px;
            height: 600px;
            position: relative;
            background: #f5f5f5;
            padding: 2px;
            border-radius: 4px;
            overflow: hidden;
        }

        #itemsTable {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        #itemsTable th, #itemsTable td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        #itemsTable th {
            background-color: #f5f5f5;
            position: sticky;  /* Mantener los encabezados visibles */
            top: 0;
            z-index: 1;
        }

        /* Definir anchos de columnas */
        #itemsTable th:nth-child(1) { width: 50%; } /* Nombre */
        #itemsTable th:nth-child(2) { width: 45%; } /* Ajuste */
        #itemsTable th:nth-child(3) { 
            width: 40px; /* Ancho fijo para la X */
            min-width: 40px;
        } 

        #itemsTable td {
            padding: 8px;
            white-space: normal;
            word-break: break-word;
        }

        .color-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .color-box {
            width: 30px;
            height: 20px;
            border: 1px solid #000;
            display: inline-block;
        }

        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            display: none;
        }

        .error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }

        .success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }

        #mosaic {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            gap: 2px;
            overflow: visible;
        }

        .mosaic-row {
            display: flex;
            gap: 2px;
            width: 100%;
        }

        .mosaic-item {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            border-radius: 4px;
        }

        .mosaic-item:last-child {
            margin-right: 0;
        }

        .mosaic-item.filler {
            background-color: #f0f0f0;
            border: 1px solid #e0e0e0;
        }

        .mosaic-label {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.0009);
            text-align: center;
            padding: 8px;
            background-color: rgba(0, 0, 0, 0.0009);
            border-radius: 4px;
            width: 90%;
            max-height: 90%;
            overflow: hidden;
            transition: font-size 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .mosaic-name {
            word-wrap: break-word;
            white-space: normal;
            margin-bottom: 4px;
            width: 100%;
        }

        .mosaic-percentage {
            font-size: 0.8em;
            opacity: 0.9;
        }

        .mosaic-item:hover .mosaic-label {
            background-color: rgba(0, 0, 0, 0.5);
        }

        /* Estilos para botones de acción */
        .action-button {
            padding: 6px 12px;
            margin: 0 4px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .delete-button {
            background: none;
            border: none;
            color: #ff4444;
            cursor: pointer;
            font-size: 16px;
            padding: 5px;
            width: 30px;
            min-width: 30px;
        }

        .delete-button:hover {
            color: #cc0000;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            #itemsTable {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }

            .mosaic-row {
                height: 80px;
            }

            .mosaic-label {
                font-size: 12px;
            }
        }

        /* Estilo para el scrollbar (opcional, para mejor apariencia) */
        #mosaic::-webkit-scrollbar {
            height: 8px;
        }

        #mosaic::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        #mosaic::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        #mosaic::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .percentage-slider {
            width: 100%;
            height: 20px;
        }

        .mosaic-item:hover .percentage-slider {
            opacity: 1;
        }

        /* Estilo para el scrollbar de la tabla */
        .table-container::-webkit-scrollbar {
            width: 8px;
        }

        .table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Responsive design */
        @media (max-width: 900px) {
            .layout-container {
                flex-direction: column;
                height: auto;
            }
            
            .mosaic-container {
                width: 100%;
                height: 500px;
            }
            
            .table-container {
                width: 100%;
                max-width: none;
                margin-bottom: 20px;
            }
        }

        .name-cell {
            padding: 0 !important;
        }

        .name-container {
            display: flex;
            align-items: flex-start;
            gap: 5px;
            padding: 5px;
        }

        .name-input {
            flex: 1;
            min-width: 0;
            padding: 5px;
            border: 1px solid transparent;
            background: transparent;
            font-family: inherit;
            font-size: inherit;
            resize: vertical;
            min-height: 50px;
            overflow-y: auto;
        }

        .name-input:hover {
            border-color: #ddd;
        }

        .name-input:focus {
            border-color: #4CAF50;
            outline: none;
            background: white;
        }

        .save-name-button {
            background: none;
            border: none;
            color: #4CAF50;
            cursor: pointer;
            font-size: 16px;
            padding: 5px;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }

        .save-name-button:hover {
            opacity: 1;
        }

        /* Centrar la X en su columna */
        #itemsTable td:last-child {
            text-align: center;
            padding: 0;
            width: 40px;
            min-width: 40px;
        }

        #itemsTable th:last-child {
            text-align: center;
            padding: 0;
            width: 40px;
            min-width: 40px;
        }

        .logout-button {
            padding: 8px 16px;
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .logout-button:hover {
            background-color: #d32f2f;
        }

        /* Agregar estilos para el tooltip */
        .mosaic-tooltip {
            position: fixed;
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 1000;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s;
            white-space: nowrap;
            transform: translate(10px, -50%);
        }

        .mosaic-item:hover .mosaic-tooltip {
            opacity: 1;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 8px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .task-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .task-form input {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .task-form button {
            padding: 8px 16px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .task-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .task-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px;
            border-bottom: 1px solid #eee;
        }

        .task-item.completed {
            display: none;
        }

        .task-item.show-completed {
            display: flex;
            opacity: 0.7;
        }

        .task-item input[type="checkbox"] {
            margin: 0;
        }

        .task-item .task-description {
            flex: 1;
        }

        .task-item.completed .task-description {
            text-decoration: line-through;
        }

        .task-item .delete-task {
            color: #ff4444;
            cursor: pointer;
            font-size: 18px;
        }

        .task-filters {
            margin-bottom: 10px;
        }
    </style>
    
   
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1>Mosaico de Porcentajes</h1>
            <div style="display: flex; align-items: center; gap: 10px;">
                <span>Hola <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <button onclick="logout()" class="logout-button">Cerrar Sesión</button>
            </div>
        </div>
        <div id="message" style="display: none;"></div>
        
        <div class="layout-container">
            <div class="table-container">
                <table id="itemsTable">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Ajuste</th>
                            <th>✖</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2">
                                <input type="text" 
                                       id="newItemName" 
                                       placeholder="Agregar nuevo item" 
                                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"
                                       onkeypress="handleKeyPress(event)">
                            </td>
                            <td style="text-align: center;">
                                <button onclick="addItem()" 
                                        style="background: none; border: none; color: #4CAF50; cursor: pointer; font-size: 16px;">
                                    ✓
                                </button>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="mosaic-container">
                <div id="mosaic"></div>
            </div>
        </div>
    </div>

    <div id="taskModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Tareas</h2>
                <button class="btn btn-danger" onclick="closeTaskModal()">Cerrar</button>
            </div>
            <div class="modal-body">
                <div class="task-form">
                    <input type="text" id="newTask" placeholder="Nueva tarea">
                    <button onclick="addTask()">Agregar</button>
                </div>
                <div class="task-filters">
                    <label>
                        <input type="checkbox" id="showCompleted" onchange="toggleCompletedTasks()">
                        Mostrar completadas
                    </label>
                </div>
                <div id="taskList"></div>
            </div>
        </div>
    </div>

    <script src="<?php echo $base_path; ?>/public/js/mosaic.js"></script>
    <script src="<?php echo $base_path; ?>/public/js/tasks.js"></script>
    <script>
        const BASE_URL = '/mosaicophp';
        
        async function loadItems() {
            try {
                const response = await fetch(`${BASE_URL}/api/items`, {
                    headers: {
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new TypeError("La respuesta no es JSON!");
                }

                const items = await response.json();
                if (Array.isArray(items)) {
                    updateTable(items);
                    updateMosaic(items);
                } else {
                    throw new Error('Formato de respuesta inválido');
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Error: ' + error.message, false);
            }
        }

        function updateTable(items) {
            const tbody = document.querySelector('#itemsTable tbody');
            if (!tbody) return;
            
            tbody.innerHTML = items.map(item => `
                <tr>
                    <td class="name-cell">
                        <div class="name-container">
                            <textarea class="name-input"
                                      data-id="${item.id}"
                                      data-original="${item.name}"
                                      rows="2">${item.name}</textarea>
                            <button class="save-name-button" 
                                    onclick="updateItemName(${item.id}, this.previousElementSibling.value)"
                                    title="Guardar nombre">
                                ✓
                            </button>
                        </div>
                    </td>
                    <td>
                        <input type="range" 
                               class="percentage-slider" 
                               value="${item.percentage}" 
                               min="0" 
                               max="100" 
                               step="0.1"
                               data-id="${item.id}"
                               oninput="adjustPercentage(this)"
                               title="Ajustar porcentaje">
                    </td>
                    <td>
                        <button onclick="deleteItem(${item.id})" class="delete-button">✖</button>
                    </td>
                </tr>
            `).join('');
        }

        async function adjustPercentage(slider) {
            const itemId = slider.dataset.id;
            const newPercentage = parseFloat(slider.value);

            try {
                const response = await fetch(`${BASE_URL}/api/items/adjust`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        id: itemId,
                        percentage: newPercentage
                    })
                });

                if (!response.ok) {
                    throw new Error('Error al ajustar el porcentaje');
                }

                const data = await response.json();
                if (Array.isArray(data)) {
                    updateTable(data);
                    updateMosaic(data);
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage(error.message, false);
            }
        }

        async function updateItemName(itemId, newName) {
            try {
                if (!newName || newName.trim() === '') {
                    throw new Error('El nombre no puede estar vacío');
                }

                console.log(`Actualizando item ${itemId} con nombre: ${newName}`);
                
                const response = await fetch(`${BASE_URL}/api/items/${itemId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ 
                        name: newName.trim() 
                    }),
                    credentials: 'same-origin'
                });

                // Log de la respuesta
                console.log('Status:', response.status);
                const text = await response.text();
                console.log('Respuesta:', text);

                if (!response.ok) {
                    const errorData = text ? JSON.parse(text) : {};
                    throw new Error(errorData.error || `Error del servidor: ${response.status}`);
                }

                if (!text) {
                    throw new Error('Respuesta vacía del servidor');
                }

                // Intentar parsear el JSON
                try {
                    const data = JSON.parse(text);
                    if (Array.isArray(data)) {
                        updateTable(data);
                        updateMosaic(data);
                    } else if (data.error) {
                        throw new Error(data.error);
                    } else {
                        throw new Error('Formato de respuesta inválido');
                    }
                } catch (e) {
                    console.error('Error parseando JSON:', text);
                    throw new Error('Error en el formato de la respuesta');
                }
            } catch (error) {
                console.error('Error actualizando nombre:', error);
                alert('Error al actualizar el nombre: ' + error.message);
            }
        }

        function updateMosaic(items) {
            const mosaicDiv = document.getElementById('mosaic');
            if (!mosaicDiv) return;

            items.sort((a, b) => b.percentage - a.percentage);

            // Agregar manejador de eventos para el movimiento del mouse
            const handleMouseMove = (e, tooltip) => {
                tooltip.style.left = `${e.clientX}px`;
                tooltip.style.top = `${e.clientY}px`;
            };

            // Función para calcular el ratio de aspecto de un conjunto de rectángulos
            function aspectRatio(row, width) {
                const rowSum = row.reduce((sum, item) => sum + item.percentage, 0);
                if (rowSum === 0) return Number.MAX_VALUE;
                const max = Math.max(...row.map(item => item.percentage));
                const min = Math.min(...row.map(item => item.percentage));
                return (width * width * max) / (rowSum * rowSum);
            }

            // Función para layoutear los items
            function layoutRow(items, startIndex, remainingPercentage, depth = 0) {
                if (startIndex >= items.length) return [];

                let currentRow = [];
                let currentSum = 0;
                let bestRatio = Number.MAX_VALUE;
                let bestLength = 1;

                for (let i = 0; i < items.length - startIndex; i++) {
                    currentRow.push(items[startIndex + i]);
                    currentSum += items[startIndex + i].percentage;
                    const ratio = aspectRatio(currentRow, currentSum / remainingPercentage);

                    if (ratio < bestRatio) {
                        bestRatio = ratio;
                        bestLength = i + 1;
                    }
                }

                const row = items.slice(startIndex, startIndex + bestLength);
                const rowSum = row.reduce((sum, item) => sum + item.percentage, 0);
                const nextRows = layoutRow(items, startIndex + bestLength, remainingPercentage - rowSum, depth + 1);

                return [{
                    items: row,
                    percentage: rowSum
                }, ...nextRows];
            }

            const totalPercentage = items.reduce((sum, item) => sum + item.percentage, 0);
            const rows = layoutRow(items, 0, totalPercentage);

            // Generar HTML
            mosaicDiv.innerHTML = rows.map(row => {
                const rowPercentage = (row.percentage / totalPercentage) * 100;
                return `
                    <div class="mosaic-row" style="height: ${rowPercentage}%">
                        ${row.items.map(item => {
                            const itemPercentage = (item.percentage / row.percentage) * 100;
                            const fontSize = Math.max(12, Math.min(48, item.percentage * 0.8));
                            return `
                                <div class="mosaic-item" 
                                     style="flex: ${itemPercentage}; 
                                            background-color: ${item.color};"
                                     onmousemove="handleMouseMove(event, this.querySelector('.mosaic-tooltip'))"
                                     onclick="openTaskModal(${item.id}, '${item.name.replace(/'/g, "\\'")}')"
                                     data-id="${item.id}"
                                >
                                    <div class="mosaic-tooltip">
                                        ${item.name} (${item.percentage.toFixed(1)}%)
                                    </div>
                                    <div class="mosaic-label"
                                         style="font-size: ${fontSize}px;">
                                        <div class="mosaic-name">${item.name}</div>
                                        <div class="mosaic-percentage">${item.percentage.toFixed(1)}%</div>
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                `;
            }).join('');
        }

        function showMessage(message, isSuccess) {
            const messageDiv = document.getElementById('message');
            if (!messageDiv) return;
            
            messageDiv.textContent = message;
            messageDiv.className = isSuccess ? 'success' : 'error';
            messageDiv.style.display = 'block';
            
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 3000);
        }

        async function addItem() {
            const nameInput = document.getElementById('newItemName');
            const name = nameInput.value.trim();
            
            if (!name) {
                showMessage('Por favor ingrese un nombre para el item', false);
                return;
            }

            try {
                const response = await fetch(`${BASE_URL}/api/items`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ name })
                });

                if (!response.ok) {
                    throw new Error('Error al agregar el item');
                }

                nameInput.value = '';
                await loadItems();
                showMessage('Item agregado exitosamente', true);
            } catch (error) {
                console.error('Error:', error);
                showMessage('Error al agregar el item: ' + error.message, false);
            }
        }

        async function deleteItem(id) {
            if (!confirm('¿Está seguro de eliminar este item?')) {
                return;
            }

            try {
                const response = await fetch(`${BASE_URL}/api/items/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.error || 'Error al eliminar el item');
                }

                const items = await response.json();
                if (Array.isArray(items)) {
                    updateTable(items);
                    updateMosaic(items);
                    showMessage('Item eliminado exitosamente', true);
                } else {
                    throw new Error('Formato de respuesta inválido');
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage(error.message, false);
            }
        }

        async function logout() {
            try {
                const response = await fetch(`${BASE_URL}/api/auth/logout`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    window.location.href = `${BASE_URL}/login`;
                } else {
                    console.error('Error al cerrar sesión');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        // Cargar items al iniciar
        document.addEventListener('DOMContentLoaded', loadItems);

        // Agregar esta nueva función para manejar la tecla Enter
        function handleKeyPress(event) {
            if (event.key === 'Enter') {
                addItem();
            }
        }
    </script>
</body>
</html>