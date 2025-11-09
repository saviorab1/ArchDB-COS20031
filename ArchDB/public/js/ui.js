// UI utility functions

// Notification system
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <span>${message}</span>
        <button onclick="this.parentElement.remove()">&times;</button>
    `;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 5000);
}

// Loading spinner
function showLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = '<div class="loading">Loading...</div>';
    }
}

function hideLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = '';
    }
}

// Format date for display
function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-AU', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Format score display
function formatScoreDisplay(arrowScores, totalPoints) {
    if (!Array.isArray(arrowScores)) return '';
    const arrows = arrowScores.map(score => score || 0).join(', ');
    return `${arrows} (Total: ${totalPoints || 0})`;
}

// Update category display in real-time
function updateCategoryDisplay() {
    const gender = document.getElementById('gender')?.value;
    const birthDate = document.getElementById('birthDate')?.value;
    const equipment = document.getElementById('equipment')?.value || 'RECURVE';

    if (gender && birthDate) {
        const category = determineCategory(gender, birthDate, equipment);
        const displayElement = document.getElementById('category-display');
        if (displayElement) {
            displayElement.textContent = category;
        }
    }
}

// Real-time score calculation for score entry
function updateScoreTotal() {
    const arrowInputs = document.querySelectorAll('.arrow-input');
    const scores = Array.from(arrowInputs).map(input => parseInt(input.value) || 0);
    const total = calculateTotalPoints(scores);

    const totalDisplay = document.getElementById('score-total');
    if (totalDisplay) {
        totalDisplay.textContent = `Total: ${total}`;
    }
}
