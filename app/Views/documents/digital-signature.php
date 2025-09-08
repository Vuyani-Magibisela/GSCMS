<?php
$layout = 'layouts/admin';
ob_start();
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-2">Digital Signature Capture</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/GSCMS/admin/dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/GSCMS/admin/documents">Document Management</a></li>
                    <li class="breadcrumb-item active">Digital Signature</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary" onclick="goBack()">
                <i class="fas fa-arrow-left"></i> Back
            </button>
        </div>
    </div>

    <!-- Document Information -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Document Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Document Type:</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($document['document_type'] ?? 'N/A') ?></dd>
                                
                                <dt class="col-sm-4">Participant:</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($document['participant_name'] ?? 'N/A') ?></dd>
                                
                                <dt class="col-sm-4">School:</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($document['school_name'] ?? 'N/A') ?></dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Date:</dt>
                                <dd class="col-sm-8"><?= date('Y-m-d') ?></dd>
                                
                                <dt class="col-sm-4">Required By:</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($document['signer_role'] ?? 'Parent/Guardian') ?></dd>
                                
                                <dt class="col-sm-4">Status:</dt>
                                <dd class="col-sm-8">
                                    <span class="badge bg-warning">Awaiting Signature</span>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Signature Capture Section -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Digital Signature</h5>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" onclick="clearSignature()">
                                <i class="fas fa-eraser"></i> Clear
                            </button>
                            <button type="button" class="btn btn-outline-primary" onclick="undoSignature()" disabled id="undoBtn">
                                <i class="fas fa-undo"></i> Undo
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Signature Canvas -->
                    <div class="signature-container mb-3" style="border: 2px solid #dee2e6; border-radius: 0.375rem; background: #ffffff;">
                        <canvas id="signatureCanvas" class="signature-canvas" width="800" height="300" 
                                style="display: block; width: 100%; max-width: 800px; height: 300px; touch-action: none;"></canvas>
                    </div>
                    
                    <!-- Signature Instructions -->
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Instructions:</strong> 
                        Please sign in the area above using your mouse, finger (on touch devices), or stylus. 
                        Your signature will be captured digitally and securely stored with legal compliance.
                    </div>
                    
                    <!-- Signature Tools -->
                    <div class="row">
                        <div class="col-md-6">
                            <label for="penColor" class="form-label">Pen Color:</label>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-dark btn-sm" onclick="setPenColor('#000000')" data-color="#000000">
                                    <i class="fas fa-circle" style="color: #000000;"></i>
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="setPenColor('#0066cc')" data-color="#0066cc">
                                    <i class="fas fa-circle" style="color: #0066cc;"></i>
                                </button>
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="setPenColor('#17a2b8')" data-color="#17a2b8">
                                    <i class="fas fa-circle" style="color: #17a2b8;"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="penWidth" class="form-label">Pen Width:</label>
                            <input type="range" class="form-range" id="penWidth" min="0.5" max="5" step="0.5" value="2" onchange="setPenWidth(this.value)">
                            <div class="small text-muted">Width: <span id="penWidthValue">2</span>px</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Signer Information -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Signer Information</h6>
                </div>
                <div class="card-body">
                    <form id="signerForm">
                        <div class="mb-3">
                            <label for="signerName" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="signerName" name="signer_name" required
                                   placeholder="Enter your full legal name">
                        </div>
                        
                        <div class="mb-3">
                            <label for="signerEmail" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="signerEmail" name="signer_email"
                                   placeholder="your.email@example.com">
                        </div>
                        
                        <div class="mb-3">
                            <label for="signerPhone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="signerPhone" name="signer_phone"
                                   placeholder="+27 123 456 789">
                        </div>
                        
                        <div class="mb-3">
                            <label for="signerRole" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select" id="signerRole" name="signer_role" required>
                                <option value="">Select your role...</option>
                                <option value="parent">Parent</option>
                                <option value="guardian">Guardian</option>
                                <option value="participant">Participant</option>
                                <option value="witness">Witness</option>
                                <option value="legal_representative">Legal Representative</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="intentStatement" class="form-label">Intent Statement</label>
                            <textarea class="form-control" id="intentStatement" name="intent_statement" rows="3"
                                      placeholder="I intend to sign this document and agree to its terms..."></textarea>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Legal Compliance -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Legal Compliance</h6>
                </div>
                <div class="card-body">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="electronicSignatureConsent" required>
                        <label class="form-check-label" for="electronicSignatureConsent">
                            <small>I consent to sign this document electronically and understand that my electronic signature has the same legal effect as a handwritten signature.</small>
                        </label>
                    </div>
                    
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="popiaConsent" required>
                        <label class="form-check-label" for="popiaConsent">
                            <small>I consent to the processing of my personal information in accordance with POPIA and understand my rights regarding data protection.</small>
                        </label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="identityConfirmation" required>
                        <label class="form-check-label" for="identityConfirmation">
                            <small>I confirm that I am the person identified above and have the authority to sign this document.</small>
                        </label>
                    </div>
                    
                    <div class="alert alert-sm alert-info">
                        <i class="fas fa-shield-alt"></i>
                        <small>Your signature will be encrypted and stored securely with full audit trails for legal compliance.</small>
                    </div>
                </div>
            </div>
            
            <!-- Signature Actions -->
            <div class="card">
                <div class="card-body">
                    <button type="button" class="btn btn-success btn-lg w-100 mb-2" onclick="saveSignature()" id="saveSignatureBtn" disabled>
                        <i class="fas fa-pen-nib"></i> Save Digital Signature
                    </button>
                    
                    <button type="button" class="btn btn-outline-secondary w-100" onclick="previewSignature()">
                        <i class="fas fa-eye"></i> Preview Signature
                    </button>
                    
                    <div class="mt-3">
                        <div class="small text-muted">
                            <i class="fas fa-clock"></i> Session expires in: <span id="sessionTimer">15:00</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Signature Preview Modal -->
<div class="modal fade" id="signaturePreviewModal" tabindex="-1" aria-labelledby="signaturePreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="signaturePreviewModalLabel">Signature Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <canvas id="previewCanvas" width="400" height="150" style="border: 1px solid #dee2e6; border-radius: 0.375rem;"></canvas>
                <div class="mt-3">
                    <small class="text-muted">This is how your signature will appear on the document</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="acceptSignature()">
                    <i class="fas fa-check"></i> Accept Signature
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="successModalLabel">
                    <i class="fas fa-check-circle"></i> Signature Saved Successfully
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="fas fa-certificate fa-3x text-success mb-3"></i>
                    <h5>Digital Signature Complete</h5>
                    <p class="mb-3">Your digital signature has been captured and saved successfully. The document is now legally signed and will be processed accordingly.</p>
                    
                    <div class="alert alert-info">
                        <strong>Signature ID:</strong> <span id="signatureId"></span><br>
                        <strong>Timestamp:</strong> <span id="signatureTimestamp"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="window.location.href='/GSCMS/admin/documents'">
                    <i class="fas fa-arrow-right"></i> Continue to Documents
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Include Signature Pad Library -->
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>

<script>
let signaturePad;
let sessionTimer;
let sessionTimeRemaining = 900; // 15 minutes in seconds

// Initialize signature pad
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('signatureCanvas');
    signaturePad = new SignaturePad(canvas, {
        backgroundColor: 'rgb(255, 255, 255)',
        penColor: '#000000',
        minWidth: 0.5,
        maxWidth: 2.5,
        throttle: 16,
        minDistance: 5
    });
    
    // Enable save button when signature is drawn
    signaturePad.addEventListener('beginStroke', function() {
        document.getElementById('saveSignatureBtn').disabled = false;
        document.getElementById('undoBtn').disabled = false;
    });
    
    // Start session timer
    startSessionTimer();
    
    // Capture device info for security
    captureDeviceInfo();
    
    // Auto-resize canvas on window resize
    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();
});

// Resize canvas to maintain aspect ratio
function resizeCanvas() {
    const canvas = document.getElementById('signatureCanvas');
    const container = canvas.parentElement;
    const rect = container.getBoundingClientRect();
    
    // Set canvas size to container width while maintaining aspect ratio
    const ratio = Math.min(rect.width / 800, rect.height / 300);
    canvas.width = 800 * ratio;
    canvas.height = 300 * ratio;
    
    // Reinitialize signature pad
    if (signaturePad) {
        signaturePad.clear();
    }
}

// Clear signature
function clearSignature() {
    signaturePad.clear();
    document.getElementById('saveSignatureBtn').disabled = true;
    document.getElementById('undoBtn').disabled = true;
}

// Undo last stroke
function undoSignature() {
    const data = signaturePad.toData();
    if (data.length > 0) {
        data.pop();
        signaturePad.fromData(data);
        
        if (data.length === 0) {
            document.getElementById('saveSignatureBtn').disabled = true;
            document.getElementById('undoBtn').disabled = true;
        }
    }
}

// Set pen color
function setPenColor(color) {
    signaturePad.penColor = color;
    
    // Update active button
    document.querySelectorAll('[data-color]').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[data-color="${color}"]`).classList.add('active');
}

// Set pen width
function setPenWidth(width) {
    signaturePad.minWidth = parseFloat(width) * 0.5;
    signaturePad.maxWidth = parseFloat(width) * 1.5;
    document.getElementById('penWidthValue').textContent = width;
}

// Preview signature
function previewSignature() {
    if (signaturePad.isEmpty()) {
        alert('Please provide a signature first');
        return;
    }
    
    const modal = new bootstrap.Modal(document.getElementById('signaturePreviewModal'));
    const previewCanvas = document.getElementById('previewCanvas');
    const previewCtx = previewCanvas.getContext('2d');
    
    // Clear preview canvas
    previewCtx.clearRect(0, 0, previewCanvas.width, previewCanvas.height);
    
    // Draw signature on preview canvas
    const signatureDataURL = signaturePad.toDataURL();
    const img = new Image();
    img.onload = function() {
        previewCtx.drawImage(img, 0, 0, previewCanvas.width, previewCanvas.height);
    };
    img.src = signatureDataURL;
    
    modal.show();
}

// Accept signature from preview
function acceptSignature() {
    bootstrap.Modal.getInstance(document.getElementById('signaturePreviewModal')).hide();
    saveSignature();
}

// Save signature
function saveSignature() {
    if (signaturePad.isEmpty()) {
        alert('Please provide a signature first');
        return;
    }
    
    // Validate required fields
    if (!validateSignerInfo()) {
        return;
    }
    
    // Validate consent checkboxes
    if (!validateConsent()) {
        return;
    }
    
    // Disable save button to prevent double submission
    const saveBtn = document.getElementById('saveSignatureBtn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    // Prepare signature data
    const signatureData = {
        document_id: <?= $document['id'] ?? 'null' ?>,
        document_type: '<?= $document['document_type'] ?? '' ?>',
        signature_data: signaturePad.toDataURL(),
        signature_method: 'web_capture',
        signer_name: document.getElementById('signerName').value,
        signer_email: document.getElementById('signerEmail').value,
        signer_phone: document.getElementById('signerPhone').value,
        signer_role: document.getElementById('signerRole').value,
        intent_statement: document.getElementById('intentStatement').value,
        signature_features: extractSignatureFeatures(),
        signature_bounds: getSignatureBounds(),
        device_info: getDeviceInfo(),
        timestamp: new Date().toISOString(),
        session_id: generateSessionId()
    };
    
    // Send to server
    fetch('/GSCMS/admin/documents/save-digital-signature', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(signatureData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessModal(data.signature);
        } else {
            alert('Failed to save signature: ' + data.message);
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-pen-nib"></i> Save Digital Signature';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to save signature');
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-pen-nib"></i> Save Digital Signature';
    });
}

// Validate signer information
function validateSignerInfo() {
    const signerName = document.getElementById('signerName').value.trim();
    const signerRole = document.getElementById('signerRole').value;
    
    if (!signerName) {
        alert('Please enter your full name');
        document.getElementById('signerName').focus();
        return false;
    }
    
    if (!signerRole) {
        alert('Please select your role');
        document.getElementById('signerRole').focus();
        return false;
    }
    
    return true;
}

// Validate consent checkboxes
function validateConsent() {
    const requiredConsents = [
        'electronicSignatureConsent',
        'popiaConsent',
        'identityConfirmation'
    ];
    
    for (const consentId of requiredConsents) {
        if (!document.getElementById(consentId).checked) {
            alert('Please accept all required consent agreements');
            document.getElementById(consentId).focus();
            return false;
        }
    }
    
    return true;
}

// Extract signature features for verification
function extractSignatureFeatures() {
    const data = signaturePad.toData();
    const features = {
        stroke_count: data.length,
        total_points: data.reduce((total, stroke) => total + stroke.points.length, 0),
        avg_pressure: 0,
        drawing_time: 0,
        bounds: null
    };
    
    if (data.length > 0) {
        features.bounds = getSignatureBounds();
        // Additional feature extraction could be implemented here
    }
    
    return features;
}

// Get signature bounds
function getSignatureBounds() {
    const data = signaturePad.toData();
    if (data.length === 0) return null;
    
    let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
    
    data.forEach(stroke => {
        stroke.points.forEach(point => {
            minX = Math.min(minX, point.x);
            minY = Math.min(minY, point.y);
            maxX = Math.max(maxX, point.x);
            maxY = Math.max(maxY, point.y);
        });
    });
    
    return {
        x: minX,
        y: minY,
        width: maxX - minX,
        height: maxY - minY
    };
}

// Get device information for security
let deviceInfo = {};

function captureDeviceInfo() {
    deviceInfo = {
        user_agent: navigator.userAgent,
        screen_resolution: `${screen.width}x${screen.height}`,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        language: navigator.language,
        platform: navigator.platform,
        touch_support: 'ontouchstart' in window
    };
}

function getDeviceInfo() {
    return deviceInfo;
}

// Generate session ID
function generateSessionId() {
    return 'sign_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

// Session timer functions
function startSessionTimer() {
    updateSessionTimer();
    sessionTimer = setInterval(function() {
        sessionTimeRemaining--;
        updateSessionTimer();
        
        if (sessionTimeRemaining <= 0) {
            clearInterval(sessionTimer);
            alert('Your signing session has expired. Please refresh the page to start a new session.');
            location.reload();
        }
    }, 1000);
}

function updateSessionTimer() {
    const minutes = Math.floor(sessionTimeRemaining / 60);
    const seconds = sessionTimeRemaining % 60;
    document.getElementById('sessionTimer').textContent = 
        `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
}

// Show success modal
function showSuccessModal(signature) {
    document.getElementById('signatureId').textContent = signature.id || 'N/A';
    document.getElementById('signatureTimestamp').textContent = new Date().toLocaleString();
    
    const modal = new bootstrap.Modal(document.getElementById('successModal'));
    modal.show();
    
    // Clear session timer
    clearInterval(sessionTimer);
}

// Go back function
function goBack() {
    if (confirm('Are you sure you want to leave? Any unsaved signature will be lost.')) {
        window.history.back();
    }
}

// Prevent page refresh if signature exists
window.addEventListener('beforeunload', function(e) {
    if (signaturePad && !signaturePad.isEmpty()) {
        e.preventDefault();
        e.returnValue = '';
    }
});
</script>

<style>
.signature-canvas {
    cursor: crosshair;
    border-radius: 0.375rem;
}

.signature-container {
    position: relative;
    touch-action: none;
}

#sessionTimer {
    font-weight: bold;
    color: #dc3545;
}

.btn[data-color].active {
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    border-color: #0d6efd;
}

@media (max-width: 768px) {
    .signature-canvas {
        height: 200px !important;
    }
    
    .btn-lg {
        font-size: 1rem;
        padding: 0.75rem 1rem;
    }
}
</style>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>