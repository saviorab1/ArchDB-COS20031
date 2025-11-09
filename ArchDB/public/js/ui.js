// UI utility functions

// Notification system
function showNotification(message, type = 'info') {
    // Create notification container if it doesn't exist
    let container = document.getElementById('notification-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notification-container';
        container.className = 'notification-container';
        document.body.appendChild(container);
    }

    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" class="notification-close">&times;</button>
    `;

    container.appendChild(notification);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Loading spinner
function showLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = `
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <p>Loading...</p>
            </div>
        `;
    }
}

function hideLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = '';
    }
}

// Confirm dialog
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
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

// Enhanced form validation display
function showFormErrors(formId, errors) {
    // Clear previous errors
    const existingErrors = document.querySelectorAll('.form-error');
    existingErrors.forEach(error => error.remove());

    // Add new errors
    const form = document.getElementById(formId);
    if (!form) return;

    errors.forEach(error => {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'form-error';
        errorDiv.textContent = error;
        errorDiv.style.cssText = `
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            font-weight: 500;
        `;

        // Find the first input field to place error after
        const firstInput = form.querySelector('input, select, textarea');
        if (firstInput) {
            firstInput.parentNode.insertBefore(errorDiv, firstInput.nextSibling);
        }
    });
}

// Clear form errors
function clearFormErrors(formId) {
    const errors = document.querySelectorAll('.form-error');
    errors.forEach(error => error.remove());
}

// Toggle element visibility
function toggleVisibility(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.style.display = element.style.display === 'none' ? 'block' : 'none';
    }
}

// Smooth scroll to element
function scrollToElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Debounce function for search inputs
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
