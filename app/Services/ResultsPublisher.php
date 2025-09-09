<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Tournament;
use App\Models\TournamentResult;
use App\Models\Team;
use App\Core\Mail;
use Exception;

/**
 * ResultsPublisher Service
 * 
 * Handles results compilation, publication, and distribution
 * for GDE SciBOTICS 2025 tournaments.
 */
class ResultsPublisher
{
    private $db;
    private $mailer;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->mailer = new Mail();
    }
    
    /**
     * Generate tournament results from completed matches
     */
    public function generateTournamentResults($tournamentId)
    {
        $tournament = $this->getTournament($tournamentId);
        
        if (!$tournament || $tournament['status'] !== 'completed') {
            throw new Exception("Tournament not found or not completed");
        }
        
        // Clear existing results
        $this->db->table('tournament_results')
            ->where('tournament_id', $tournamentId)
            ->delete();
        
        $results = [];
        
        if ($tournament['tournament_type'] === 'round_robin') {
            $results = $this->generateRoundRobinResults($tournamentId);
        } else {
            $results = $this->generateEliminationResults($tournamentId);
        }
        
        // Save results to database
        foreach ($results as $result) {
            $this->db->table('tournament_results')->insert(array_merge($result, [
                'tournament_id' => $tournamentId,
                'category_id' => $tournament['category_id'],
                'published' => false,
                'created_at' => date('Y-m-d H:i:s')
            ]));
        }
        
        return $results;
    }
    
    /**
     * Generate round-robin results from standings
     */
    private function generateRoundRobinResults($tournamentId)
    {
        $standings = $this->db->query("
            SELECT rrs.*, t.id as team_id, t.name as team_name
            FROM round_robin_standings rrs
            JOIN teams t ON rrs.team_id = t.id
            WHERE rrs.tournament_id = ?
            ORDER BY rrs.ranking ASC
        ", [$tournamentId]);
        
        $results = [];
        
        foreach ($standings as $standing) {
            $results[] = [
                'placement' => $standing['ranking'],
                'team_id' => $standing['team_id'],
                'team_score' => $standing['league_points'],
                'medal_type' => $this->getMedalType($standing['ranking']),
                'prize_description' => $this->getPrizeDescription($standing['ranking']),
                'certificate_number' => $this->generateCertificateNumber($standing['team_id'], $standing['ranking'])
            ];
        }
        
        return $results;
    }
    
    /**
     * Generate elimination results from bracket winners
     */
    private function generateEliminationResults($tournamentId)
    {
        // Get final bracket results
        $finalMatches = $this->db->query("
            SELECT tm.*, tb.round_name, tb.round_number
            FROM tournament_matches tm
            JOIN tournament_brackets tb ON tm.bracket_id = tb.id
            WHERE tm.tournament_id = ?
            AND tb.round_number = (
                SELECT MAX(round_number) 
                FROM tournament_brackets 
                WHERE tournament_id = ?
            )
            AND tm.match_status = 'completed'
            ORDER BY tm.match_number
        ", [$tournamentId, $tournamentId]);
        
        $results = [];
        $placement = 1;
        
        foreach ($finalMatches as $match) {
            // Winner gets current placement
            if ($match['winner_team_id']) {
                $results[] = [
                    'placement' => $placement,
                    'team_id' => $match['winner_team_id'],
                    'team_score' => $this->getTeamTotalScore($match['winner_team_id']),
                    'medal_type' => $this->getMedalType($placement),
                    'prize_description' => $this->getPrizeDescription($placement),
                    'certificate_number' => $this->generateCertificateNumber($match['winner_team_id'], $placement)
                ];
                $placement++;
                
                // Loser gets next placement
                if ($match['loser_team_id']) {
                    $results[] = [
                        'placement' => $placement,
                        'team_id' => $match['loser_team_id'],
                        'team_score' => $this->getTeamTotalScore($match['loser_team_id']),
                        'medal_type' => $this->getMedalType($placement),
                        'prize_description' => $this->getPrizeDescription($placement),
                        'certificate_number' => $this->generateCertificateNumber($match['loser_team_id'], $placement)
                    ];
                    $placement++;
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Publish results to various channels
     */
    public function publishResults($tournamentId, $channels = ['website'])
    {
        $tournament = $this->getTournament($tournamentId);
        $results = $this->getResults($tournamentId);
        
        if (empty($results)) {
            throw new Exception("No results found for tournament");
        }
        
        $publicationId = $this->createPublicationRecord($tournamentId, $channels);
        
        $published = [];
        
        foreach ($channels as $channel) {
            switch ($channel) {
                case 'website':
                    $this->publishToWebsite($tournamentId, $results);
                    $published[] = 'website';
                    break;
                    
                case 'email':
                    $this->publishToEmail($tournamentId, $results);
                    $published[] = 'email';
                    break;
                    
                case 'social':
                    $this->publishToSocialMedia($tournamentId, $results);
                    $published[] = 'social';
                    break;
                    
                case 'print':
                    $documentPath = $this->generatePrintDocument($tournamentId, $results);
                    $published[] = 'print';
                    break;
            }
        }
        
        // Update publication record
        $this->updatePublicationRecord($publicationId, [
            'publication_status' => 'published',
            'published_at' => date('Y-m-d H:i:s'),
            'document_path' => $documentPath ?? null
        ]);
        
        return [
            'publication_id' => $publicationId,
            'channels' => $published,
            'results_count' => count($results)
        ];
    }
    
    /**
     * Publish results to website (mark as published)
     */
    private function publishToWebsite($tournamentId, $results)
    {
        // Mark all results as published
        $this->db->table('tournament_results')
            ->where('tournament_id', $tournamentId)
            ->update([
                'published' => true,
                'published_at' => date('Y-m-d H:i:s')
            ]);
        
        return true;
    }
    
    /**
     * Send results via email
     */
    private function publishToEmail($tournamentId, $results)
    {
        $tournament = $this->getTournament($tournamentId);
        
        // Get all school coordinators for teams in this tournament
        $recipients = $this->db->query("
            SELECT DISTINCT u.email, u.first_name, u.last_name, s.name as school_name
            FROM users u
            JOIN schools s ON u.school_id = s.id
            JOIN teams t ON s.id = t.school_id
            JOIN tournament_results tr ON t.id = tr.team_id
            WHERE tr.tournament_id = ?
            AND u.role = 'school_coordinator'
            AND u.email IS NOT NULL
        ", [$tournamentId]);
        
        $subject = "GDE SciBOTICS 2025 Results - {$tournament['tournament_name']}";
        $template = $this->generateEmailTemplate($tournament, $results);
        
        $sentCount = 0;
        
        foreach ($recipients as $recipient) {
            try {
                $personalizedTemplate = str_replace(
                    '{{SCHOOL_NAME}}', 
                    $recipient['school_name'], 
                    $template
                );
                
                $this->mailer->send(
                    $recipient['email'],
                    $subject,
                    $personalizedTemplate,
                    [
                        'recipient_name' => $recipient['first_name'] . ' ' . $recipient['last_name']
                    ]
                );
                
                $sentCount++;
                
            } catch (Exception $e) {
                error_log("Failed to send results email to {$recipient['email']}: " . $e->getMessage());
            }
        }
        
        return $sentCount;
    }
    
    /**
     * Generate email template for results
     */
    private function generateEmailTemplate($tournament, $results)
    {
        $html = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #1a472a; border-bottom: 2px solid #28a745; padding-bottom: 10px;'>
                    🏆 GDE SciBOTICS Competition 2025 Results
                </h2>
                
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <h3 style='margin: 0; color: #495057;'>{$tournament['tournament_name']}</h3>
                    <p style='margin: 5px 0 0 0; color: #6c757d;'>
                        Category: {$tournament['category_name']} | 
                        Date: " . date('d M Y', strtotime($tournament['end_date'])) . "
                    </p>
                </div>
                
                <h3 style='color: #1a472a;'>🥇 Final Results</h3>
                <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                    <thead>
                        <tr style='background: #28a745; color: white;'>
                            <th style='padding: 12px; text-align: left; border: 1px solid #ddd;'>Place</th>
                            <th style='padding: 12px; text-align: left; border: 1px solid #ddd;'>Team</th>
                            <th style='padding: 12px; text-align: left; border: 1px solid #ddd;'>School</th>
                            <th style='padding: 12px; text-align: left; border: 1px solid #ddd;'>Score</th>
                            <th style='padding: 12px; text-align: left; border: 1px solid #ddd;'>Award</th>
                        </tr>
                    </thead>
                    <tbody>";
        
        foreach (array_slice($results, 0, 10) as $index => $result) {
            $medalEmoji = match($result['placement']) {
                1 => '🥇',
                2 => '🥈', 
                3 => '🥉',
                default => '🏅'
            };
            
            $rowColor = $index % 2 == 0 ? '#f8f9fa' : 'white';
            
            $html .= "
                        <tr style='background: {$rowColor};'>
                            <td style='padding: 10px; border: 1px solid #ddd;'>{$medalEmoji} {$result['placement']}</td>
                            <td style='padding: 10px; border: 1px solid #ddd; font-weight: bold;'>{$result['team_name']}</td>
                            <td style='padding: 10px; border: 1px solid #ddd;'>{$result['school_name']}</td>
                            <td style='padding: 10px; border: 1px solid #ddd;'>{$result['team_score']}</td>
                            <td style='padding: 10px; border: 1px solid #ddd;'>{$result['medal_type']}</td>
                        </tr>";
        }
        
        $html .= "
                    </tbody>
                </table>
                
                <div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 20px 0;'>
                    <p style='margin: 0; color: #155724;'>
                        <strong>Congratulations to all participating teams!</strong><br>
                        Complete results and certificates are available on the competition portal.
                    </p>
                </div>
                
                <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #6c757d;'>
                    <p>This email was sent from the GDE SciBOTICS Competition Management System.</p>
                    <p>For any queries, please contact the competition organizers.</p>
                </div>
            </div>
        </body>
        </html>";
        
        return $html;
    }
    
    /**
     * Generate printable PDF document
     */
    public function generatePrintDocument($tournamentId, $results = null)
    {
        $tournament = $this->getTournament($tournamentId);
        $results = $results ?: $this->getResults($tournamentId);
        
        // Create HTML content for PDF generation
        $html = $this->generatePrintHTML($tournament, $results);
        
        // Save as HTML file (PDF generation would require additional library)
        $filename = "tournament_results_{$tournamentId}_" . date('YmdHis') . ".html";
        $filepath = 'storage/results/' . $filename;
        
        // Ensure directory exists
        if (!is_dir('storage/results/')) {
            mkdir('storage/results/', 0755, true);
        }
        
        file_put_contents($filepath, $html);
        
        return $filepath;
    }
    
    /**
     * Generate HTML for printing/PDF
     */
    private function generatePrintHTML($tournament, $results)
    {
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Tournament Results - {$tournament['tournament_name']}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; border-bottom: 3px solid #28a745; padding-bottom: 20px; margin-bottom: 30px; }
                .logo { font-size: 24px; font-weight: bold; color: #1a472a; }
                .tournament-info { background: #f8f9fa; padding: 15px; margin: 20px 0; border-radius: 5px; }
                .results-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .results-table th, .results-table td { padding: 12px; text-align: left; border: 1px solid #ddd; }
                .results-table th { background: #28a745; color: white; }
                .results-table tr:nth-child(even) { background: #f8f9fa; }
                .medal-gold { background: #ffd700 !important; }
                .medal-silver { background: #c0c0c0 !important; }
                .medal-bronze { background: #cd7f32 !important; }
                .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #666; }
                @media print {
                    body { margin: 15px; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class='header'>
                <div class='logo'>🏆 GDE SciBOTICS Competition 2025</div>
                <h2>Official Tournament Results</h2>
            </div>
            
            <div class='tournament-info'>
                <h3>{$tournament['tournament_name']}</h3>
                <p><strong>Category:</strong> {$tournament['category_name']}</p>
                <p><strong>Date:</strong> " . date('d M Y', strtotime($tournament['end_date'])) . "</p>
                <p><strong>Venue:</strong> {$tournament['venue_name']}</p>
                <p><strong>Total Teams:</strong> " . count($results) . "</p>
            </div>
            
            <table class='results-table'>
                <thead>
                    <tr>
                        <th>Position</th>
                        <th>Team Name</th>
                        <th>Team Code</th>
                        <th>School</th>
                        <th>Final Score</th>
                        <th>Medal</th>
                        <th>Certificate No.</th>
                    </tr>
                </thead>
                <tbody>";
        
        foreach ($results as $result) {
            $medalClass = match($result['placement']) {
                1 => 'medal-gold',
                2 => 'medal-silver',
                3 => 'medal-bronze',
                default => ''
            };
            
            $medalText = match($result['medal_type']) {
                'gold' => '🥇 Gold',
                'silver' => '🥈 Silver',
                'bronze' => '🥉 Bronze',
                default => ''
            };
            
            $html .= "
                    <tr class='{$medalClass}'>
                        <td><strong>{$result['placement']}</strong></td>
                        <td><strong>{$result['team_name']}</strong></td>
                        <td>{$result['team_code']}</td>
                        <td>{$result['school_name']}</td>
                        <td>{$result['team_score']}</td>
                        <td>{$medalText}</td>
                        <td>{$result['certificate_number']}</td>
                    </tr>";
        }
        
        $html .= "
                </tbody>
            </table>
            
            <div class='footer'>
                <p>Generated on " . date('d M Y H:i:s') . "</p>
                <p>GDE SciBOTICS Competition Management System</p>
            </div>
        </body>
        </html>";
        
        return $html;
    }
    
    // Helper methods
    
    private function getTournament($tournamentId)
    {
        return $this->db->query("
            SELECT t.*, 
                   c.name as category_name,
                   v.name as venue_name
            FROM tournaments t
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN venues v ON t.venue_id = v.id
            WHERE t.id = ?
        ", [$tournamentId])[0] ?? null;
    }
    
    private function getResults($tournamentId)
    {
        return $this->db->query("
            SELECT tr.*, 
                   t.name as team_name, t.team_code,
                   s.name as school_name
            FROM tournament_results tr
            JOIN teams t ON tr.team_id = t.id
            JOIN schools s ON t.school_id = s.id
            WHERE tr.tournament_id = ?
            ORDER BY tr.placement ASC
        ", [$tournamentId]);
    }
    
    private function getMedalType($placement)
    {
        return match($placement) {
            1 => 'gold',
            2 => 'silver', 
            3 => 'bronze',
            default => 'none'
        };
    }
    
    private function getPrizeDescription($placement)
    {
        return match($placement) {
            1 => 'First Place - Champion',
            2 => 'Second Place - Runner-up',
            3 => 'Third Place',
            default => "Position {$placement}"
        };
    }
    
    private function generateCertificateNumber($teamId, $placement)
    {
        return sprintf(
            'GDE2025-%s-%03d-%02d',
            date('md'),
            $teamId,
            $placement
        );
    }
    
    private function getTeamTotalScore($teamId)
    {
        $score = $this->db->query("
            SELECT SUM(total_score) as total
            FROM scores
            WHERE team_id = ?
        ", [$teamId])[0]['total'] ?? 0;
        
        return $score;
    }
    
    private function createPublicationRecord($tournamentId, $channels)
    {
        return $this->db->table('results_publications')->insertGetId([
            'tournament_id' => $tournamentId,
            'publication_type' => 'official',
            'publication_channel' => implode(',', $channels),
            'published_by' => $_SESSION['user_id'] ?? 1,
            'publication_status' => 'draft',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    private function updatePublicationRecord($publicationId, $data)
    {
        return $this->db->table('results_publications')
            ->where('id', $publicationId)
            ->update($data);
    }
    
    private function publishToSocialMedia($tournamentId, $results)
    {
        // Social media integration would be implemented here
        // For now, just return success
        return true;
    }
}