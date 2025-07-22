<?php
// database/migrations/010_create_views_and_procedures.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateViewsAndProcedures extends Migration
{
    public function up()
    {
        // Create team summary view
        $teamSummaryView = "
        CREATE VIEW team_summary AS
        SELECT 
            t.id,
            t.name as team_name,
            t.team_code,
            s.name as school_name,
            s.district,
            c.name as category_name,
            c.code as category_code,
            t.team_size,
            t.status,
            t.current_phase,
            t.qualification_score,
            CONCAT(u1.first_name, ' ', u1.last_name) as coach1_name,
            CONCAT(u2.first_name, ' ', u2.last_name) as coach2_name,
            t.created_at
        FROM teams t
        JOIN schools s ON t.school_id = s.id
        JOIN categories c ON t.category_id = c.id
        LEFT JOIN users u1 ON t.coach1_id = u1.id
        LEFT JOIN users u2 ON t.coach2_id = u2.id
        ";
        $this->execute($teamSummaryView, "Creating team summary view");
        
        // Create participant summary view
        $participantSummaryView = "
        CREATE VIEW participant_summary AS
        SELECT 
            p.id,
            p.first_name,
            p.last_name,
            p.grade,
            p.gender,
            p.date_of_birth,
            YEAR(CURDATE()) - YEAR(p.date_of_birth) - (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(p.date_of_birth, '%m%d')) as age,
            t.name as team_name,
            t.team_code,
            s.name as school_name,
            c.name as category_name,
            p.consent_form_signed,
            p.created_at
        FROM participants p
        JOIN teams t ON p.team_id = t.id
        JOIN schools s ON t.school_id = s.id
        JOIN categories c ON t.category_id = c.id
        ";
        $this->execute($participantSummaryView, "Creating participant summary view");
        
        // Create competition overview view
        $competitionOverviewView = "
        CREATE VIEW competition_overview AS
        SELECT 
            comp.id,
            comp.name as competition_name,
            comp.year,
            p.name as phase_name,
            cat.name as category_name,
            comp.venue_name,
            comp.date,
            comp.status,
            comp.current_participants,
            comp.max_participants,
            COUNT(t.id) as registered_teams
        FROM competitions comp
        JOIN phases p ON comp.phase_id = p.id
        JOIN categories cat ON comp.category_id = cat.id
        LEFT JOIN teams t ON comp.id = t.competition_id
        GROUP BY comp.id, comp.name, comp.year, p.name, cat.name, comp.venue_name, comp.date, comp.status, comp.current_participants, comp.max_participants
        ";
        $this->execute($competitionOverviewView, "Creating competition overview view");
        
        $this->logger->info("Views created successfully");
    }
    
    public function down()
    {
        $this->execute("DROP VIEW IF EXISTS competition_overview", "Dropping competition overview view");
        $this->execute("DROP VIEW IF EXISTS participant_summary", "Dropping participant summary view");
        $this->execute("DROP VIEW IF EXISTS team_summary", "Dropping team summary view");
    }
}