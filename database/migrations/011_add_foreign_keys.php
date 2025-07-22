<?php
// database/migrations/011_add_foreign_keys.php - NEW MIGRATION

require_once __DIR__ . '/../../app/Core/Migration.php';

class AddForeignKeys extends Migration
{
    public function up()
    {
        $this->logger->info("Adding foreign key constraints...");
        
        // Add foreign keys for schools table
        $this->execute(
            "ALTER TABLE schools ADD CONSTRAINT fk_schools_coordinator FOREIGN KEY (coordinator_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE",
            "Adding coordinator foreign key to schools"
        );
        
        // Add foreign keys for competitions table
        $this->execute(
            "ALTER TABLE competitions ADD CONSTRAINT fk_competitions_phase FOREIGN KEY (phase_id) REFERENCES phases(id) ON DELETE CASCADE ON UPDATE CASCADE",
            "Adding phase foreign key to competitions"
        );
        
        $this->execute(
            "ALTER TABLE competitions ADD CONSTRAINT fk_competitions_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE ON UPDATE CASCADE",
            "Adding category foreign key to competitions"
        );
        
        // Add foreign keys for teams table
        $this->execute(
            "ALTER TABLE teams ADD CONSTRAINT fk_teams_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE ON UPDATE CASCADE",
            "Adding school foreign key to teams"
        );
        
        $this->execute(
            "ALTER TABLE teams ADD CONSTRAINT fk_teams_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT ON UPDATE CASCADE",
            "Adding category foreign key to teams"
        );
        
        $this->execute(
            "ALTER TABLE teams ADD CONSTRAINT fk_teams_competition FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE SET NULL ON UPDATE CASCADE",
            "Adding competition foreign key to teams"
        );
        
        $this->execute(
            "ALTER TABLE teams ADD CONSTRAINT fk_teams_coach1 FOREIGN KEY (coach1_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE",
            "Adding coach1 foreign key to teams"
        );
        
        $this->execute(
            "ALTER TABLE teams ADD CONSTRAINT fk_teams_coach2 FOREIGN KEY (coach2_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE",
            "Adding coach2 foreign key to teams"
        );
        
        // Add foreign keys for participants table
        $this->execute(
            "ALTER TABLE participants ADD CONSTRAINT fk_participants_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE ON UPDATE CASCADE",
            "Adding team foreign key to participants"
        );
        
        $this->execute(
            "ALTER TABLE participants ADD CONSTRAINT fk_participants_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE",
            "Adding user foreign key to participants"
        );
        
        // Add foreign keys for supporting tables
        $this->execute(
            "ALTER TABLE user_sessions ADD CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE",
            "Adding user foreign key to user_sessions"
        );
        
        $this->execute(
            "ALTER TABLE user_activity_log ADD CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE",
            "Adding user foreign key to user_activity_log"
        );
        
        $this->logger->info("All foreign key constraints added successfully");
    }
    
    public function down()
    {
        $this->logger->info("Removing foreign key constraints...");
        
        // Remove foreign keys in reverse order
        $foreignKeys = [
            'user_activity_log' => ['fk_activity_user'],
            'user_sessions' => ['fk_sessions_user'],
            'participants' => ['fk_participants_user', 'fk_participants_team'],
            'teams' => ['fk_teams_coach2', 'fk_teams_coach1', 'fk_teams_competition', 'fk_teams_category', 'fk_teams_school'],
            'competitions' => ['fk_competitions_category', 'fk_competitions_phase'],
            'schools' => ['fk_schools_coordinator']
        ];
        
        foreach ($foreignKeys as $table => $constraints) {
            foreach ($constraints as $constraint) {
                try {
                    $this->execute(
                        "ALTER TABLE {$table} DROP FOREIGN KEY {$constraint}",
                        "Removing foreign key: {$constraint}"
                    );
                } catch (Exception $e) {
                    $this->logger->warning("Could not remove foreign key {$constraint}: " . $e->getMessage());
                }
            }
        }
        
        $this->logger->info("Foreign key constraints removed");
    }
}