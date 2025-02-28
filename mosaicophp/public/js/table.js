// Funciones relacionadas con la tabla
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