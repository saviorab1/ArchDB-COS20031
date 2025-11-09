// Core archery business logic functions

// Calculate total points from arrow scores
function calculateTotalPoints(arrowScores) {
    if (!Array.isArray(arrowScores)) return 0;
    return arrowScores.reduce((sum, score) => sum + (parseInt(score) || 0), 0);
}

// Validate single arrow score (0-10)
function validateArrowScore(score) {
    const num = parseInt(score);
    return !isNaN(num) && num >= 0 && num <= 10;
}

// Validate complete 6-arrow score entry
function validateScoreEntry(scores) {
    if (!Array.isArray(scores) || scores.length !== 6) return false;
    return scores.every(score => validateArrowScore(score));
}

// Calculate age from birth date
function calculateAge(birthDate) {
    if (!birthDate) return 0;
    const today = new Date();
    const birth = new Date(birthDate);
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--;
    }
    return age;
}

// Determine archer category from age, gender, and equipment
function determineCategory(gender, birthDate, equipment = 'RECURVE') {
    const age = calculateAge(birthDate);
    let ageGroup = 'Open';

    if (age < 14) ageGroup = 'Under 14';
    else if (age < 16) ageGroup = 'Under 16';
    else if (age < 18) ageGroup = 'Under 18';
    else if (age < 21) ageGroup = 'Under 21';
    else if (age >= 70) ageGroup = '70+';
    else if (age >= 60) ageGroup = '60+';
    else if (age >= 50) ageGroup = '50+';

    const genderLabel = gender === 'M' ? 'Male' : 'Female';
    return `${ageGroup} ${genderLabel} ${equipment}`;
}

// Find personal best for a specific round
function findPersonalBest(scores, roundId = null) {
    if (!Array.isArray(scores) || scores.length === 0) return null;

    let filteredScores = scores;
    if (roundId) {
        filteredScores = scores.filter(s => s.roundId === roundId);
    }

    return filteredScores.reduce((max, score) =>
        (score.totalPoints || 0) > (max.totalPoints || 0) ? score : max, {});
}

// Find club best for a round
function findClubBest(allScores, roundId) {
    if (!Array.isArray(allScores) || !roundId) return null;

    const roundScores = allScores.filter(s => s.roundId === roundId);
    return findPersonalBest(roundScores);
}

// Rank scores within a competition (highest first)
function rankCompetitionScores(scores) {
    if (!Array.isArray(scores)) return [];

    return scores
        .sort((a, b) => (b.totalPoints || 0) - (a.totalPoints || 0))
        .map((score, index) => ({
            ...score,
            rank: index + 1
        }));
}

// Group scores by category for competitions
function groupScoresByCategory(scores) {
    if (!Array.isArray(scores)) return {};

    return scores.reduce((groups, score) => {
        const category = score.category || 'Unknown';
        if (!groups[category]) groups[category] = [];
        groups[category].push(score);
        return groups;
    }, {});
}

// Filter scores by date range
function filterScoresByDate(scores, startDate, endDate) {
    if (!Array.isArray(scores)) return [];

    return scores.filter(score => {
        const scoreDate = new Date(score.scoreDate);
        const start = startDate ? new Date(startDate) : null;
        const end = endDate ? new Date(endDate) : null;

        if (start && scoreDate < start) return false;
        if (end && scoreDate > end) return false;
        return true;
    });
}

// Sort scores by different criteria
function sortScores(scores, sortBy = 'date', direction = 'desc') {
    if (!Array.isArray(scores)) return [];

    return [...scores].sort((a, b) => {
        let aVal, bVal;

        switch (sortBy) {
            case 'score':
                aVal = a.totalPoints || 0;
                bVal = b.totalPoints || 0;
                break;
            case 'date':
                aVal = new Date(a.scoreDate);
                bVal = new Date(b.scoreDate);
                break;
            case 'archer':
                aVal = (a.archerName || '').toLowerCase();
                bVal = (b.archerName || '').toLowerCase();
                break;
            default:
                return 0;
        }

        if (direction === 'asc') {
            return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
        } else {
            return aVal < bVal ? 1 : aVal > bVal ? -1 : 0;
        }
    });
}
