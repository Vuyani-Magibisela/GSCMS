<?php

namespace App\Controllers\Documents;

use App\Controllers\BaseController;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Encryption;
use App\Models\DigitalSignature;
use App\Models\ConsentForm;
use App\Models\User;
use Exception;

class DigitalSignatureController extends BaseController
{
    protected $auth;
    protected $db;
    protected $encryption;
    
    public function __construct()
    {
        parent::__construct();
        $this->auth = Auth::getInstance();
        $this->db = Database::getInstance();
        $this->encryption = new Encryption();
    }
    
    /**
     * Capture digital signature from web interface
     */
    public function captureSignature()
    {
        try {
            // Validate request
            $documentId = Request::input('document_id');
            $documentType = Request::input('document_type');
            $signatureData = Request::input('signature_data');
            $signerName = Request::input('signer_name');
            $signerEmail = Request::input('signer_email');
            $signerRole = Request::input('signer_role');
            $intentStatement = Request::input('intent_statement');
            
            if (!$documentId || !$documentType || !$signatureData || !$signerName) {
                return Response::json(['error' => 'Required signature data missing'], 400);
            }
            
            // Verify user can sign this document
            if (!$this->canSignDocument($documentId, $documentType)) {
                return Response::json(['error' => 'Permission denied to sign this document'], 403);
            }
            
            // Validate signature data format and quality
            $signatureValidation = $this->validateSignatureData($signatureData);
            if (!$signatureValidation['valid']) {
                return Response::json(['error' => 'Invalid signature data: ' . $signatureValidation['reason']], 400);
            }
            
            // Create signature record
            $signatureRecord = $this->createDigitalSignature([
                'document_id' => $documentId,
                'document_type' => $documentType,
                'signature_data' => $signatureData,
                'signer_name' => $signerName,
                'signer_email' => $signerEmail,
                'signer_role' => $signerRole,
                'intent_statement' => $intentStatement,
                'signature_method' => 'web_capture',
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'timestamp' => date('Y-m-d H:i:s'),
                'quality_score' => $signatureValidation['quality_score']
            ]);
            
            if ($signatureRecord) {
                // Update the related document
                $this->updateDocumentWithSignature($documentId, $documentType, $signatureRecord->id);
                
                // Generate verification hash for legal compliance
                $verificationHash = $this->generateVerificationHash($signatureRecord);
                
                // Log signature capture
                $this->logger->info("Digital signature captured", [
                    'document_id' => $documentId,
                    'document_type' => $documentType,
                    'signer' => $signerName,
                    'signature_id' => $signatureRecord->id,
                    'verification_hash' => $verificationHash
                ]);
                
                return Response::json([
                    'success' => true,
                    'message' => 'Signature captured successfully',
                    'signature_id' => $signatureRecord->id,
                    'verification_hash' => $verificationHash,
                    'legal_binding_confirmed' => true
                ]);
            }
            
            return Response::json(['error' => 'Failed to save signature'], 500);
            
        } catch (Exception $e) {
            $this->logger->error('Signature capture error: ' . $e->getMessage());
            return Response::json(['error' => 'Signature capture failed'], 500);
        }
    }
    
    /**
     * Verify digital signature authenticity and integrity
     */
    public function verifySignature()
    {
        try {
            $signatureId = Request::input('signature_id');
            $verificationHash = Request::input('verification_hash');
            
            if (!$signatureId) {
                return Response::json(['error' => 'Signature ID is required'], 400);
            }
            
            // Get signature record
            $signature = DigitalSignature::find($signatureId);
            if (!$signature) {
                return Response::json(['error' => 'Signature not found'], 404);
            }
            
            // Perform comprehensive verification
            $verification = $this->performSignatureVerification($signature, $verificationHash);
            
            // Update verification status
            $signature->verification_status = $verification['status'];
            $signature->verification_details = json_encode($verification['details']);
            $signature->verified_at = date('Y-m-d H:i:s');
            $signature->verified_by = $this->auth->getId();
            $signature->save();
            
            // Log verification attempt
            $this->logger->info("Signature verification performed", [
                'signature_id' => $signatureId,
                'verification_status' => $verification['status'],
                'verified_by' => $this->auth->getId(),
                'integrity_check' => $verification['integrity_check']
            ]);
            
            return Response::json([
                'success' => true,
                'verification' => $verification,
                'legal_validity' => $this->assessLegalValidity($verification),
                'compliance_status' => $this->checkComplianceStatus($signature)
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Signature verification error: ' . $e->getMessage());
            return Response::json(['error' => 'Verification failed'], 500);
        }
    }
    
    /**
     * Process uploaded signature images
     */
    public function processUploadedSignature()
    {
        try {
            $documentId = Request::input('document_id');
            $documentType = Request::input('document_type');
            $signerInfo = Request::input('signer_info');
            
            // Handle file upload
            if (!isset($_FILES['signature_image'])) {
                return Response::json(['error' => 'No signature image uploaded'], 400);
            }
            
            $signatureFile = $_FILES['signature_image'];
            
            // Validate image file
            $imageValidation = $this->validateSignatureImage($signatureFile);
            if (!$imageValidation['valid']) {
                return Response::json(['error' => $imageValidation['error']], 400);
            }
            
            // Process and enhance signature image
            $processedImage = $this->processSignatureImage($signatureFile);
            
            // Extract signature features for verification
            $signatureFeatures = $this->extractSignatureFeatures($processedImage);
            
            // Create signature record
            $signatureRecord = $this->createDigitalSignature([
                'document_id' => $documentId,
                'document_type' => $documentType,
                'signature_data' => $processedImage['data'],
                'signature_features' => json_encode($signatureFeatures),
                'signer_name' => $signerInfo['name'],
                'signer_email' => $signerInfo['email'],
                'signature_method' => 'image_upload',
                'original_filename' => $signatureFile['name'],
                'image_metadata' => json_encode($processedImage['metadata']),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            if ($signatureRecord) {
                return Response::json([
                    'success' => true,
                    'message' => 'Signature image processed successfully',
                    'signature_id' => $signatureRecord->id,
                    'quality_assessment' => $processedImage['quality']
                ]);
            }
            
            return Response::json(['error' => 'Failed to process signature'], 500);
            
        } catch (Exception $e) {
            $this->logger->error('Signature upload processing error: ' . $e->getMessage());
            return Response::json(['error' => 'Processing failed'], 500);
        }
    }
    
    /**
     * Integration with DocuSign (optional)
     */
    public function docusignIntegration()
    {
        try {
            $documentId = Request::input('document_id');
            $documentType = Request::input('document_type');
            $signerEmail = Request::input('signer_email');
            $signerName = Request::input('signer_name');
            
            if (!$documentId || !$signerEmail || !$signerName) {
                return Response::json(['error' => 'Required DocuSign parameters missing'], 400);
            }
            
            // Check if DocuSign is configured
            if (!$this->isDocuSignConfigured()) {
                return Response::json(['error' => 'DocuSign integration not configured'], 503);
            }
            
            // Create DocuSign envelope
            $envelope = $this->createDocuSignEnvelope($documentId, $documentType, [
                'signer_email' => $signerEmail,
                'signer_name' => $signerName
            ]);
            
            if ($envelope['success']) {
                // Save DocuSign reference
                $signatureRecord = $this->createDigitalSignature([
                    'document_id' => $documentId,
                    'document_type' => $documentType,
                    'signature_method' => 'docusign',
                    'docusign_envelope_id' => $envelope['envelope_id'],
                    'signer_name' => $signerName,
                    'signer_email' => $signerEmail,
                    'status' => 'sent',
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                
                return Response::json([
                    'success' => true,
                    'message' => 'DocuSign envelope created and sent',
                    'envelope_id' => $envelope['envelope_id'],
                    'signing_url' => $envelope['signing_url'],
                    'signature_id' => $signatureRecord->id
                ]);
            }
            
            return Response::json(['error' => 'Failed to create DocuSign envelope'], 500);
            
        } catch (Exception $e) {
            $this->logger->error('DocuSign integration error: ' . $e->getMessage());
            return Response::json(['error' => 'DocuSign integration failed'], 500);
        }
    }
    
    /**
     * Handle DocuSign webhook callbacks
     */
    public function docusignWebhook()
    {
        try {
            // Verify webhook authenticity
            if (!$this->verifyDocuSignWebhook()) {
                return Response::json(['error' => 'Unauthorized webhook'], 401);
            }
            
            $webhookData = json_decode(file_get_contents('php://input'), true);
            $envelopeId = $webhookData['data']['envelopeId'];
            $status = $webhookData['event'];
            
            // Find signature record by envelope ID
            $signature = DigitalSignature::where('docusign_envelope_id', $envelopeId)->first();
            if (!$signature) {
                $this->logger->warning("DocuSign webhook for unknown envelope: {$envelopeId}");
                return Response::json(['message' => 'Envelope not found'], 404);
            }
            
            // Process webhook event
            switch ($status) {
                case 'envelope-completed':
                    $this->processDocuSignCompletion($signature, $webhookData);
                    break;
                case 'envelope-declined':
                    $this->processDocuSignDeclined($signature, $webhookData);
                    break;
                case 'envelope-voided':
                    $this->processDocuSignVoided($signature, $webhookData);
                    break;
                default:
                    $this->updateDocuSignStatus($signature, $status, $webhookData);
            }
            
            return Response::json(['message' => 'Webhook processed successfully']);
            
        } catch (Exception $e) {
            $this->logger->error('DocuSign webhook error: ' . $e->getMessage());
            return Response::json(['error' => 'Webhook processing failed'], 500);
        }
    }
    
    /**
     * Generate signature pad interface for web capture
     */
    public function signaturePad($documentId, $documentType)
    {
        try {
            // Verify user can sign this document
            if (!$this->canSignDocument($documentId, $documentType)) {
                return redirect('/access-denied');
            }
            
            // Get document information
            $document = $this->getDocumentInfo($documentId, $documentType);
            if (!$document) {
                return redirect('/document-not-found');
            }
            
            // Check if already signed
            $existingSignature = DigitalSignature::where('document_id', $documentId)
                ->where('document_type', $documentType)
                ->where('verification_status', 'verified')
                ->first();
            
            if ($existingSignature) {
                return redirect('/document-already-signed');
            }
            
            return $this->render('documents/signature-pad', [
                'pageTitle' => 'Digital Signature - ' . $document['title'],
                'pageCSS' => ['/css/signature-pad.css'],
                'pageJS' => ['/js/signature-capture.js'],
                'document' => $document,
                'documentId' => $documentId,
                'documentType' => $documentType,
                'signerInfo' => $this->getSignerInfo(),
                'legalNotice' => $this->getLegalSigningNotice(),
                'signatureRequirements' => $this->getSignatureRequirements()
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Signature pad display error: ' . $e->getMessage());
            return redirect('/signature-error');
        }
    }
    
    // Helper methods for signature processing
    
    /**
     * Create digital signature record with encryption
     */
    protected function createDigitalSignature($data)
    {
        $signature = new DigitalSignature();
        
        // Encrypt sensitive signature data
        if (isset($data['signature_data'])) {
            $data['signature_data_encrypted'] = $this->encryption->encrypt($data['signature_data']);
            unset($data['signature_data']);
        }
        
        // Add legal compliance metadata
        $data['legal_binding_confirmed'] = true;
        $data['electronic_signature_act_compliance'] = true;
        $data['non_repudiation_hash'] = $this->generateNonRepudiationHash($data);
        
        foreach ($data as $key => $value) {
            if (property_exists($signature, $key)) {
                $signature->$key = $value;
            }
        }
        
        return $signature->save();
    }
    
    /**
     * Validate signature data format and quality
     */
    protected function validateSignatureData($signatureData)
    {
        // Check if signature data is valid JSON
        $decodedData = json_decode($signatureData, true);
        if (!$decodedData) {
            return ['valid' => false, 'reason' => 'Invalid signature data format'];
        }
        
        // Check for minimum stroke requirements
        if (!isset($decodedData['strokes']) || count($decodedData['strokes']) < 3) {
            return ['valid' => false, 'reason' => 'Signature too simple - minimum 3 strokes required'];
        }
        
        // Calculate signature quality score
        $qualityScore = $this->calculateSignatureQuality($decodedData);
        if ($qualityScore < 0.3) {
            return ['valid' => false, 'reason' => 'Signature quality too low'];
        }
        
        // Check signature dimensions
        $bounds = $this->calculateSignatureBounds($decodedData);
        if ($bounds['width'] < 50 || $bounds['height'] < 20) {
            return ['valid' => false, 'reason' => 'Signature too small'];
        }
        
        return [
            'valid' => true,
            'quality_score' => $qualityScore,
            'bounds' => $bounds
        ];
    }
    
    /**
     * Generate verification hash for legal compliance
     */
    protected function generateVerificationHash($signatureRecord)
    {
        $hashData = [
            'signature_id' => $signatureRecord->id,
            'document_id' => $signatureRecord->document_id,
            'document_type' => $signatureRecord->document_type,
            'signer_name' => $signatureRecord->signer_name,
            'timestamp' => $signatureRecord->timestamp,
            'ip_address' => $signatureRecord->ip_address
        ];
        
        return hash('sha256', json_encode($hashData) . config('app.signature_salt'));
    }
    
    /**
     * Check if user can sign the specified document
     */
    protected function canSignDocument($documentId, $documentType)
    {
        // Implementation depends on document type and user permissions
        switch ($documentType) {
            case 'consent_form':
                return $this->canSignConsentForm($documentId);
            case 'medical_form':
                return $this->canSignMedicalForm($documentId);
            default:
                return false;
        }
    }
}