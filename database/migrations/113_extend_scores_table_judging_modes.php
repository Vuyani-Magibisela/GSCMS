<?php
// database/migrations/113_extend_scores_table_judging_modes.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class ExtendScoresTableJudgingModes extends Migration
{
    public function up()
    {
        // Add judging mode tracking to scores table
        $this->addColumn('scores', 'judging_mode', 'ENUM("presentation", "gameplay", "hybrid") DEFAULT "presentation" COMMENT "Type of judging mode used for this score"');

        // Add presentation-specific scoring breakdown
        $this->addColumn('scores', 'presentation_breakdown', 'JSON NULL COMMENT "Detailed breakdown of presentation scoring categories"');

        // Add gameplay-specific scoring breakdown
        $this->addColumn('scores', 'gameplay_breakdown', 'JSON NULL COMMENT "Detailed breakdown of gameplay runs and mission completion"');

        // Add presentation timing data
        $this->addColumn('scores', 'presentation_duration_minutes', 'INT NULL COMMENT "Total presentation time in minutes"');

        // Add best run reference for gameplay judging
        $this->addColumn('scores', 'best_gameplay_run_id', 'INT NULL COMMENT "Reference to the best gameplay run used for scoring"');

        // Add mission completion summary
        $this->addColumn('scores', 'mission_completion_percentage', 'DECIMAL(5,2) NULL COMMENT "Percentage of mission objectives completed"');

        // Add fastest run time for gameplay
        $this->addColumn('scores', 'fastest_run_time_seconds', 'INT NULL COMMENT "Time of fastest successful run in seconds"');

        // Add presentation quality scores
        $this->addColumn('scores', 'problem_research_score', 'DECIMAL(10,2) DEFAULT 0.00 COMMENT "Problem & Solution Research score (0-25)"');
        $this->addColumn('scores', 'robot_presentation_score', 'DECIMAL(10,2) DEFAULT 0.00 COMMENT "Robot Presentation score (0-25)"');
        $this->addColumn('scores', 'model_presentation_score', 'DECIMAL(10,2) DEFAULT 0.00 COMMENT "Model Presentation score (0-15)"');
        $this->addColumn('scores', 'communication_skills_score', 'DECIMAL(10,2) DEFAULT 0.00 COMMENT "Communication Skills score (0-5)"');
        $this->addColumn('scores', 'teamwork_collaboration_score', 'DECIMAL(10,2) DEFAULT 0.00 COMMENT "Teamwork & Collaboration score (0-5)"');

        // Add indexes for efficient querying
        $this->addIndex('scores', 'idx_judging_mode', 'judging_mode');
        $this->addIndex('scores', 'idx_best_run', 'best_gameplay_run_id');
        $this->addIndex('scores', 'idx_fastest_time', 'fastest_run_time_seconds');
        $this->addIndex('scores', 'idx_mission_completion', 'mission_completion_percentage');

        // Add foreign key constraint for best run reference
        $this->addForeignKey('scores', 'fk_scores_best_run', 'best_gameplay_run_id', 'gameplay_runs', 'id');

        echo "Extended scores table with dual judging system fields.\n";
    }

    public function down()
    {
        // Remove the added columns
        $this->dropColumn('scores', 'judging_mode');
        $this->dropColumn('scores', 'presentation_breakdown');
        $this->dropColumn('scores', 'gameplay_breakdown');
        $this->dropColumn('scores', 'presentation_duration_minutes');
        $this->dropColumn('scores', 'best_gameplay_run_id');
        $this->dropColumn('scores', 'mission_completion_percentage');
        $this->dropColumn('scores', 'fastest_run_time_seconds');
        $this->dropColumn('scores', 'problem_research_score');
        $this->dropColumn('scores', 'robot_presentation_score');
        $this->dropColumn('scores', 'model_presentation_score');
        $this->dropColumn('scores', 'communication_skills_score');
        $this->dropColumn('scores', 'teamwork_collaboration_score');

        echo "Removed dual judging system fields from scores table.\n";
    }
}