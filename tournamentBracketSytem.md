# TOURNAMENT BRACKET SYSTEM - Detailed Execution Plan
## Overview

Based on your competition structure, the tournament system needs to handle a multi-phase competition with elimination rounds at schools (30 teams/category → 6 teams), followed by finals at Sci-Bono (6 teams → 3 winners). With 4 categories and specific progression rules, this system must support both elimination and round-robin formats while maintaining scoring integrity.

## Competition Structure Analysis

Based on the project documents:
- Phase 1 (School Elimination): Sep 12, 2025 - 30 teams/category → 6 finalists
- Finals (Sci-Bono): Sep 27, 2025 - 6 teams/category → Top 3 winners
- Categories: 4 main categories (JUNIOR, SPIKE, ARDUINO, INVENTOR)
- Team Size: 4 members per team in finals (down from max 6)
- Total Progression: 1080 learners → 216 finalists → 108 winners

## 1. ELIMINATION BRACKET GENERATION
### 1.1 Tournament Database Schema

```sql
-- Tournament structure
CREATE TABLE tournaments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tournament_name VARCHAR(200) NOT NULL,
    competition_phase_id INT NOT NULL,
    tournament_type ENUM('elimination', 'round_robin', 'swiss', 'double_elimination') NOT NULL,
    category_id INT NOT NULL,
    venue_id INT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    max_teams INT NOT NULL,
    current_teams INT DEFAULT 0,
    rounds_total INT NULL,
    current_round INT DEFAULT 0,
    seeding_method ENUM('random', 'performance', 'regional', 'manual') DEFAULT 'performance',
    advancement_count INT NOT NULL, -- How many teams advance
    status ENUM('setup', 'registration', 'seeding', 'active', 'completed') DEFAULT 'setup',
    winner_team_id INT NULL,
    second_place_id INT NULL,
    third_place_id INT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (competition_phase_id) REFERENCES competition_phases(id),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (venue_id) REFERENCES venues(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_phase_category (competition_phase_id, category_id)
);

-- Tournament brackets/rounds
CREATE TABLE tournament_brackets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tournament_id INT NOT NULL,
    bracket_type ENUM('winners', 'losers', 'consolation') DEFAULT 'winners',
    round_number INT NOT NULL,
    round_name VARCHAR(100) NULL, -- 'Quarter-Finals', 'Semi-Finals', etc.
    matches_in_round INT NOT NULL,
    start_datetime DATETIME NULL,
    end_datetime DATETIME NULL,
    status ENUM('pending', 'active', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id),
    UNIQUE KEY unique_round (tournament_id, bracket_type, round_number),
    INDEX idx_tournament_round (tournament_id, round_number)
);

-- Tournament matches
CREATE TABLE tournament_matches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tournament_id INT NOT NULL,
    bracket_id INT NOT NULL,
    match_number INT NOT NULL,
    match_position INT NOT NULL, -- Position in bracket
    team1_id INT NULL,
    team2_id INT NULL,
    team1_seed INT NULL,
    team2_seed INT NULL,
    team1_score DECIMAL(10,2) NULL,
    team2_score DECIMAL(10,2) NULL,
    winner_team_id INT NULL,
    loser_team_id INT NULL,
    next_match_id INT NULL, -- Where winner advances to
    consolation_match_id INT NULL, -- Where loser goes (if applicable)
    venue_id INT NULL,
    table_number VARCHAR(10) NULL,
    scheduled_time DATETIME NULL,
    actual_start_time DATETIME NULL,
    actual_end_time DATETIME NULL,
    match_status ENUM('pending', 'ready', 'in_progress', 'completed', 'forfeit', 'bye') DEFAULT 'pending',
    forfeit_reason TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id),
    FOREIGN KEY (bracket_id) REFERENCES tournament_brackets(id),
    FOREIGN KEY (team1_id) REFERENCES teams(id),
    FOREIGN KEY (team2_id) REFERENCES teams(id),
    FOREIGN KEY (winner_team_id) REFERENCES teams(id),
    FOREIGN KEY (venue_id) REFERENCES venues(id),
    INDEX idx_bracket_match (bracket_id, match_number),
    INDEX idx_schedule (scheduled_time, match_status)
);

-- Team seeding and rankings
CREATE TABLE tournament_seedings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tournament_id INT NOT NULL,
    team_id INT NOT NULL,
    seed_number INT NOT NULL,
    seeding_score DECIMAL(10,2) NULL,
    previous_phase_rank INT NULL,
    district_rank INT NULL,
    elo_rating INT DEFAULT 1200,
    matches_played INT DEFAULT 0,
    matches_won INT DEFAULT 0,
    matches_lost INT DEFAULT 0,
    points_for DECIMAL(10,2) DEFAULT 0.00,
    points_against DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id),
    FOREIGN KEY (team_id) REFERENCES teams(id),
    UNIQUE KEY unique_seed (tournament_id, team_id),
    UNIQUE KEY unique_seed_number (tournament_id, seed_number),
    INDEX idx_tournament_seed (tournament_id, seed_number)
);
```
### 1.2 Bracket Generation Service
```php
// app/Services/BracketGenerator.php
class BracketGenerator {
    
    // Based on competition structure: 30 teams → 6 teams
    const PHASE1_CONFIG = [
        'teams_per_category' => 30,
        'advancement_count' => 6,
        'format' => 'elimination_with_repechage' // Allows second chance
    ];
    
    const FINALS_CONFIG = [
        'teams_per_category' => 6,
        'advancement_count' => 3,
        'format' => 'round_robin' // All play all for fairness
    ];
    
    public function generateEliminationBracket($tournamentId) {
        $tournament = $this->getTournament($tournamentId);
        $teams = $this->getSeededTeams($tournamentId);
        $teamCount = count($teams);
        
        // Calculate number of rounds needed
        $rounds = $this->calculateRounds($teamCount, $tournament->advancement_count);
        
        // Handle byes for non-power-of-2 team counts
        $bracketSize = $this->getNextPowerOfTwo($teamCount);
        $byes = $bracketSize - $teamCount;
        
        // Create bracket structure
        $bracket = $this->createBracketStructure($tournament, $rounds);
        
        // Place teams with proper seeding
        $this->placeTeamsInBracket($bracket, $teams, $byes);
        
        return $bracket;
    }
    
    private function calculateRounds($teamCount, $advancementCount) {
        // For 30 teams to 6 teams, we need special handling
        if ($teamCount == 30 && $advancementCount == 6) {
            // Round 1: 14 matches (28 teams play, 2 get byes)
            // Round 2: 8 matches (16 teams)
            // Round 3: 4 matches (8 teams)
            // Semi-final: 2 matches (4 teams + 2 lucky losers = 6 finalists)
            return [
                'rounds' => 4,
                'special_handling' => 'repechage_for_finals'
            ];
        }
        
        return ceil(log($teamCount / $advancementCount, 2));
    }
    
    private function placeTeamsInBracket($bracket, $teams, $byes) {
        // Use standard seeding to minimize strong teams meeting early
        $seeds = $this->generateSeeding(count($teams));
        
        // Place byes for top seeds
        $byeSeeds = array_slice($seeds, 0, $byes);
        
        // Create first round matches
        $firstRound = $bracket['rounds'][0];
        $matchNumber = 0;
        
        for ($i = 0; $i < count($seeds) / 2; $i++) {
            $seed1 = $seeds[$i];
            $seed2 = $seeds[count($seeds) - 1 - $i];
            
            // Check if either team gets a bye
            if (in_array($seed1, $byeSeeds)) {
                $this->createByeMatch($firstRound, $teams[$seed2 - 1]);
            } elseif (in_array($seed2, $byeSeeds)) {
                $this->createByeMatch($firstRound, $teams[$seed1 - 1]);
            } else {
                $this->createMatch(
                    $firstRound,
                    $teams[$seed1 - 1],
                    $teams[$seed2 - 1],
                    $matchNumber++
                );
            }
        }
    }
    
    public function generateSchoolEliminationBracket($schoolId, $categoryId) {
        // Special handling for school-level elimination
        // 30 teams need to be reduced to 6
        
        $teams = $this->getSchoolTeams($schoolId, $categoryId);
        
        if (count($teams) <= 6) {
            // Direct advancement if 6 or fewer teams
            return $this->directAdvancement($teams);
        }
        
        // Create a modified single-elimination with repechage
        $bracket = [
            'main_bracket' => $this->createMainBracket($teams),
            'repechage_bracket' => $this->createRepechageBracket($teams)
        ];
        
        return $bracket;
    }
    
    private function createRepechageBracket($teams) {
        // Repechage gives losing teams a second chance
        // Used to determine final 2 spots for finals (positions 5-6)
        
        return [
            'purpose' => 'lucky_loser_qualification',
            'spots_available' => 2,
            'eligible_teams' => 'quarter_final_losers'
        ];
    }
}
```
### 1.3 Interactive Bracket UI
```javascript
// public/js/tournament-bracket.js
class TournamentBracket {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.tournament = null;
        this.matches = [];
        this.selectedMatch = null;
    }
    
    init(tournamentId) {
        this.loadTournament(tournamentId);
        this.initBracketDisplay();
        this.initDragDrop();
    }
    
    initBracketDisplay() {
        // Use bracket library for visualization
        this.bracket = new BracketViewer(this.container, {
            teamWidth: 150,
            scoreWidth: 30,
            matchMargin: 20,
            roundMargin: 50,
            backgroundColor: '#f8f9fa',
            teamBgColor: '#ffffff',
            teamFontSize: 12,
            scoreFontSize: 14,
            onMatchClick: (match) => this.handleMatchClick(match),
            onMatchHover: (match) => this.handleMatchHover(match)
        });
    }
    
    renderBracket(data) {
        const bracketData = {
            teams: data.teams.map(team => [team.name, team.seed]),
            results: this.formatResults(data.matches)
        };
        
        this.bracket.render(bracketData);
        this.addCustomStyling();
    }
    
    formatResults(matches) {
        // Convert matches to bracket library format
        const rounds = {};
        
        matches.forEach(match => {
            if (!rounds[match.round]) {
                rounds[match.round] = [];
            }
            
            rounds[match.round].push([
                match.team1_score || null,
                match.team2_score || null
            ]);
        });
        
        return Object.values(rounds);
    }
    
    handleMatchClick(match) {
        this.selectedMatch = match;
        this.showMatchDetails(match);
        
        if (match.status === 'ready') {
            this.enableScoreEntry(match);
        }
    }
    
    showMatchDetails(match) {
        const modal = new bootstrap.Modal(document.getElementById('matchModal'));
        
        $('#match-title').text(`${match.team1_name} vs ${match.team2_name}`);
        $('#match-round').text(match.round_name);
        $('#match-time').text(match.scheduled_time);
        $('#match-table').text(match.table_number);
        $('#match-status').text(match.status);
        
        if (match.status === 'completed') {
            $('#match-score').text(`${match.team1_score} - ${match.team2_score}`);
            $('#winner-team').text(match.winner_name);
        }
        
        modal.show();
    }
    
    updateMatchScore(matchId, team1Score, team2Score) {
        $.ajax({
            url: `/api/tournament/match/${matchId}/score`,
            method: 'PUT',
            data: {
                team1_score: team1Score,
                team2_score: team2Score
            },
            success: (response) => {
                this.updateBracketDisplay(response.match);
                this.checkBracketProgression(response.bracket);
                toastr.success('Score updated successfully');
            }
        });
    }
    
    generatePrintableBracket() {
        const printWindow = window.open('', 'PRINT', 'height=600,width=800');
        
        printWindow.document.write(`
            <html>
            <head>
                <title>Tournament Bracket - ${this.tournament.name}</title>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .bracket { display: flex; }
                    .round { margin: 0 20px; }
                    .match { border: 1px solid #ccc; padding: 10px; margin: 10px 0; }
                    .team { padding: 5px; }
                    .winner { font-weight: bold; background: #d4edda; }
                </style>
            </head>
            <body>
                <h1>${this.tournament.name}</h1>
                <div id="bracket-print"></div>
            </body>
            </html>
        `);
        
        // Render bracket for printing
        this.renderPrintBracket(printWindow.document.getElementById('bracket-print'));
        
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    }
}
```
## 2. ROUND-ROBIN TOURNAMENT SUPPORT
### 2.1 Round-Robin Database Schema

```sql
-- Round-robin specific tables
CREATE TABLE round_robin_standings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tournament_id INT NOT NULL,
    team_id INT NOT NULL,
    matches_played INT DEFAULT 0,
    wins INT DEFAULT 0,
    draws INT DEFAULT 0,
    losses INT DEFAULT 0,
    points_for DECIMAL(10,2) DEFAULT 0.00,
    points_against DECIMAL(10,2) DEFAULT 0.00,
    point_differential DECIMAL(10,2) GENERATED ALWAYS AS (points_for - points_against) STORED,
    league_points INT DEFAULT 0, -- 3 for win, 1 for draw, 0 for loss
    head_to_head JSON NULL, -- Store H2H results for tiebreaking
    ranking INT NULL,
    qualified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id),
    FOREIGN KEY (team_id) REFERENCES teams(id),
    UNIQUE KEY unique_team (tournament_id, team_id),
    INDEX idx_ranking (tournament_id, league_points DESC, point_differential DESC)
);

-- Round-robin schedule
CREATE TABLE round_robin_schedule (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tournament_id INT NOT NULL,
    round_number INT NOT NULL,
    match_day DATE NOT NULL,
    team1_id INT NOT NULL,
    team2_id INT NOT NULL,
    venue_id INT NULL,
    time_slot TIME NULL,
    match_id INT NULL,
    is_played BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id),
    FOREIGN KEY (team1_id) REFERENCES teams(id),
    FOREIGN KEY (team2_id) REFERENCES teams(id),
    FOREIGN KEY (venue_id) REFERENCES venues(id),
    FOREIGN KEY (match_id) REFERENCES tournament_matches(id),
    INDEX idx_round (tournament_id, round_number),
    INDEX idx_day (tournament_id, match_day)
);
```
### 2.2 Round-Robin Generator Service
```php
// app/Services/RoundRobinGenerator.php
class RoundRobinGenerator {
    
    public function generateRoundRobin($tournamentId) {
        $tournament = $this->getTournament($tournamentId);
        $teams = $this->getTeams($tournamentId);
        $teamCount = count($teams);
        
        // For finals: 6 teams in round-robin
        if ($tournament->competition_phase_id == 3) { // Finals phase
            return $this->generateFinalsRoundRobin($teams);
        }
        
        // Standard round-robin algorithm
        $schedule = $this->bergerTables($teams);
        
        // Save schedule to database
        $this->saveSchedule($tournamentId, $schedule);
        
        // Initialize standings
        $this->initializeStandings($tournamentId, $teams);
        
        return $schedule;
    }
    
    private function generateFinalsRoundRobin($teams) {
        // 6 teams play each other once
        // Total matches: 6 * 5 / 2 = 15 matches
        
        $schedule = [];
        $rounds = [];
        
        // Use Berger tables for optimal scheduling
        $teamIds = array_column($teams, 'id');
        
        if (count($teamIds) % 2 != 0) {
            $teamIds[] = null; // Add bye for odd number
        }
        
        $away = array_splice($teamIds, count($teamIds) / 2);
        $home = $teamIds;
        
        for ($round = 0; $round < count($teams) - 1; $round++) {
            for ($i = 0; $i < count($home); $i++) {
                if ($home[$i] !== null && $away[$i] !== null) {
                    $rounds[$round][] = [
                        'home' => $home[$i],
                        'away' => $away[$i]
                    ];
                }
            }
            
            // Rotate teams for next round
            $lastHome = array_pop($home);
            $firstAway = array_shift($away);
            
            array_unshift($home, $firstAway);
            array_push($away, $lastHome);
        }
        
        return $rounds;
    }
    
    private function bergerTables($teams) {
        $n = count($teams);
        $rounds = $n - 1;
        
        if ($n % 2 != 0) {
            $teams[] = ['id' => null, 'name' => 'BYE'];
            $n++;
            $rounds = $n;
        }
        
        $schedule = [];
        
        for ($round = 0; $round < $rounds; $round++) {
            $roundMatches = [];
            
            for ($match = 0; $match < $n / 2; $match++) {
                $home = ($round + $match) % ($n - 1);
                $away = ($n - 1 - $match + $round) % ($n - 1);
                
                if ($match == 0) {
                    $away = $n - 1;
                }
                
                $roundMatches[] = [
                    'round' => $round + 1,
                    'match' => $match + 1,
                    'home' => $teams[$home],
                    'away' => $teams[$away]
                ];
            }
            
            $schedule[] = $roundMatches;
        }
        
        return $schedule;
    }
    
    public function updateStandings($matchId) {
        $match = $this->getMatch($matchId);
        
        if ($match->match_status !== 'completed') {
            return false;
        }
        
        // Determine winner
        $winner = null;
        $loser = null;
        $isDraw = false;
        
        if ($match->team1_score > $match->team2_score) {
            $winner = $match->team1_id;
            $loser = $match->team2_id;
        } elseif ($match->team2_score > $match->team1_score) {
            $winner = $match->team2_id;
            $loser = $match->team1_id;
        } else {
            $isDraw = true;
        }
        
        // Update standings
        $this->updateTeamStanding($match->tournament_id, $match->team1_id, [
            'played' => 1,
            'won' => $winner == $match->team1_id ? 1 : 0,
            'drawn' => $isDraw ? 1 : 0,
            'lost' => $loser == $match->team1_id ? 1 : 0,
            'for' => $match->team1_score,
            'against' => $match->team2_score,
            'points' => $winner == $match->team1_id ? 3 : ($isDraw ? 1 : 0)
        ]);
        
        $this->updateTeamStanding($match->tournament_id, $match->team2_id, [
            'played' => 1,
            'won' => $winner == $match->team2_id ? 1 : 0,
            'drawn' => $isDraw ? 1 : 0,
            'lost' => $loser == $match->team2_id ? 1 : 0,
            'for' => $match->team2_score,
            'against' => $match->team1_score,
            'points' => $winner == $match->team2_id ? 3 : ($isDraw ? 1 : 0)
        ]);
        
        // Recalculate rankings
        $this->recalculateRankings($match->tournament_id);
    }
    
    private function recalculateRankings($tournamentId) {
        $standings = $this->getStandings($tournamentId);
        
        // Sort by: Points, Goal Difference, Goals For, Head-to-Head
        usort($standings, function($a, $b) {
            // First by points
            if ($a->league_points != $b->league_points) {
                return $b->league_points - $a->league_points;
            }
            
            // Then by point differential
            if ($a->point_differential != $b->point_differential) {
                return $b->point_differential - $a->point_differential;
            }
            
            // Then by points for
            if ($a->points_for != $b->points_for) {
                return $b->points_for - $a->points_for;
            }
            
            // Finally by head-to-head
            return $this->compareHeadToHead($a, $b);
        });
        
        // Update rankings
        foreach ($standings as $rank => $team) {
            $this->updateRanking($team->id, $rank + 1);
            
            // Mark qualified teams (top 3 for finals)
            if ($rank < 3) {
                $this->markQualified($team->id);
            }
        }
    }
}
```
### 2.3 Round-Robin Standings Display
```javascript
// public/js/round-robin-standings.js
class RoundRobinStandings {
    constructor() {
        this.tournamentId = null;
        this.standings = [];
        this.schedule = [];
    }
    
    init(tournamentId) {
        this.tournamentId = tournamentId;
        this.loadStandings();
        this.loadSchedule();
        this.initLiveUpdates();
    }
    
    loadStandings() {
        $.ajax({
            url: `/api/tournament/${this.tournamentId}/standings`,
            success: (data) => {
                this.standings = data.standings;
                this.renderStandingsTable();
            }
        });
    }
    
    renderStandingsTable() {
        const table = $('#standings-table tbody');
        table.empty();
        
        this.standings.forEach((team, index) => {
            const qualified = index < 3 ? 'qualified' : '';
            const row = $(`
                <tr class="${qualified}">
                    <td class="rank">${index + 1}</td>
                    <td class="team-name">
                        <img src="${team.logo}" class="team-logo" />
                        ${team.name}
                    </td>
                    <td class="played">${team.matches_played}</td>
                    <td class="won">${team.wins}</td>
                    <td class="drawn">${team.draws}</td>
                    <td class="lost">${team.losses}</td>
                    <td class="goals-for">${team.points_for}</td>
                    <td class="goals-against">${team.points_against}</td>
                    <td class="goal-diff">${team.point_differential > 0 ? '+' : ''}${team.point_differential}</td>
                    <td class="points"><strong>${team.league_points}</strong></td>
                </tr>
            `);
            
            table.append(row);
        });
        
        this.addQualificationIndicators();
    }
    
    renderScheduleGrid() {
        const grid = $('#schedule-grid');
        const teams = this.standings.map(s => s.team_name);
        
        // Create grid header
        let headerRow = '<tr><th></th>';
        teams.forEach(team => {
            headerRow += `<th class="rotate">${team}</th>`;
        });
        headerRow += '</tr>';
        
        grid.append(headerRow);
        
        // Create grid rows
        teams.forEach((homeTeam, homeIndex) => {
            let row = `<tr><th>${homeTeam}</th>`;
            
            teams.forEach((awayTeam, awayIndex) => {
                if (homeIndex === awayIndex) {
                    row += '<td class="diagonal">-</td>';
                } else {
                    const match = this.findMatch(homeTeam, awayTeam);
                    if (match) {
                        row += `<td class="match-cell ${match.status}" 
                                    data-match-id="${match.id}">
                                    ${match.score || '-'}
                                </td>`;
                    } else {
                        row += '<td class="match-cell">-</td>';
                    }
                }
            });
            
            row += '</tr>';
            grid.append(row);
        });
        
        // Make cells clickable
        $('.match-cell').on('click', function() {
            const matchId = $(this).data('match-id');
            if (matchId) {
                this.showMatchDetails(matchId);
            }
        }.bind(this));
    }
    
    initLiveUpdates() {
        // Use Server-Sent Events for live updates
        const eventSource = new EventSource(`/api/tournament/${this.tournamentId}/live`);
        
        eventSource.onmessage = (event) => {
            const data = JSON.parse(event.data);
            
            if (data.type === 'score_update') {
                this.updateMatchScore(data.match);
                this.flashUpdate(data.match.id);
            } else if (data.type === 'standings_update') {
                this.loadStandings();
            }
        };
    }
}
```
## 3. SEEDING BASED ON PERFORMANCE
### 3.1 Seeding Algorithm Implementation

```php
// app/Services/SeedingService.php
class SeedingService {
    
    private $weights = [
        'previous_score' => 0.4,
        'consistency' => 0.2,
        'strength_of_schedule' => 0.2,
        'recent_form' => 0.2
    ];
    
    public function calculateSeeding($tournamentId) {
        $tournament = $this->getTournament($tournamentId);
        $teams = $this->getEligibleTeams($tournament);
        
        $seedingData = [];
        
        foreach ($teams as $team) {
            $seedingData[] = [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'seeding_score' => $this->calculateSeedingScore($team, $tournament),
                'previous_rank' => $this->getPreviousPhaseRank($team),
                'elo_rating' => $this->calculateEloRating($team)
            ];
        }
        
        // Sort by seeding score
        usort($seedingData, function($a, $b) {
            return $b['seeding_score'] <=> $a['seeding_score'];
        });
        
        // Assign seeds
        foreach ($seedingData as $index => &$seed) {
            $seed['seed_number'] = $index + 1;
        }
        
        // Save seeding
        $this->saveSeedingData($tournamentId, $seedingData);
        
        return $seedingData;
    }
    
    private function calculateSeedingScore($team, $tournament) {
        $score = 0;
        
        // Previous phase performance (40%)
        if ($tournament->competition_phase_id > 1) {
            $previousScore = $this->getPreviousPhaseScore($team);
            $score += $previousScore * $this->weights['previous_score'];
        }
        
        // Consistency score (20%)
        $consistency = $this->calculateConsistency($team);
        $score += $consistency * $this->weights['consistency'];
        
        // Strength of schedule (20%)
        $sos = $this->calculateStrengthOfSchedule($team);
        $score += $sos * $this->weights['strength_of_schedule'];
        
        // Recent form (20%)
        $recentForm = $this->calculateRecentForm($team);
        $score += $recentForm * $this->weights['recent_form'];
        
        return $score;
    }
    
    private function calculateEloRating($team) {
        $elo = 1200; // Starting ELO
        $matches = $this->getTeamMatches($team->id);
        
        foreach ($matches as $match) {
            $opponent = $this->getOpponent($match, $team->id);
            $opponentElo = $opponent->elo_rating ?? 1200;
            
            // Calculate expected score
            $expected = 1 / (1 + pow(10, ($opponentElo - $elo) / 400));
            
            // Actual score (1 for win, 0.5 for draw, 0 for loss)
            $actual = $this->getMatchResult($match, $team->id);
            
            // Update ELO
            $k = 32; // K-factor
            $elo += $k * ($actual - $expected);
        }
        
        return round($elo);
    }
    
    public function applyManualSeeding($tournamentId, $adjustments) {
        // Allow admin to manually adjust seeding
        foreach ($adjustments as $adjustment) {
            $this->updateSeeding(
                $tournamentId,
                $adjustment['team_id'],
                $adjustment['new_seed']
            );
        }
        
        // Recalculate bracket with new seeding
        $this->regenerateBracket($tournamentId);
    }
    
    public function simulateBracket($tournamentId) {
        $seeding = $this->getSeeding($tournamentId);
        $simulations = 1000;
        $results = [];
        
        for ($i = 0; $i < $simulations; $i++) {
            $bracket = $this->simulateTournament($seeding);
            
            foreach ($bracket['results'] as $teamId => $placement) {
                if (!isset($results[$teamId])) {
                    $results[$teamId] = [
                        'wins' => 0,
                        'finals' => 0,
                        'top3' => 0
                    ];
                }
                
                if ($placement == 1) $results[$teamId]['wins']++;
                if ($placement <= 2) $results[$teamId]['finals']++;
                if ($placement <= 3) $results[$teamId]['top3']++;
            }
        }
        
        // Calculate probabilities
        foreach ($results as &$teamResult) {
            $teamResult['win_probability'] = ($teamResult['wins'] / $simulations) * 100;
            $teamResult['finals_probability'] = ($teamResult['finals'] / $simulations) * 100;
            $teamResult['top3_probability'] = ($teamResult['top3'] / $simulations) * 100;
        }
        
        return $results;
    }
}
```
### 3.2 Seeding Management Interface
```javascript
// public/js/seeding-manager.js
class SeedingManager {
    constructor() {
        this.tournamentId = null;
        this.teams = [];
        this.originalSeeding = [];
    }
    
    init(tournamentId) {
        this.tournamentId = tournamentId;
        this.loadTeams();
        this.initDragDropSeeding();
    }
    
    loadTeams() {
        $.ajax({
            url: `/api/tournament/${this.tournamentId}/seeding`,
            success: (data) => {
                this.teams = data.teams;
                this.originalSeeding = [...data.teams];
                this.renderSeedingList();
                this.renderPerformanceMetrics();
            }
        });
    }
    
    renderSeedingList() {
        const container = $('#seeding-list');
        container.empty();
        
        this.teams.forEach((team, index) => {
            const card = $(`
                <div class="seeding-card" data-team-id="${team.id}" data-seed="${index + 1}">
                    <div class="seed-number">${index + 1}</div>
                    <div class="team-info">
                        <h5>${team.name}</h5>
                        <div class="team-stats">
                            <span class="badge bg-primary">Score: ${team.seeding_score.toFixed(2)}</span>
                            <span class="badge bg-secondary">ELO: ${team.elo_rating}</span>
                            <span class="badge bg-info">Prev: ${team.previous_rank || 'N/A'}</span>
                        </div>
                    </div>
                    <div class="seed-actions">
                        <button class="btn btn-sm btn-outline-primary lock-seed" 
                                data-team-id="${team.id}">
                            <i class="fas fa-lock-open"></i>
                        </button>
                    </div>
                </div>
            `);
            
            container.append(card);
        });
    }
    
    initDragDropSeeding() {
        $('#seeding-list').sortable({
            handle: '.team-info',
            animation: 150,
            onEnd: (evt) => {
                this.updateSeeding(evt.oldIndex, evt.newIndex);
            }
        });
    }
    
    updateSeeding(oldIndex, newIndex) {
        // Reorder teams array
        const team = this.teams.splice(oldIndex, 1)[0];
        this.teams.splice(newIndex, 0, team);
        
        // Update seed numbers
        this.teams.forEach((team, index) => {
            team.seed_number = index + 1;
        });
        
        // Update display
        this.renderSeedingList();
        
        // Show save button
        $('#save-seeding').removeClass('d-none');
    }
    
    saveSeeding() {
        const adjustments = this.teams.map((team, index) => ({
            team_id: team.id,
            new_seed: index + 1
        }));
        
        $.ajax({
            url: `/api/tournament/${this.tournamentId}/seeding`,
            method: 'PUT',
            data: { adjustments: adjustments },
            success: (response) => {
                toastr.success('Seeding updated successfully');
                this.previewBracket();
            }
        });
    }
    
    autoSeed() {
        $.ajax({
            url: `/api/tournament/${this.tournamentId}/seeding/auto`,
            method: 'POST',
            success: (data) => {
                this.teams = data.teams;
                this.renderSeedingList();
                toastr.success('Automatic seeding applied');
            }
        });
    }
    
    simulateTournament() {
        $('#simulation-modal').modal('show');
        
        $.ajax({
            url: `/api/tournament/${this.tournamentId}/simulate`,
            method: 'POST',
            success: (data) => {
                this.renderSimulationResults(data.results);
            }
        });
    }
    
    renderSimulationResults(results) {
        const container = $('#simulation-results');
        container.empty();
        
        // Sort by win probability
        const sorted = Object.entries(results).sort((a, b) => 
            b[1].win_probability - a[1].win_probability
        );
        
        sorted.forEach(([teamId, probabilities]) => {
            const team = this.teams.find(t => t.id == teamId);
            
            const row = $(`
                <tr>
                    <td>${team.name}</td>
                    <td>${probabilities.win_probability.toFixed(1)}%</td>
                    <td>${probabilities.finals_probability.toFixed(1)}%</td>
                    <td>${probabilities.top3_probability.toFixed(1)}%</td>
                </tr>
            `);
            
            container.append(row);
        });
    }
}
```
## 4. RESULTS PUBLICATION
### 4.1 Results Management Database

```sql
-- Tournament results and publications
CREATE TABLE tournament_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tournament_id INT NOT NULL,
    category_id INT NOT NULL,
    placement INT NOT NULL,
    team_id INT NOT NULL,
    team_score DECIMAL(10,2) NULL,
    medal_type ENUM('gold', 'silver', 'bronze', 'none') NULL,
    prize_description TEXT NULL,
    certificate_number VARCHAR(50) NULL,
    published BOOLEAN DEFAULT FALSE,
    published_at TIMESTAMP NULL,
    verified_by INT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (team_id) REFERENCES teams(id),
    FOREIGN KEY (verified_by) REFERENCES users(id),
    UNIQUE KEY unique_placement (tournament_id, category_id, placement),
    INDEX idx_team_results (team_id, tournament_id)
);

-- Results publication log
CREATE TABLE results_publications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tournament_id INT NOT NULL,
    publication_type ENUM('preliminary', 'official', 'amended') NOT NULL,
    publication_channel ENUM('website', 'email', 'social', 'print', 'all') NOT NULL,
    published_by INT NOT NULL,
    publication_url VARCHAR(255) NULL,
    document_path VARCHAR(255) NULL,
    recipients_count INT DEFAULT 0,
    publication_status ENUM('draft', 'scheduled', 'published', 'retracted') DEFAULT 'draft',
    scheduled_for DATETIME NULL,
    published_at TIMESTAMP NULL,
    retracted_at TIMESTAMP NULL,
    retraction_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id),
    FOREIGN KEY (published_by) REFERENCES users(id),
    INDEX idx_publication_status (publication_status, scheduled_for)
);
```
### 4.2 Results Publication Service
```php
// app/Services/ResultsPublisher.php
class ResultsPublisher {
    
    public function publishResults($tournamentId) {
        $tournament = $this->getTournament($tournamentId);
        
        // Verify all matches completed
        if (!$this->verifyTournamentComplete($tournamentId)) {
            throw new Exception("Tournament not yet complete");
        }
        
        // Calculate final results
        $results = $this->calculateFinalResults($tournamentId);
        
        // Generate certificates
        $certificates = $this->generateCertificates($results);
        
        // Create publication record
        $publication = $this->createPublication($tournamentId, $results);
        
        // Publish to different channels
        $this->publishToWebsite($publication);
        $this->publishToEmail($publication);
        $this->publishToSocialMedia($publication);
        
        return $publication;
    }
    
    private function calculateFinalResults($tournamentId) {
        $tournament = $this->getTournament($tournamentId);
        
        if ($tournament->tournament_type == 'round_robin') {
            return $this->getRoundRobinResults($tournamentId);
        } else {
            return $this->getEliminationResults($tournamentId);
        }
    }
    
    private function getRoundRobinResults($tournamentId) {
        $standings = $this->getStandings($tournamentId);
        $results = [];
        
        foreach ($standings as $index => $team) {
            $results[] = [
                'placement' => $index + 1,
                'team_id' => $team->team_id,
                'team_name' => $team->team_name,
                'team_score' => $team->league_points,
                'medal_type' => $this->getMedalType($index + 1),
                'statistics' => [
                    'played' => $team->matches_played,
                    'won' => $team->wins,
                    'drawn' => $team->draws,
                    'lost' => $team->losses,
                    'points_for' => $team->points_for,
                    'points_against' => $team->points_against
                ]
            ];
        }
        
        return $results;
    }
    
    private function getMedalType($placement) {
        switch ($placement) {
            case 1: return 'gold';
            case 2: return 'silver';
            case 3: return 'bronze';
            default: return 'none';
        }
    }
    
    public function generateResultsDocument($tournamentId) {
        $tournament = $this->getTournament($tournamentId);
        $results = $this->getResults($tournamentId);
        
        // Create PDF document
        $pdf = new ResultsPDF();
        
        // Add header
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 20);
        $pdf->Cell(0, 10, 'GDE SciBOTICS Competition 2025', 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, $tournament->tournament_name, 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, 'Official Results - ' . date('d M Y'), 0, 1, 'C');
        
        // Add results by category
        foreach ($results as $category => $categoryResults) {
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 10, "Category: $category", 0, 1);
            
            // Results table
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(20, 7, 'Place', 1);
            $pdf->Cell(80, 7, 'Team Name', 1);
            $pdf->Cell(40, 7, 'School', 1);
            $pdf->Cell(30, 7, 'Score', 1);
            $pdf->Cell(20, 7, 'Medal', 1);
            $pdf->Ln();
            
            $pdf->SetFont('Arial', '', 10);
            foreach ($categoryResults as $result) {
                $pdf->Cell(20, 6, $result->placement, 1);
                $pdf->Cell(80, 6, $result->team_name, 1);
                $pdf->Cell(40, 6, $result->school_name, 1);
                $pdf->Cell(30, 6, $result->team_score, 1);
                $pdf->Cell(20, 6, ucfirst($result->medal_type), 1);
                $pdf->Ln();
            }
        }
        
        // Save document
        $filename = "results_{$tournament->id}_" . date('YmdHis') . ".pdf";
        $pdf->Output('F', storage_path("app/results/{$filename}"));
        
        return $filename;
    }
}
```
### 4.3 Results Display Interface
```javascript
// public/js/results-display.js
class ResultsDisplay {
    constructor() {
        this.tournamentId = null;
        this.results = {};
        this.animations = true;
    }
    
    init(tournamentId) {
        this.tournamentId = tournamentId;
        this.loadResults();
        this.initLiveUpdates();
    }
    
    loadResults() {
        $.ajax({
            url: `/api/tournament/${this.tournamentId}/results`,
            success: (data) => {
                this.results = data.results;
                this.renderResults();
                
                if (data.status === 'final') {
                    this.showCelebration();
                }
            }
        });
    }
    
    renderResults() {
        const container = $('#results-container');
        container.empty();
        
        // Render by category
        Object.keys(this.results).forEach(category => {
            const categoryResults = this.results[category];
            
            const section = $(`
                <div class="category-results" data-category="${category}">
                    <h3 class="category-title">${category} Category</h3>
                    <div class="podium-display"></div>
                    <div class="full-results"></div>
                </div>
            `);
            
            // Render podium for top 3
            this.renderPodium(section.find('.podium-display'), categoryResults.slice(0, 3));
            
            // Render full results table
            this.renderResultsTable(section.find('.full-results'), categoryResults);
            
            container.append(section);
        });
    }
    
    renderPodium(container, topThree) {
        const podium = $(`
            <div class="podium">
                <div class="podium-place second">
                    <div class="team-info">
                        <div class="medal silver-medal">2</div>
                        <h5>${topThree[1]?.team_name || '-'}</h5>
                        <p class="school">${topThree[1]?.school_name || ''}</p>
                        <p class="score">${topThree[1]?.team_score || ''}</p>
                    </div>
                    <div class="podium-bar silver-bar"></div>
                </div>
                <div class="podium-place first">
                    <div class="team-info">
                        <div class="medal gold-medal">1</div>
                        <h5>${topThree[0]?.team_name || '-'}</h5>
                        <p class="school">${topThree[0]?.school_name || ''}</p>
                        <p class="score">${topThree[0]?.team_score || ''}</p>
                    </div>
                    <div class="podium-bar gold-bar"></div>
                </div>
                <div class="podium-place third">
                    <div class="team-info">
                        <div class="medal bronze-medal">3</div>
                        <h5>${topThree[2]?.team_name || '-'}</h5>
                        <p class="school">${topThree[2]?.school_name || ''}</p>
                        <p class="score">${topThree[2]?.team_score || ''}</p>
                    </div>
                    <div class="podium-bar bronze-bar"></div>
                </div>
            </div>
        `);
        
        container.html(podium);
        
        // Animate podium appearance
        if (this.animations) {
            setTimeout(() => {
                $('.podium-bar').addClass('animate-grow');
            }, 100);
        }
    }
    
    showCelebration() {
        // Confetti animation for winners
        confetti({
            particleCount: 100,
            spread: 70,
            origin: { y: 0.6 }
        });
        
        // Play celebration sound
        const audio = new Audio('/sounds/celebration.mp3');
        audio.play();
        
        // Show winner announcement
        this.showWinnerAnnouncement();
    }
    
    generateShareableResults() {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        canvas.width = 1200;
        canvas.height = 630;
        
        // Draw background
        ctx.fillStyle = '#1a1a2e';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        // Add logo and title
        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 48px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('GDE SciBOTICS Competition 2025', canvas.width / 2, 100);
        
        // Add results summary
        ctx.font = '24px Arial';
        let yPos = 200;
        
        Object.keys(this.results).forEach(category => {
            const winner = this.results[category][0];
            ctx.fillText(`${category}: ${winner.team_name}`, canvas.width / 2, yPos);
            yPos += 50;
        });
        
        // Convert to image
        canvas.toBlob((blob) => {
            const url = URL.createObjectURL(blob);
            
            // Open share dialog
            if (navigator.share) {
                navigator.share({
                    title: 'GDE SciBOTICS Competition Results',
                    text: 'Check out the competition results!',
                    files: [new File([blob], 'results.png', { type: 'image/png' })]
                });
            } else {
                // Fallback: download image
                const a = document.createElement('a');
                a.href = url;
                a.download = 'scibiotics-results.png';
                a.click();
            }
        });
    }
    
    exportResults(format) {
        $.ajax({
            url: `/api/tournament/${this.tournamentId}/results/export`,
            method: 'POST',
            data: { format: format },
            xhrFields: {
                responseType: 'blob'
            },
            success: (blob) => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `results.${format}`;
                document.body.appendChild(a);
                a.click();
                a.remove();
            }
        });
    }
}
```
---
# IMPLEMENTATION TIMELINE
## Database & Core Structure

- [ ] Create tournament tables
- [ ] Set up bracket and match schemas
- [ ] Build seeding tables
- [ ] Implement results structure

## Bracket Generation

- [ ] Build elimination bracket generator
- [ ] Implement bye handling
- [ ] Create match progression logic
- [ ] Add repechage support

## Round-Robin System

- [ ] Implement round-robin generator
- [ ] Build standings calculator
- [ ] Create tiebreaker logic
- [ ] Add schedule optimizer

## Seeding & Performance

- [ ] Build seeding calculator
- [ ] Implement ELO system
- [ ] Create manual adjustment interface
- [ ] Add simulation engine

## Results & Publication

- [ ] Build results compiler
- [ ] Create publication system
- [ ] Generate certificates
- [ ] Implement sharing features

---

# KEY DELIVERABLES
## 1. Multi-Format Tournament Support
- Elimination brackets (single/double)
- Round-robin tournaments
- Swiss system support
- Hybrid tournament formats

## 2. Intelligent Seeding System

- Performance-based seeding
- ELO rating tracking
- Manual adjustment capability
- Tournament simulation

## 3. Live Tournament Management

- Real-time bracket updates
- Live scoring integration
- Automatic progression
- Conflict resolution

## 4. Comprehensive Results System

- Automated results calculation
- Multi-channel publication
- Certificate generation
- Social media integration
---
# SUCCESS METRICS
| Metric | Target | Measurement |
| --- | --- | --- |
|Bracket Generation Speed | ```<2 seconds``` | System timing |
| Seeding Accuracy | ```>90% correlation``` | Performance analysis |
| Results Publication Time | ```<5 minutes post-final``` | Publication logs |
| Data Accuracy | ```100%``` | Verification checks |
| User Satisfaction | ```>4.5/5``` | User surveys |

---
# TESTING CHECKLIST
## Functional Testing

 - [ ] Bracket generation for all team counts
- [ ] Bye handling and progression
- [ ] Round-robin schedule completeness
- [ ] Seeding calculation accuracy
- [ ] Results compilation correctness

## Integration Testing

- [ ] Scoring system integration
- [ ] Live update functionality
- [ ] Publication channel testing
- [ ] Certificate generation

## Performance Testing

- [ ] Large tournament handling (100+ teams)
- [ ] Concurrent match updates
- [ ] Real-time standings calculation
- [ ] Export generation speed

This comprehensive Tournament Bracket System will handle the complete competition flow from school eliminations through to provincial finals, ensuring fair competition and transparent results publication.
