/**
 * Tournament Bracket JavaScript
 * 
 * Handles interactive bracket visualization, match updates,
 * and real-time tournament management for GSCMS.
 */

class TournamentBracket {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        this.tournament = null;
        this.matches = [];
        this.brackets = [];
        this.selectedMatch = null;
        this.isAdmin = options.isAdmin || false;
        this.autoRefresh = options.autoRefresh || false;
        
        this.options = {
            teamWidth: 180,
            scoreWidth: 40,
            matchMargin: 20,
            roundMargin: 60,
            backgroundColor: '#f8f9fa',
            teamBgColor: '#ffffff',
            teamFontSize: 14,
            scoreFontSize: 16,
            ...options
        };
        
        this.init();
    }
    
    init() {
        if (!this.container) {
            console.error('Tournament bracket container not found');
            return;
        }
        
        this.container.innerHTML = '<div class="bracket-loading">Loading tournament bracket...</div>';
        
        // Setup event listeners
        this.setupEventListeners();
        
        // Start auto-refresh if enabled
        if (this.autoRefresh) {
            this.startAutoRefresh();
        }
    }
    
    setupEventListeners() {
        // Modal events
        const updateScoreBtn = document.getElementById('update-score-btn');
        if (updateScoreBtn) {
            updateScoreBtn.addEventListener('click', () => this.handleScoreUpdate());
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
            }
            if (e.key === 'r' && e.ctrlKey) {
                e.preventDefault();
                this.refreshBracket();
            }
        });
        
        // Window resize
        window.addEventListener('resize', debounce(() => {
            this.renderBracket();
        }, 300));
    }
    
    /**
     * Load tournament data from server
     */
    async loadTournament(tournamentId) {
        try {
            const response = await fetch(`/api/tournaments/${tournamentId}/bracket`);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            this.tournament = data.tournament;
            this.brackets = data.brackets;
            this.matches = data.matches;
            
            this.renderBracket();
            
        } catch (error) {
            console.error('Error loading tournament:', error);
            this.showError('Failed to load tournament data');
        }
    }
    
    /**
     * Render the tournament bracket
     */
    renderBracket() {
        if (!this.tournament || !this.matches.length) {
            this.container.innerHTML = '<div class="bracket-empty">No tournament data available</div>';
            return;
        }
        
        if (this.tournament.tournament_type === 'round_robin') {
            this.renderRoundRobinView();
        } else {
            this.renderEliminationBracket();
        }
    }
    
    /**
     * Render elimination bracket
     */
    renderEliminationBracket() {
        const bracketData = this.organizeBracketData();
        
        this.container.innerHTML = `
            <div class="bracket-header">
                <h3>${this.tournament.tournament_name}</h3>
                <div class="tournament-info">
                    <span class="badge bg-primary">${this.tournament.category_name}</span>
                    <span class="badge bg-info">${this.tournament.status}</span>
                    <span class="badge bg-secondary">${bracketData.totalTeams} teams</span>
                </div>
            </div>
            <div class="bracket-controls">
                <button class="btn btn-sm btn-outline-primary" onclick="window.bracket.refreshBracket()">
                    <i class="fas fa-sync"></i> Refresh
                </button>
                <button class="btn btn-sm btn-outline-success" onclick="window.bracket.exportBracket()">
                    <i class="fas fa-download"></i> Export
                </button>
                ${this.isAdmin ? '<button class="btn btn-sm btn-outline-warning" onclick="window.bracket.printBracket()"><i class="fas fa-print"></i> Print</button>' : ''}
            </div>
            <div class="bracket-container">
                <div class="bracket-rounds"></div>
            </div>
        `;
        
        this.renderRounds(bracketData);
        this.addBracketStyling();
    }
    
    /**
     * Organize bracket data by rounds
     */
    organizeBracketData() {
        const rounds = {};
        let totalTeams = 0;
        
        // Group matches by round
        this.matches.forEach(match => {
            const roundNum = match.round_number;
            if (!rounds[roundNum]) {
                rounds[roundNum] = {
                    number: roundNum,
                    name: match.round_name || `Round ${roundNum}`,
                    matches: []
                };
            }
            rounds[roundNum].matches.push(match);
            
            // Count teams (approximate)
            if (roundNum === 1) {
                totalTeams += 2;
            }
        });
        
        return {
            rounds: Object.values(rounds).sort((a, b) => a.number - b.number),
            totalTeams: totalTeams
        };
    }
    
    /**
     * Render tournament rounds
     */
    renderRounds(bracketData) {
        const roundsContainer = this.container.querySelector('.bracket-rounds');
        
        bracketData.rounds.forEach((round, roundIndex) => {
            const roundDiv = document.createElement('div');
            roundDiv.className = 'bracket-round';
            roundDiv.innerHTML = `
                <div class="round-header">
                    <h5>${round.name}</h5>
                </div>
                <div class="round-matches"></div>
            `;
            
            const matchesContainer = roundDiv.querySelector('.round-matches');
            
            round.matches.forEach(match => {
                const matchElement = this.createMatchElement(match);
                matchesContainer.appendChild(matchElement);
            });
            
            roundsContainer.appendChild(roundDiv);
        });
    }
    
    /**
     * Create match element
     */
    createMatchElement(match) {
        const matchDiv = document.createElement('div');
        matchDiv.className = `bracket-match match-${match.match_status}`;
        matchDiv.dataset.matchId = match.id;
        
        const team1Info = this.getTeamDisplayInfo(match, 'team1');
        const team2Info = this.getTeamDisplayInfo(match, 'team2');
        
        matchDiv.innerHTML = `
            <div class="match-teams">
                <div class="team ${team1Info.isWinner ? 'winner' : ''} ${team1Info.isEmpty ? 'empty' : ''}">
                    <span class="team-name">${team1Info.name}</span>
                    <span class="team-score">${team1Info.score}</span>
                    ${team1Info.seed ? `<span class="team-seed">${team1Info.seed}</span>` : ''}
                </div>
                <div class="team ${team2Info.isWinner ? 'winner' : ''} ${team2Info.isEmpty ? 'empty' : ''}">
                    <span class="team-name">${team2Info.name}</span>
                    <span class="team-score">${team2Info.score}</span>
                    ${team2Info.seed ? `<span class="team-seed">${team2Info.seed}</span>` : ''}
                </div>
            </div>
            <div class="match-info">
                <span class="match-status badge bg-${this.getStatusBadgeClass(match.match_status)}">${match.match_status}</span>
                ${match.table_number ? `<span class="table-number">Table ${match.table_number}</span>` : ''}
            </div>
        `;
        
        // Add click handler
        matchDiv.addEventListener('click', () => this.handleMatchClick(match));
        
        // Add visual indicators
        if (match.match_status === 'in_progress') {
            matchDiv.classList.add('live');
        }
        
        return matchDiv;
    }
    
    /**
     * Get team display information
     */
    getTeamDisplayInfo(match, teamPrefix) {
        const teamId = match[`${teamPrefix}_id`];
        const teamName = match[`${teamPrefix}_name`];
        const teamScore = match[`${teamPrefix}_score`];
        const teamSeed = match[`${teamPrefix}_seed`];
        const winnerId = match.winner_team_id;
        
        return {
            name: teamName || 'TBD',
            score: teamScore !== null ? teamScore : '',
            seed: teamSeed,
            isWinner: winnerId && winnerId === teamId,
            isEmpty: !teamName
        };
    }
    
    /**
     * Render round-robin view
     */
    renderRoundRobinView() {
        this.container.innerHTML = `
            <div class="bracket-header">
                <h3>${this.tournament.tournament_name}</h3>
                <div class="tournament-info">
                    <span class="badge bg-primary">${this.tournament.category_name}</span>
                    <span class="badge bg-info">${this.tournament.status}</span>
                </div>
            </div>
            <div class="round-robin-container">
                <div class="standings-section">
                    <h4>Current Standings</h4>
                    <div id="standings-table"></div>
                </div>
                <div class="schedule-section">
                    <h4>Match Schedule</h4>
                    <div id="schedule-grid"></div>
                </div>
            </div>
        `;
        
        this.loadRoundRobinData();
    }
    
    /**
     * Load round-robin data
     */
    async loadRoundRobinData() {
        try {
            const [standingsResponse, scheduleResponse] = await Promise.all([
                fetch(`/api/tournaments/${this.tournament.id}/standings`),
                fetch(`/api/tournaments/${this.tournament.id}/schedule`)
            ]);
            
            const standings = await standingsResponse.json();
            const schedule = await scheduleResponse.json();
            
            this.renderStandingsTable(standings);
            this.renderScheduleGrid(schedule);
            
        } catch (error) {
            console.error('Error loading round-robin data:', error);
        }
    }
    
    /**
     * Render standings table
     */
    renderStandingsTable(standings) {
        const container = document.getElementById('standings-table');
        
        let html = `
            <table class="table table-sm standings-table">
                <thead>
                    <tr>
                        <th>Pos</th>
                        <th>Team</th>
                        <th>P</th>
                        <th>W</th>
                        <th>D</th>
                        <th>L</th>
                        <th>F</th>
                        <th>A</th>
                        <th>GD</th>
                        <th>Pts</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        standings.forEach((team, index) => {
            const qualified = index < 3 ? 'qualified' : '';
            const medalIcon = index < 3 ? ['🥇', '🥈', '🥉'][index] : '';
            
            html += `
                <tr class="${qualified}">
                    <td>${medalIcon} ${index + 1}</td>
                    <td class="team-name">
                        <strong>${team.team_name}</strong>
                        <small class="text-muted d-block">${team.school_name}</small>
                    </td>
                    <td>${team.matches_played}</td>
                    <td>${team.wins}</td>
                    <td>${team.draws}</td>
                    <td>${team.losses}</td>
                    <td>${team.points_for}</td>
                    <td>${team.points_against}</td>
                    <td>${team.point_differential > 0 ? '+' : ''}${team.point_differential}</td>
                    <td><strong>${team.league_points}</strong></td>
                </tr>
            `;
        });
        
        html += '</tbody></table>';
        container.innerHTML = html;
    }
    
    /**
     * Handle match click
     */
    handleMatchClick(match) {
        this.selectedMatch = match;
        
        if (match.match_status === 'bye' || !match.team1_id || !match.team2_id) {
            return; // No action for bye matches or incomplete matches
        }
        
        this.showMatchModal(match);
    }
    
    /**
     * Show match details modal
     */
    showMatchModal(match) {
        const modal = document.getElementById('matchModal');
        if (!modal) {
            console.error('Match modal not found');
            return;
        }
        
        // Populate modal
        document.getElementById('match-title').textContent = `${match.team1_name} vs ${match.team2_name}`;
        document.getElementById('match-round').textContent = match.round_name || `Round ${match.round_number}`;
        document.getElementById('match-status').textContent = match.match_status;
        
        if (match.scheduled_time) {
            document.getElementById('match-time').textContent = new Date(match.scheduled_time).toLocaleString();
        }
        
        if (match.table_number) {
            document.getElementById('match-table').textContent = match.table_number;
        }
        
        // Show scores if completed
        if (match.match_status === 'completed') {
            document.getElementById('match-score').textContent = `${match.team1_score} - ${match.team2_score}`;
            document.getElementById('winner-team').textContent = match.winner_name || 'Draw';
        }
        
        // Show/hide score input based on admin status and match status
        const scoreInputSection = document.getElementById('score-input-section');
        if (this.isAdmin && ['ready', 'in_progress'].includes(match.match_status)) {
            scoreInputSection.style.display = 'block';
            document.getElementById('team1-score').value = match.team1_score || '';
            document.getElementById('team2-score').value = match.team2_score || '';
        } else {
            scoreInputSection.style.display = 'none';
        }
        
        // Show modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
    
    /**
     * Handle score update
     */
    async handleScoreUpdate() {
        if (!this.selectedMatch || !this.isAdmin) {
            return;
        }
        
        const team1Score = parseFloat(document.getElementById('team1-score').value) || 0;
        const team2Score = parseFloat(document.getElementById('team2-score').value) || 0;
        const forfeit = document.getElementById('forfeit-checkbox').checked;
        const forfeitReason = document.getElementById('forfeit-reason').value;
        
        try {
            const response = await fetch(`/admin/tournaments/matches/${this.selectedMatch.id}/score`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    team1_score: team1Score,
                    team2_score: team2Score,
                    forfeit: forfeit,
                    forfeit_reason: forfeitReason
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('matchModal')).hide();
                
                // Refresh bracket
                await this.refreshBracket();
                
                // Show success message
                this.showSuccess('Score updated successfully');
            } else {
                throw new Error(result.error || 'Failed to update score');
            }
            
        } catch (error) {
            console.error('Error updating score:', error);
            this.showError('Failed to update match score: ' + error.message);
        }
    }
    
    /**
     * Refresh bracket data
     */
    async refreshBracket() {
        if (this.tournament) {
            await this.loadTournament(this.tournament.id);
        }
    }
    
    /**
     * Start auto-refresh
     */
    startAutoRefresh() {
        this.autoRefreshInterval = setInterval(() => {
            this.refreshBracket();
        }, 30000); // Refresh every 30 seconds
    }
    
    /**
     * Stop auto-refresh
     */
    stopAutoRefresh() {
        if (this.autoRefreshInterval) {
            clearInterval(this.autoRefreshInterval);
        }
    }
    
    /**
     * Export bracket as image
     */
    async exportBracket() {
        try {
            // Use html2canvas to capture bracket
            if (typeof html2canvas !== 'undefined') {
                const canvas = await html2canvas(this.container);
                
                // Create download link
                const link = document.createElement('a');
                link.download = `tournament_bracket_${this.tournament.id}.png`;
                link.href = canvas.toDataURL();
                link.click();
            } else {
                // Fallback: open print dialog
                this.printBracket();
            }
        } catch (error) {
            console.error('Error exporting bracket:', error);
            this.showError('Failed to export bracket');
        }
    }
    
    /**
     * Print bracket
     */
    printBracket() {
        const printWindow = window.open('', 'PRINT', 'height=600,width=800');
        
        printWindow.document.write(`
            <html>
            <head>
                <title>Tournament Bracket - ${this.tournament.tournament_name}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .bracket-header { text-align: center; margin-bottom: 20px; }
                    .bracket-round { display: inline-block; vertical-align: top; margin: 0 10px; }
                    .bracket-match { border: 1px solid #ccc; padding: 10px; margin: 10px 0; background: white; }
                    .team { padding: 5px; display: flex; justify-content: space-between; }
                    .team.winner { font-weight: bold; background: #d4edda; }
                    .team.empty { color: #999; font-style: italic; }
                    @media print { body { margin: 10px; } }
                </style>
            </head>
            <body>
                <div class="bracket-header">
                    <h1>${this.tournament.tournament_name}</h1>
                    <p>${this.tournament.category_name} - ${new Date().toLocaleDateString()}</p>
                </div>
                ${this.container.innerHTML}
            </body>
            </html>
        `);
        
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    }
    
    /**
     * Utility methods
     */
    getStatusBadgeClass(status) {
        const statusMap = {
            'pending': 'secondary',
            'ready': 'primary', 
            'in_progress': 'warning',
            'completed': 'success',
            'forfeit': 'danger',
            'bye': 'info'
        };
        
        return statusMap[status] || 'secondary';
    }
    
    addBracketStyling() {
        // Add CSS classes for responsive design
        this.container.classList.add('tournament-bracket');
        
        // Add connection lines between rounds
        this.addConnectionLines();
    }
    
    addConnectionLines() {
        // This would add SVG lines connecting matches between rounds
        // Implementation would depend on specific layout requirements
    }
    
    closeModal() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('matchModal'));
        if (modal) {
            modal.hide();
        }
    }
    
    showError(message) {
        // Show toast or alert
        if (typeof toastr !== 'undefined') {
            toastr.error(message);
        } else {
            alert('Error: ' + message);
        }
    }
    
    showSuccess(message) {
        // Show success toast or alert
        if (typeof toastr !== 'undefined') {
            toastr.success(message);
        } else {
            console.log('Success: ' + message);
        }
    }
    
    destroy() {
        this.stopAutoRefresh();
        this.container.innerHTML = '';
    }
}

/**
 * Utility functions
 */
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

// Global bracket instance for easy access
window.bracket = null;

/**
 * Initialize bracket when DOM is loaded
 */
document.addEventListener('DOMContentLoaded', function() {
    const bracketContainer = document.getElementById('tournament-bracket');
    
    if (bracketContainer) {
        const tournamentId = bracketContainer.dataset.tournamentId;
        const isAdmin = bracketContainer.dataset.isAdmin === 'true';
        const autoRefresh = bracketContainer.dataset.autoRefresh === 'true';
        
        window.bracket = new TournamentBracket('tournament-bracket', {
            isAdmin: isAdmin,
            autoRefresh: autoRefresh
        });
        
        if (tournamentId) {
            window.bracket.loadTournament(tournamentId);
        }
    }
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TournamentBracket;
}