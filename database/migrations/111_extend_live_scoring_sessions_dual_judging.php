<?php
// database/migrations/111_extend_live_scoring_sessions_dual_judging.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class ExtendLiveScoringSesssionsDualJudging extends Migration
{
    public function up()
    {
        // Add new columns to live_scoring_sessions table for dual judging system
        $this->addColumn('live_scoring_sessions', 'judging_mode', 'ENUM("presentation", "gameplay", "hybrid") DEFAULT "presentation" COMMENT "Type of judging mode for this session"');

        $this->addColumn('live_scoring_sessions', 'presentation_rubric_config', 'JSON NULL COMMENT "Configuration for presentation judging criteria"');

        $this->addColumn('live_scoring_sessions', 'gameplay_config', 'JSON NULL COMMENT "Configuration for gameplay timing and mission parameters"');

        $this->addColumn('live_scoring_sessions', 'max_presentation_time_minutes', 'INT DEFAULT 10 COMMENT "Maximum time allowed for presentations"');

        $this->addColumn('live_scoring_sessions', 'max_gameplay_runs', 'TINYINT DEFAULT 3 COMMENT "Maximum number of gameplay runs allowed"');

        $this->addColumn('live_scoring_sessions', 'auto_select_fastest_run', 'BOOLEAN DEFAULT TRUE COMMENT "Whether to automatically select fastest successful run"');

        $this->addColumn('live_scoring_sessions', 'mission_objectives', 'JSON NULL COMMENT "List of mission objectives for gameplay judging"');

        // Add index for judging mode queries
        $this->addIndex('live_scoring_sessions', 'idx_judging_mode', 'judging_mode');

        echo "Extended live_scoring_sessions table with dual judging system fields.\n";
    }

    public function down()
    {
        // Remove the added columns
        $this->dropColumn('live_scoring_sessions', 'judging_mode');
        $this->dropColumn('live_scoring_sessions', 'presentation_rubric_config');
        $this->dropColumn('live_scoring_sessions', 'gameplay_config');
        $this->dropColumn('live_scoring_sessions', 'max_presentation_time_minutes');
        $this->dropColumn('live_scoring_sessions', 'max_gameplay_runs');
        $this->dropColumn('live_scoring_sessions', 'auto_select_fastest_run');
        $this->dropColumn('live_scoring_sessions', 'mission_objectives');

        echo "Removed dual judging system fields from live_scoring_sessions table.\n";
    }
}