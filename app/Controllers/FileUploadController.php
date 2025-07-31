<?php

namespace App\Controllers;

use App\Core\FileUpload;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\ConsentForm;
use App\Models\TeamSubmission;
use App\Models\UploadedFile;
use App\Models\User;
use App\Models\Participant;
use App\Models\Team;
use Exception;

class FileUploadController extends BaseController
{
    protected $fileUpload;
    protected $auth;
    
    public function __construct()
    {
        parent::__construct();
        $this->fileUpload = new FileUpload();
        $this->auth = Auth::getInstance();
    }
    
    /**
     * Upload consent form
     */
    public function uploadConsentForm()
    {
        try {
            // Check authentication
            if (!$this->auth->isLoggedIn()) {
                return Response::json(['error' => 'Authentication required'], 401);
            }
            
            // Validate request
            $participantId = Request::input('participant_id');
            $formType = Request::input('form_type');
            
            if (!$participantId || !$formType) {
                return Response::json(['error' => 'Participant ID and form type are required'], 400);
            }
            
            // Check if user has permission to upload for this participant
            if (!$this->canUploadForParticipant($participantId)) {
                return Response::json(['error' => 'Permission denied'], 403);
            }
            
            // Get uploaded files
            $files = $_FILES['consent_file'] ?? null;
            if (!$files) {
                return Response::json(['error' => 'No file uploaded'], 400);
            }
            
            // Set upload options
            $options = [
                'participant_id' => $participantId,
                'form_type' => $formType,
                'school_id' => $this->getParticipantSchoolId($participantId),
                'team_id' => $this->getParticipantTeamId($participantId)
            ];
            
            // Handle upload
            $result = $this->fileUpload->handleUpload($files, 'consent_forms', $options);
            
            if ($result['success']) {
                // Create or update consent form record
                $consentForm = $this->createOrUpdateConsentForm($participantId, $formType, $result['data'][0]);
                
                if ($consentForm) {
                    return Response::json([
                        'success' => true,
                        'message' => 'Consent form uploaded successfully',
                        'consent_form_id' => $consentForm->id,
                        'file_info' => $result['data'][0]
                    ]);
                } else {
                    return Response::json(['error' => 'Failed to create consent form record'], 500);
                }
            } else {
                return Response::json(['error' => $result['message']], 400);
            }
            
        } catch (Exception $e) {
            $this->logger->error('Consent form upload error: ' . $e->getMessage());
            return Response::json(['error' => 'Upload failed: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Upload team submission
     */
    public function uploadTeamSubmission()
    {
        try {
            // Check authentication
            if (!$this->auth->isLoggedIn()) {
                return Response::json(['error' => 'Authentication required'], 401);
            }
            
            // Validate request
            $teamId = Request::input('team_id');
            $phaseId = Request::input('phase_id');
            $submissionType = Request::input('submission_type');
            $title = Request::input('title');
            $description = Request::input('description');
            
            if (!$teamId || !$phaseId || !$submissionType || !$title) {
                return Response::json(['error' => 'Team ID, phase ID, submission type, and title are required'], 400);
            }
            
            // Check if user has permission to upload for this team
            if (!$this->canUploadForTeam($teamId)) {
                return Response::json(['error' => 'Permission denied'], 403);
            }
            
            // Get uploaded files
            $files = $_FILES['submission_file'] ?? null;
            if (!$files) {
                return Response::json(['error' => 'No file uploaded'], 400);
            }
            
            // Set upload options
            $options = [
                'team_id' => $teamId,
                'phase' => $phaseId,
                'school_id' => $this->getTeamSchoolId($teamId)
            ];
            
            // Handle upload
            $result = $this->fileUpload->handleUpload($files, 'team_submissions', $options);
            
            if ($result['success']) {
                // Create team submission record
                $submission = $this->createTeamSubmission($teamId, $phaseId, $submissionType, $title, $description, $result['data'][0]);
                
                if ($submission) {
                    return Response::json([
                        'success' => true,
                        'message' => 'Team submission uploaded successfully',
                        'submission_id' => $submission->id,
                        'file_info' => $result['data'][0]
                    ]);
                } else {
                    return Response::json(['error' => 'Failed to create submission record'], 500);
                }
            } else {
                return Response::json(['error' => $result['message']], 400);
            }
            
        } catch (Exception $e) {
            $this->logger->error('Team submission upload error: ' . $e->getMessage());
            return Response::json(['error' => 'Upload failed: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Upload profile photo
     */
    public function uploadProfilePhoto()
    {
        try {
            // Check authentication
            if (!$this->auth->isLoggedIn()) {
                return Response::json(['error' => 'Authentication required'], 401);
            }
            
            $userId = $this->auth->getId();
            
            // Get uploaded files
            $files = $_FILES['profile_photo'] ?? null;
            if (!$files) {
                return Response::json(['error' => 'No file uploaded'], 400);
            }
            
            // Set upload options
            $options = [
                'user_id' => $userId
            ];
            
            // Handle upload
            $result = $this->fileUpload->handleUpload($files, 'profile_photos', $options);
            
            if ($result['success']) {
                // Create uploaded file record
                $uploadedFile = UploadedFile::createFileRecord(
                    $result['data'][0],
                    'profile_photos',
                    $userId,
                    'App\\Models\\User',
                    $userId
                );
                
                if ($uploadedFile) {
                    // Update user's profile photo
                    $user = User::find($userId);
                    if ($user) {
                        $user->profile_photo = $result['data'][0]['relative_path'];
                        $user->save();
                    }
                    
                    return Response::json([
                        'success' => true,
                        'message' => 'Profile photo uploaded successfully',
                        'file_info' => $result['data'][0]
                    ]);
                } else {
                    return Response::json(['error' => 'Failed to create file record'], 500);
                }
            } else {
                return Response::json(['error' => $result['message']], 400);
            }
            
        } catch (Exception $e) {
            $this->logger->error('Profile photo upload error: ' . $e->getMessage());
            return Response::json(['error' => 'Upload failed: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Bulk upload files
     */
    public function bulkUpload()
    {
        try {
            // Check authentication and admin permission
            if (!$this->auth->isLoggedIn() || !$this->auth->hasRole('admin')) {
                return Response::json(['error' => 'Admin access required'], 403);
            }
            
            $uploadType = Request::input('upload_type');
            if (!$uploadType) {
                return Response::json(['error' => 'Upload type is required'], 400);
            }
            
            // Get uploaded files
            $files = $_FILES['bulk_files'] ?? null;
            if (!$files) {
                return Response::json(['error' => 'No files uploaded'], 400);
            }
            
            // Handle bulk upload
            $result = $this->fileUpload->handleUpload($files, $uploadType, []);
            
            if ($result['success']) {
                $successCount = count(array_filter($result['data'], fn($r) => $r['success']));
                
                return Response::json([
                    'success' => true,
                    'message' => "Successfully uploaded {$successCount} files",
                    'results' => $result['data']
                ]);
            } else {
                return Response::json(['error' => $result['message']], 400);
            }
            
        } catch (Exception $e) {
            $this->logger->error('Bulk upload error: ' . $e->getMessage());
            return Response::json(['error' => 'Bulk upload failed: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Download file with access control
     */
    public function downloadFile($fileId)
    {
        try {
            // Check authentication
            if (!$this->auth->isLoggedIn()) {
                return Response::json(['error' => 'Authentication required'], 401);
            }
            
            // Get file record
            $uploadedFile = UploadedFile::find($fileId);
            if (!$uploadedFile) {
                return Response::json(['error' => 'File not found'], 404);
            }
            
            // Check download permission
            if (!$this->canDownloadFile($uploadedFile)) {
                return Response::json(['error' => 'Permission denied'], 403);
            }
            
            // Check if file exists
            if (!$uploadedFile->fileExists()) {
                return Response::json(['error' => 'File not found on disk'], 404);
            }
            
            // Update download count
            $uploadedFile->incrementDownloadCount();
            
            // Stream file
            $fileManager = new \App\Core\FileManager();
            return $fileManager->streamFile(
                $uploadedFile->file_path,
                $uploadedFile->original_name,
                $uploadedFile->mime_type
            );
            
        } catch (Exception $e) {
            $this->logger->error('File download error: ' . $e->getMessage());
            return Response::json(['error' => 'Download failed'], 500);
        }
    }
    
    /**
     * Delete uploaded file
     */
    public function deleteFile($fileId)
    {
        try {
            // Check authentication
            if (!$this->auth->isLoggedIn()) {
                return Response::json(['error' => 'Authentication required'], 401);
            }
            
            // Get file record
            $uploadedFile = UploadedFile::find($fileId);
            if (!$uploadedFile) {
                return Response::json(['error' => 'File not found'], 404);
            }
            
            // Check delete permission
            if (!$this->canDeleteFile($uploadedFile)) {
                return Response::json(['error' => 'Permission denied'], 403);
            }
            
            // Delete file from disk
            $fileManager = new \App\Core\FileManager();
            if ($uploadedFile->fileExists()) {
                $fileManager->deleteFile($uploadedFile->file_path);
            }
            
            // Soft delete file record
            $uploadedFile->delete($fileId);
            
            return Response::json([
                'success' => true,
                'message' => 'File deleted successfully'
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('File deletion error: ' . $e->getMessage());
            return Response::json(['error' => 'Deletion failed'], 500);
        }
    }
    
    /**
     * Get upload progress (for AJAX uploads)
     */
    public function getUploadProgress($uploadId)
    {
        try {
            $progress = $this->fileUpload->getUploadProgress($uploadId);
            return Response::json($progress);
            
        } catch (Exception $e) {
            return Response::json(['error' => 'Could not get upload progress'], 500);
        }
    }
    
    /**
     * Get file information
     */
    public function getFileInfo($fileId)
    {
        try {
            // Check authentication
            if (!$this->auth->isLoggedIn()) {
                return Response::json(['error' => 'Authentication required'], 401);
            }
            
            // Get file record
            $uploadedFile = UploadedFile::find($fileId);
            if (!$uploadedFile) {
                return Response::json(['error' => 'File not found'], 404);
            }
            
            // Check view permission
            if (!$this->canViewFile($uploadedFile)) {
                return Response::json(['error' => 'Permission denied'], 403);
            }
            
            return Response::json([
                'success' => true,
                'file' => $uploadedFile->toArray()
            ]);
            
        } catch (Exception $e) {
            return Response::json(['error' => 'Could not get file info'], 500);
        }
    }
    
    // Permission checking methods
    
    /**
     * Check if user can upload for participant
     */
    protected function canUploadForParticipant($participantId)
    {
        $userId = $this->auth->getId();
        $userRole = $this->auth->getRole();
        
        // Admin can upload for anyone
        if ($userRole === 'admin') {
            return true;
        }
        
        // Get participant info
        $participant = Participant::find($participantId);
        if (!$participant) {
            return false;
        }
        
        // Team coach can upload for their team members
        if ($userRole === 'team_coach') {
            $team = Team::find($participant->team_id);
            return $team && ($team->coach1_id == $userId || $team->coach2_id == $userId);
        }
        
        // School coordinator can upload for their school's participants
        if ($userRole === 'school_coordinator') {
            $team = Team::find($participant->team_id);
            if ($team) {
                $user = User::find($userId);
                return $user && $user->school_id == $team->school_id;
            }
        }
        
        return false;
    }
    
    /**
     * Check if user can upload for team
     */
    protected function canUploadForTeam($teamId)
    {
        $userId = $this->auth->getId();
        $userRole = $this->auth->getRole();
        
        // Admin can upload for any team
        if ($userRole === 'admin') {
            return true;
        }
        
        $team = Team::find($teamId);
        if (!$team) {
            return false;
        }
        
        // Team coach can upload for their team
        if ($userRole === 'team_coach') {
            return $team->coach1_id == $userId || $team->coach2_id == $userId;
        }
        
        // School coordinator can upload for their school's teams
        if ($userRole === 'school_coordinator') {
            $user = User::find($userId);
            return $user && $user->school_id == $team->school_id;
        }
        
        return false;
    }
    
    /**
     * Check if user can download file
     */
    protected function canDownloadFile($uploadedFile)
    {
        $userId = $this->auth->getId();
        $userRole = $this->auth->getRole();
        
        // Admin can download any file
        if ($userRole === 'admin') {
            return true;
        }
        
        // File uploader can download their own files
        if ($uploadedFile->uploaded_by == $userId) {
            return true;
        }
        
        // Check access level
        switch ($uploadedFile->access_level) {
            case UploadedFile::ACCESS_PUBLIC:
                return true;
                
            case UploadedFile::ACCESS_SCHOOL:
                // Check if user is from same school
                return $this->isSameSchool($userId, $uploadedFile);
                
            case UploadedFile::ACCESS_TEAM:
                // Check if user is from same team
                return $this->isSameTeam($userId, $uploadedFile);
                
            case UploadedFile::ACCESS_PRIVATE:
            case UploadedFile::ACCESS_ADMIN:
            default:
                return false;
        }
    }
    
    /**
     * Check if user can delete file
     */
    protected function canDeleteFile($uploadedFile)
    {
        $userId = $this->auth->getId();
        $userRole = $this->auth->getRole();
        
        // Admin can delete any file
        if ($userRole === 'admin') {
            return true;
        }
        
        // File uploader can delete their own files
        return $uploadedFile->uploaded_by == $userId;
    }
    
    /**
     * Check if user can view file info
     */
    protected function canViewFile($uploadedFile)
    {
        return $this->canDownloadFile($uploadedFile);
    }
    
    // Helper methods
    
    /**
     * Get participant's school ID
     */
    protected function getParticipantSchoolId($participantId)
    {
        $participant = Participant::find($participantId);
        if ($participant) {
            $team = Team::find($participant->team_id);
            return $team ? $team->school_id : null;
        }
        return null;
    }
    
    /**
     * Get participant's team ID
     */
    protected function getParticipantTeamId($participantId)
    {
        $participant = Participant::find($participantId);
        return $participant ? $participant->team_id : null;
    }
    
    /**
     * Get team's school ID
     */
    protected function getTeamSchoolId($teamId)
    {
        $team = Team::find($teamId);
        return $team ? $team->school_id : null;
    }
    
    /**
     * Create or update consent form record
     */
    protected function createOrUpdateConsentForm($participantId, $formType, $fileData)
    {
        // Check if consent form already exists
        $existingForm = ConsentForm::where('participant_id', $participantId)
            ->where('form_type', $formType)
            ->first();
            
        if ($existingForm) {
            // Update existing form
            $existingForm->file_path = $fileData['relative_path'];
            $existingForm->file_name = $fileData['filename'];
            $existingForm->file_size = $fileData['size'];
            $existingForm->file_type = $fileData['type'];
            $existingForm->status = ConsentForm::STATUS_PENDING;
            $existingForm->submitted_date = date('Y-m-d H:i:s');
            $existingForm->metadata = json_encode($fileData['metadata']);
            
            // Create uploaded file record
            $uploadedFile = UploadedFile::createFileRecord(
                $fileData,
                'consent_forms',
                $this->auth->getId(),
                'App\\Models\\ConsentForm',
                $existingForm->id
            );
            
            if ($uploadedFile) {
                $existingForm->uploaded_file_id = $uploadedFile->id;
            }
            
            $existingForm->save();
            return $existingForm;
        } else {
            // Create new form
            $consentForm = new ConsentForm();
            $consentForm->participant_id = $participantId;
            $consentForm->form_type = $formType;
            $consentForm->file_path = $fileData['relative_path'];
            $consentForm->file_name = $fileData['filename'];
            $consentForm->file_size = $fileData['size'];
            $consentForm->file_type = $fileData['type'];
            $consentForm->status = ConsentForm::STATUS_PENDING;
            $consentForm->submitted_date = date('Y-m-d H:i:s');
            $consentForm->metadata = json_encode($fileData['metadata']);
            
            $savedForm = $consentForm->save();
            
            if ($savedForm) {
                // Create uploaded file record
                $uploadedFile = UploadedFile::createFileRecord(
                    $fileData,
                    'consent_forms',
                    $this->auth->getId(),
                    'App\\Models\\ConsentForm',
                    $savedForm->id
                );
                
                if ($uploadedFile) {
                    $savedForm->uploaded_file_id = $uploadedFile->id;
                    $savedForm->save();
                }
                
                return $savedForm;
            }
        }
        
        return null;
    }
    
    /**
     * Create team submission record
     */
    protected function createTeamSubmission($teamId, $phaseId, $submissionType, $title, $description, $fileData)
    {
        $submission = new TeamSubmission();
        $submission->team_id = $teamId;
        $submission->phase_id = $phaseId;
        $submission->submission_type = $submissionType;
        $submission->title = $title;
        $submission->description = $description;
        $submission->file_path = $fileData['relative_path'];
        $submission->file_name = $fileData['filename'];
        $submission->file_size = $fileData['size'];
        $submission->file_type = $fileData['type'];
        $submission->status = TeamSubmission::STATUS_DRAFT;
        $submission->metadata = json_encode($fileData['metadata']);
        
        $savedSubmission = $submission->save();
        
        if ($savedSubmission) {
            // Create uploaded file record
            $uploadedFile = UploadedFile::createFileRecord(
                $fileData,
                'team_submissions',
                $this->auth->getId(),
                'App\\Models\\TeamSubmission',
                $savedSubmission->id
            );
            
            if ($uploadedFile) {
                $savedSubmission->uploaded_file_id = $uploadedFile->id;
                $savedSubmission->save();
            }
            
            return $savedSubmission;
        }
        
        return null;
    }
    
    /**
     * Check if user is from same school as file's related entity
     */
    protected function isSameSchool($userId, $uploadedFile)
    {
        // TODO: Implement school-based access check
        return false;
    }
    
    /**
     * Check if user is from same team as file's related entity
     */
    protected function isSameTeam($userId, $uploadedFile)
    {
        // TODO: Implement team-based access check
        return false;
    }
}