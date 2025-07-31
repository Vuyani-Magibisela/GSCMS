# File Upload System Database Migrations

This document outlines the database migrations created for the comprehensive file upload system implementation.

## Migration Files Created

### 021_create_uploaded_files_table.php
**Purpose**: Creates the main `uploaded_files` table to track all files uploaded to the system.

**Key Features**:
- Polymorphic relationships to any model via `related_type` and `related_id`
- File integrity tracking with MD5 and SHA256 hashes
- Multiple file statuses: uploaded, processing, ready, error, quarantine, archived
- Role-based access levels: private, school, team, public, admin
- Upload type categorization for different file types
- Download tracking and metadata storage
- Comprehensive indexing for performance

**Columns**:
- `original_name` - Original filename from user
- `stored_name` - Actual filename on disk
- `file_path` - Full file path on disk
- `relative_path` - Relative path from public directory
- `file_size` - File size in bytes
- `mime_type` - MIME type of file
- `file_extension` - File extension
- `upload_type` - Type of upload (consent_forms, team_submissions, etc.)
- `uploaded_by` - User ID who uploaded the file
- `related_type` - Model class name for polymorphic relationship
- `related_id` - Related model ID for polymorphic relationship
- `metadata` - Additional file metadata (JSON)
- `hash_md5` - MD5 hash of file
- `hash_sha256` - SHA256 hash of file
- `status` - File processing status
- `access_level` - Access level for file
- `download_count` - Number of downloads

### 022_create_team_submissions_table.php
**Purpose**: Creates the `team_submissions` table for managing team submissions across competition phases.

**Key Features**:
- Links to teams and competition phases
- Multiple submission types with different requirements
- Workflow states from draft to approved/rejected
- Reviewer assignment and scoring capability
- File attachment integration
- Unique constraint to prevent duplicate submissions

**Columns**:
- `team_id` - Team that made the submission
- `phase_id` - Competition phase this submission is for
- `submission_type` - Type of submission (design_portfolio, technical_report, etc.)
- `title` - Title of the submission
- `description` - Description of the submission
- `file_path` - Path to uploaded file
- `file_name` - Original filename
- `file_size` - File size in bytes
- `file_type` - MIME type of file
- `status` - Submission status (draft, submitted, under_review, etc.)
- `submitted_date` - When submission was submitted for review
- `reviewed_date` - When submission was reviewed
- `reviewed_by` - User ID who reviewed the submission
- `score` - Score given to submission
- `feedback` - Feedback from reviewer
- `notes` - Internal notes
- `metadata` - Additional submission metadata (JSON)
- `uploaded_file_id` - Reference to uploaded_files table

### 023_update_consent_forms_add_file_fields.php
**Purpose**: Updates the existing `consent_forms` table with file upload integration.

**Enhancements Added**:
- `file_name` - Original filename of uploaded consent form
- `file_size` - File size in bytes
- `file_type` - MIME type of uploaded file
- `metadata` - File metadata including signatures, verification status (JSON)
- `uploaded_file_id` - Reference to uploaded_files table

### 024_add_file_upload_foreign_keys.php
**Purpose**: Establishes referential integrity with foreign key constraints.

**Foreign Keys Added**:
- `uploaded_files.uploaded_by` → `users.id` (RESTRICT/CASCADE)
- `team_submissions.team_id` → `teams.id` (RESTRICT/CASCADE)
- `team_submissions.phase_id` → `phases.id` (RESTRICT/CASCADE)
- `team_submissions.reviewed_by` → `users.id` (SET NULL/CASCADE)
- `team_submissions.uploaded_file_id` → `uploaded_files.id` (SET NULL/CASCADE)
- `consent_forms.uploaded_file_id` → `uploaded_files.id` (SET NULL/CASCADE)

### 025_add_file_management_enhancements.php
**Purpose**: Adds performance optimizations and reporting capabilities.

**Enhancements**:
- Additional performance indexes for common queries
- Full-text search index on submission titles and descriptions
- Database views for reporting:
  - `file_storage_stats` - Storage usage by upload type
  - `submission_status_overview` - Submission status across phases
  - `consent_form_completeness` - Consent form completion tracking

## Database Views Created

### file_storage_stats
Provides storage analytics:
- File counts and total sizes by upload type
- Status breakdown (ready, error, quarantined files)
- Upload date ranges
- Size statistics (min, max, average)

### submission_status_overview
Tracks submission progress:
- Submission counts by phase and type
- Average scores by category
- File attachment rates
- Review completion status

### consent_form_completeness
Monitors consent form compliance:
- Participant counts vs. forms submitted
- Approval/rejection/pending breakdowns
- Completion percentages by school/team
- Compliance tracking for regulations

## Running the Migrations

To apply these migrations to your database:

```bash
# Run all pending migrations
php database/console/migrate.php

# Check migration status
php database/console/migrate.php --status

# Rollback if needed (be careful with data)
php database/console/migrate.php --rollback=5
```

## Performance Considerations

The migrations include comprehensive indexing for:
- **File lookup** by hash (duplicate detection)
- **Access control** queries by user and permission level
- **Storage analysis** by size and upload date
- **Reporting queries** for dashboards and analytics
- **Search functionality** with full-text indexes

## Data Integrity Features

- **Foreign key constraints** ensure referential integrity
- **Unique constraints** prevent duplicate submissions
- **Enum values** enforce valid status and type values
- **JSON validation** for metadata fields
- **Soft deletes** preserve audit trails

## Security Considerations

- **Access level controls** at database level
- **User tracking** for all file operations
- **Audit trails** with timestamps and user IDs
- **File integrity** validation with hash storage
- **Polymorphic relationships** for flexible security policies

## Storage Optimization

- **Hash-based duplicate detection** saves storage space
- **Metadata storage** in efficient JSON format
- **Status tracking** enables cleanup of temporary files
- **Archive status** for long-term storage management
- **Download tracking** for usage analytics

## Backup Considerations

Before running these migrations in production:

1. **Full database backup** - Critical before structural changes
2. **Test on staging** - Verify migrations work with your data
3. **Rollback plan** - Understand rollback implications
4. **Data migration** - Plan for existing file data if any
5. **Index rebuild** - May be needed after large data migrations

## Monitoring After Migration

Key metrics to monitor:
- **Migration execution time** - Large tables may take time
- **Index creation performance** - May impact database during creation
- **Storage space usage** - New tables and indexes require space
- **Query performance** - Verify improvements with new indexes
- **Foreign key constraint violations** - Check for data consistency issues