<?php

namespace App\Models;

use App\Models\BaseModel;
use PDO;

/**
 * Team Composition Model
 * Handles team size validation and composition management
 */
class TeamComposition extends BaseModel
{
    protected $table = 'team_compositions';
    protected $fillable = [
        'team_id',
        'max_participants',
        'current_participant_count',
        'composition_status',
        'last_validated_at',
        'validation_errors',
        'category_specific_rules'
    ];
    
    protected $casts = [
        'validation_errors' => 'json',
        'category_specific_rules' => 'json',
        'last_validated_at' => 'datetime'
    ];
    
    /**
     * Get team associated with this composition
     */
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
    
    /**
     * Get team participants through the team relationship
     */
    public function participants()
    {
        return TeamParticipant::where('team_id', $this->team_id)
                             ->where('status', 'active')
                             ->get();
    }
    
    /**
     * Validate team size against category rules
     */
    public function validateTeamSize()
    {
        $currentCount = $this->getCurrentParticipantCount();
        $maxAllowed = $this->getMaxParticipants();
        $minRequired = $this->getMinParticipants();
        
        $validation = [
            'is_valid' => $currentCount >= $minRequired && $currentCount <= $maxAllowed,
            'current_count' => $currentCount,
            'max_allowed' => $maxAllowed,
            'min_required' => $minRequired,
            'can_add_more' => $currentCount < $maxAllowed,
            'slots_available' => $maxAllowed - $currentCount,
            'status' => $this->determineCompositionStatus($currentCount, $minRequired, $maxAllowed)
        ];
        
        $this->updateValidationResults($validation);
        
        return $validation;
    }
    
    /**
     * Get current participant count from database
     */
    public function getCurrentParticipantCount()
    {
        $count = TeamParticipant::where('team_id', $this->team_id)
                               ->where('status', 'active')
                               ->count();
        
        // Update the cached count
        $this->current_participant_count = $count;
        $this->save();
        
        return $count;
    }
    
    /**
     * Get maximum participants allowed
     */
    public function getMaxParticipants()
    {
        // Check if there are category-specific rules
        if ($this->category_specific_rules && isset($this->category_specific_rules['max_participants'])) {
            return $this->category_specific_rules['max_participants'];
        }
        
        return $this->max_participants;
    }
    
    /**
     * Get minimum participants required
     */
    public function getMinParticipants()
    {
        // Check if there are category-specific rules
        if ($this->category_specific_rules && isset($this->category_specific_rules['min_participants'])) {
            return $this->category_specific_rules['min_participants'];
        }
        
        // Default minimum is 2 for team formation
        return 2;
    }
    
    /**
     * Determine composition status based on participant count
     */
    private function determineCompositionStatus($currentCount, $minRequired, $maxAllowed)
    {
        if ($currentCount > $maxAllowed) {
            return 'oversize';
        } elseif ($currentCount < $minRequired) {
            return 'incomplete';
        } elseif ($currentCount >= $minRequired && $currentCount <= $maxAllowed) {
            // Additional validation for eligibility
            if ($this->areAllParticipantsEligible()) {
                return 'complete';
            } else {
                return 'invalid';
            }
        }
        
        return 'invalid';
    }
    
    /**
     * Check if all participants are eligible
     */
    public function areAllParticipantsEligible()
    {
        $ineligibleCount = TeamParticipant::where('team_id', $this->team_id)
                                         ->where('status', 'active')
                                         ->where('eligibility_status', 'ineligible')
                                         ->count();
        
        return $ineligibleCount === 0;
    }
    
    /**
     * Update validation results
     */
    private function updateValidationResults($validation)
    {
        $this->composition_status = $validation['status'];
        $this->current_participant_count = $validation['current_count'];
        $this->last_validated_at = date('Y-m-d H:i:s');
        
        if (!$validation['is_valid']) {
            $this->validation_errors = [
                'team_size' => !($validation['current_count'] >= $validation['min_required'] && $validation['current_count'] <= $validation['max_allowed']),
                'participant_eligibility' => !$this->areAllParticipantsEligible(),
                'document_completion' => !$this->areAllDocumentsComplete()
            ];
        } else {
            $this->validation_errors = null;
        }
        
        $this->save();
    }
    
    /**
     * Check if all required documents are complete
     */
    public function areAllDocumentsComplete()
    {
        $incompleteCount = TeamParticipant::where('team_id', $this->team_id)
                                         ->where('status', 'active')
                                         ->where('documents_complete', false)
                                         ->count();
        
        return $incompleteCount === 0;
    }
    
    /**
     * Add participant to team with validation
     */
    public function addParticipant($participantId, $role = 'regular')
    {
        // Validate team size before adding
        $validation = $this->validateTeamSize();
        
        if (!$validation['can_add_more']) {
            throw new \Exception('Cannot add participant: team has reached maximum capacity');
        }
        
        // Check if participant is already in this team
        $existingMember = TeamParticipant::where('team_id', $this->team_id)
                                        ->where('participant_id', $participantId)
                                        ->where('status', 'active')
                                        ->first();
        
        if ($existingMember) {
            throw new \Exception('Participant is already a member of this team');
        }
        
        // Create team participant record
        $teamParticipant = new TeamParticipant([
            'team_id' => $this->team_id,
            'participant_id' => $participantId,
            'role' => $role,
            'status' => 'active',
            'joined_date' => date('Y-m-d'),
            'eligibility_status' => 'pending'
        ]);
        
        $teamParticipant->save();
        
        // Re-validate after adding
        $this->validateTeamSize();
        
        return $teamParticipant;
    }
    
    /**
     * Remove participant from team
     */
    public function removeParticipant($participantId, $reason = 'voluntary')
    {
        $teamParticipant = TeamParticipant::where('team_id', $this->team_id)
                                         ->where('participant_id', $participantId)
                                         ->where('status', 'active')
                                         ->first();
        
        if (!$teamParticipant) {
            throw new \Exception('Participant not found in team');
        }
        
        $teamParticipant->status = 'removed';
        $teamParticipant->removed_date = date('Y-m-d');
        $teamParticipant->removal_reason = $reason;
        $teamParticipant->save();
        
        // Re-validate after removing
        $this->validateTeamSize();
        
        return true;
    }
    
    /**
     * Set category-specific rules
     */
    public function setCategoryRules($categoryId)
    {
        // Get category from database
        $category = Category::find($categoryId);
        
        if ($category) {
            $rules = [
                'max_participants' => $category->team_size ?? 4,
                'min_participants' => 2,
                'category_name' => $category->name,
                'age_restrictions' => [
                    'min_age' => $category->min_age ?? null,
                    'max_age' => $category->max_age ?? null
                ],
                'grade_restrictions' => [
                    'min_grade' => $category->min_grade ?? null,
                    'max_grade' => $category->max_grade ?? null
                ]
            ];
            
            $this->category_specific_rules = $rules;
            $this->max_participants = $rules['max_participants'];
            $this->save();
        }
        
        return $this;
    }
    
    /**
     * Get team composition summary
     */
    public function getCompositionSummary()
    {
        $validation = $this->validateTeamSize();
        $participants = $this->participants();
        
        return [
            'team_id' => $this->team_id,
            'status' => $this->composition_status,
            'participant_count' => $validation['current_count'],
            'max_allowed' => $validation['max_allowed'],
            'min_required' => $validation['min_required'],
            'slots_available' => $validation['slots_available'],
            'is_complete' => $validation['is_valid'],
            'participants' => $participants->map(function($p) {
                return [
                    'id' => $p->participant_id,
                    'role' => $p->role,
                    'status' => $p->status,
                    'eligibility' => $p->eligibility_status,
                    'documents_complete' => $p->documents_complete
                ];
            })->toArray(),
            'last_validated' => $this->last_validated_at,
            'validation_errors' => $this->validation_errors
        ];
    }
    
    /**
     * Create team composition for a team
     */
    public static function createForTeam($teamId, $maxParticipants = 4)
    {
        // Check if composition already exists
        $existing = static::where('team_id', $teamId)->first();
        
        if ($existing) {
            return $existing;
        }
        
        $composition = new static([
            'team_id' => $teamId,
            'max_participants' => $maxParticipants,
            'current_participant_count' => 0,
            'composition_status' => 'incomplete'
        ]);
        
        $composition->save();
        
        return $composition;
    }
    
    /**
     * Get compositions that need validation
     */
    public static function getNeedingValidation()
    {
        return static::where('last_validated_at', '<', date('Y-m-d H:i:s', strtotime('-1 hour')))
                    ->orWhereNull('last_validated_at')
                    ->get();
    }
    
    /**
     * Bulk validate compositions
     */
    public static function bulkValidate()
    {
        $compositions = static::getNeedingValidation();
        $results = [];
        
        foreach ($compositions as $composition) {
            $results[] = [
                'team_id' => $composition->team_id,
                'validation' => $composition->validateTeamSize()
            ];
        }
        
        return $results;
    }
}