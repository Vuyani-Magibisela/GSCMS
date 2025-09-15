<?php
// database/migrations/113_add_name_column_to_venues_table.php

class AddNameColumnToVenuesTable
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Run the migration
     */
    public function up()
    {
        echo "Adding 'name' column to venues table...\n";

        // Add name column as alias to venue_name for backward compatibility
        $sql = "ALTER TABLE `venues` ADD COLUMN `name` VARCHAR(200) NOT NULL AFTER `id`";
        $this->db->exec($sql);

        echo "Copying venue_name data to name column...\n";

        // Copy existing venue_name data to the new name column
        $updateSql = "UPDATE `venues` SET `name` = `venue_name`";
        $this->db->exec($updateSql);

        echo "Name column added to venues table successfully.\n";
    }

    /**
     * Reverse the migration
     */
    public function down()
    {
        echo "Dropping 'name' column from venues table...\n";
        $this->db->exec("ALTER TABLE `venues` DROP COLUMN `name`");
        echo "Name column dropped from venues table.\n";
    }
}