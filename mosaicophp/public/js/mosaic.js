// Agregar estas funciones al inicio del archivo
function handleMouseMove(event, tooltip) {
    if (!tooltip) return;
    
    const padding = 10; // Espacio entre el cursor y el tooltip
    
    // Obtener las dimensiones y posición de la ventana
    const windowWidth = window.innerWidth;
    const windowHeight = window.innerHeight;
    
    // Obtener las dimensiones del tooltip
    const tooltipRect = tooltip.getBoundingClientRect();
    const tooltipWidth = tooltipRect.width;
    const tooltipHeight = tooltipRect.height;
    
    // Calcular la posición del tooltip
    let left = event.clientX + padding;
    let top = event.clientY + padding;
    
    // Ajustar si el tooltip se sale por la derecha
    if (left + tooltipWidth > windowWidth) {
        left = event.clientX - tooltipWidth - padding;
    }
    
    // Ajustar si el tooltip se sale por abajo
    if (top + tooltipHeight > windowHeight) {
        top = event.clientY - tooltipHeight - padding;
    }
    
    // Aplicar la posición
    tooltip.style.left = `${left}px`;
    tooltip.style.top = `${top}px`;
    tooltip.style.display = 'block';
}

// También agregar esta función para ocultar el tooltip
function handleMouseLeave(tooltip) {
    if (tooltip) {
        tooltip.style.display = 'none';
    }
}

// Funciones relacionadas con el mosaico
function updateMosaic(items) {
    const mosaicDiv = document.getElementById('mosaic');
    if (!mosaicDiv) return;

    items.sort((a, b) => b.percentage - a.percentage);

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
                             onmouseleave="handleMouseLeave(this.querySelector('.mosaic-tooltip'))"
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