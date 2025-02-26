// Funciones relacionadas con la API
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

        const text = await response.text();
        console.log('Status:', response.status);
        console.log('Respuesta:', text);

        if (!response.ok) {
            const errorData = text ? JSON.parse(text) : {};
            throw new Error(errorData.error || `Error del servidor: ${response.status}`);
        }

        if (!text) {
            throw new Error('Respuesta vacía del servidor');
        }

        const data = JSON.parse(text);
        if (Array.isArray(data)) {
            updateTable(data);
            updateMosaic(data);
        } else if (data.error) {
            throw new Error(data.error);
        } else {
            throw new Error('Formato de respuesta inválido');
        }
    } catch (error) {
        console.error('Error actualizando nombre:', error);
        alert('Error al actualizar el nombre: ' + error.message);
    }
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