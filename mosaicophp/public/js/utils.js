// Funciones utilitarias
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

function handleKeyPress(event) {
    if (event.key === 'Enter') {
        addItem();
    }
} 