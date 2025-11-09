// Form validation functions

// Email validation
function validateEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

// Phone number validation (basic)
function validatePhone(phone) {
    const regex = /^[\+]?[1-9][\d]{0,15}$/;
    return regex.test(phone.replace(/[\s\-\(\)]/g, ''));
}

// Date validation (not in future)
function validatePastDate(date) {
    if (!date) return false;
    const inputDate = new Date(date);
    const today = new Date();
    today.setHours(23, 59, 59, 999); // End of today
    return inputDate <= today;
}

// Competition date validation (not in past if upcoming)
function validateCompetitionDate(date, isUpcoming = true) {
    if (!date) return false;
    const inputDate = new Date(date);
    const today = new Date();
    today.setHours(0, 0, 0, 0); // Start of today

    if (isUpcoming) {
        return inputDate >= today;
    }
    return inputDate <= today;
}

// Form validation for archer creation/editing
function validateArcherForm(formData) {
    const errors = [];

    if (!formData.firstName?.trim()) {
        errors.push('First name is required');
    }

    if (!formData.lastName?.trim()) {
        errors.push('Last name is required');
    }

    if (!['M', 'F'].includes(formData.gender)) {
        errors.push('Please select a valid gender');
    }

    if (!formData.dateOfBirth || !validatePastDate(formData.dateOfBirth)) {
        errors.push('Please enter a valid birth date');
    }

    if (formData.email && !validateEmail(formData.email)) {
        errors.push('Please enter a valid email address');
    }

    if (formData.phoneNumber && !validatePhone(formData.phoneNumber)) {
        errors.push('Please enter a valid phone number');
    }

    return errors;
}

// Form validation for score entry
function validateScoreForm(formData) {
    const errors = [];

    if (!formData.archerId) {
        errors.push('Please select an archer');
    }

    if (!formData.roundId) {
        errors.push('Please select a round');
    }

    if (!Array.isArray(formData.arrowScores) || formData.arrowScores.length !== 6) {
        errors.push('Please enter scores for all 6 arrows');
    } else if (!validateScoreEntry(formData.arrowScores)) {
        errors.push('Arrow scores must be between 0 and 10');
    }

    if (!formData.scoreDate || !validatePastDate(formData.scoreDate)) {
        errors.push('Please enter a valid date');
    }

    return errors;
}

// Form validation for competition creation
function validateCompetitionForm(formData) {
    const errors = [];

    if (!formData.name?.trim()) {
        errors.push('Competition name is required');
    }

    if (!formData.date || !validateCompetitionDate(formData.date)) {
        errors.push('Please enter a valid future date');
    }

    if (!formData.location?.trim()) {
        errors.push('Location is required');
    }

    if (!formData.roundId) {
        errors.push('Please select a round');
    }

    return errors;
}
