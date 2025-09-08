<?php
$layout = 'layouts/admin';
ob_start();
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-2">Document Verification Queue</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <?php foreach ($breadcrumbs as $crumb): ?>
                        <li class="breadcrumb-item">
                            <?php if (!empty($crumb['url'])): ?>
                                <a href="<?= htmlspecialchars($crumb['url']) ?>"><?= htmlspecialchars($crumb['title']) ?></a>
                            <?php else: ?>
                                <?= htmlspecialchars($crumb['title']) ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success" onclick="bulkApprove()">
                <i class="fas fa-check-double"></i> Bulk Approve
            </button>
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#filtersModal">
                <i class="fas fa-filter"></i> Filters
            </button>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="filtersForm" method="GET" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="verified" <?= $filters['status'] === 'verified' ? 'selected' : '' ?>>Verified</option>
                        <option value="failed" <?= $filters['status'] === 'failed' ? 'selected' : '' ?>>Failed</option>
                        <option value="flagged" <?= $filters['status'] === 'flagged' ? 'selected' : '' ?>>Flagged</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="type" class="form-label">Document Type</label>
                    <select name="type" id="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="consent_form" <?= $filters['type'] === 'consent_form' ? 'selected' : '' ?>>Consent Forms</option>
                        <option value="medical_form" <?= $filters['type'] === 'medical_form' ? 'selected' : '' ?>>Medical Forms</option>
                        <option value="id_document" <?= $filters['type'] === 'id_document' ? 'selected' : '' ?>>ID Documents</option>
                        <option value="birth_certificate" <?= $filters['type'] === 'birth_certificate' ? 'selected' : '' ?>>Birth Certificates</option>
                        <option value="passport" <?= $filters['type'] === 'passport' ? 'selected' : '' ?>>Passports</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="school_id" class="form-label">School</label>
                    <select name="school_id" id="school_id" class="form-select">
                        <option value="">All Schools</option>
                        <?php foreach ($schools as $school): ?>
                            <option value="<?= $school['id'] ?>" <?= $filters['school_id'] == $school['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($school['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="/GSCMS/admin/documents/verification-queue" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
                <div class="col-md-2 text-end">
                    <small class="text-muted">
                        Showing <?= count($documents) ?> of <?= $pagination['total_count'] ?> documents
                    </small>
                </div>
            </form>
        </div>
    </div>

    <!-- Documents Table -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Documents Pending Verification</h5>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                    <label class="form-check-label" for="selectAll">
                        Select All
                    </label>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($documents)): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="40">
                                    <input type="checkbox" id="selectAllHeader" onchange="toggleSelectAll()">
                                </th>
                                <th>Document</th>
                                <th>Participant</th>
                                <th>School</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th width="200">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="documentsTableBody">
                            <?php foreach ($documents as $doc): ?>
                                <tr id="document-row-<?= $doc['id'] ?>">
                                    <td>
                                        <input type="checkbox" class="document-checkbox" value="<?= $doc['id'] ?>" 
                                               data-type="<?= $doc['document_type'] ?>">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php
                                            $docIcons = [
                                                'consent_form' => 'fas fa-file-contract text-primary',
                                                'medical_form' => 'fas fa-file-medical text-success',
                                                'id_document' => 'fas fa-id-card text-info',
                                                'birth_certificate' => 'fas fa-certificate text-warning',
                                                'passport' => 'fas fa-passport text-danger'
                                            ];
                                            $iconClass = $docIcons[$doc['document_type']] ?? 'fas fa-file-alt text-secondary';
                                            ?>
                                            <i class="<?= $iconClass ?> me-2"></i>
                                            <div>
                                                <div class="fw-bold"><?= ucwords(str_replace('_', ' ', $doc['document_type'])) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($doc['original_filename'] ?? 'N/A') ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($doc['participant_name']) ?></div>
                                            <small class="text-muted">ID: <?= $doc['participant_id'] ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark"><?= htmlspecialchars($doc['school_name']) ?></span>
                                    </td>
                                    <td>
                                        <div>
                                            <div><?= date('M j, Y', strtotime($doc['created_at'])) ?></div>
                                            <small class="text-muted"><?= date('H:i', strtotime($doc['created_at'])) ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'verified' => 'success', 
                                            'failed' => 'danger',
                                            'flagged' => 'info',
                                            'expired' => 'secondary'
                                        ];
                                        $badgeColor = $statusColors[$doc['verification_status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $badgeColor ?>"><?= ucfirst($doc['verification_status']) ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $daysSinceSubmitted = floor((time() - strtotime($doc['created_at'])) / (60 * 60 * 24));
                                        $priorityClass = 'success';
                                        $priorityText = 'Normal';
                                        
                                        if ($daysSinceSubmitted > 7) {
                                            $priorityClass = 'danger';
                                            $priorityText = 'Critical';
                                        } elseif ($daysSinceSubmitted > 3) {
                                            $priorityClass = 'warning';
                                            $priorityText = 'High';
                                        }
                                        ?>
                                        <span class="badge bg-<?= $priorityClass ?>"><?= $priorityText ?></span>
                                        <small class="d-block text-muted"><?= $daysSinceSubmitted ?> days</small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" 
                                                    onclick="previewDocument(<?= $doc['id'] ?>, '<?= $doc['document_type'] ?>')" 
                                                    title="Preview">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($doc['verification_status'] === 'pending'): ?>
                                                <button type="button" class="btn btn-outline-success" 
                                                        onclick="approveDocument(<?= $doc['id'] ?>, '<?= $doc['document_type'] ?>')" 
                                                        title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" 
                                                        onclick="rejectDocument(<?= $doc['id'] ?>, '<?= $doc['document_type'] ?>')" 
                                                        title="Reject">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-warning" 
                                                        onclick="flagDocument(<?= $doc['id'] ?>, '<?= $doc['document_type'] ?>')" 
                                                        title="Flag for Review">
                                                    <i class="fas fa-flag"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="card-footer">
                        <nav aria-label="Document pagination">
                            <ul class="pagination justify-content-center mb-0">
                                <li class="page-item <?= $pagination['current_page'] <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $pagination['current_page'] - 1 ?>&<?= http_build_query(array_filter($filters)) ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                    <?php if ($i == 1 || $i == $pagination['total_pages'] || abs($i - $pagination['current_page']) <= 2): ?>
                                        <li class="page-item <?= $i == $pagination['current_page'] ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query(array_filter($filters)) ?>"><?= $i ?></a>
                                        </li>
                                    <?php elseif (abs($i - $pagination['current_page']) == 3): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <li class="page-item <?= $pagination['current_page'] >= $pagination['total_pages'] ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $pagination['current_page'] + 1 ?>&<?= http_build_query(array_filter($filters)) ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No documents found</h5>
                    <p class="text-muted mb-0">No documents match the current filter criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Document Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">Document Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8">
                        <div id="documentPreview" class="text-center">
                            <div class="spinner-border" role="status" aria-label="Loading">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Document Information</h6>
                            </div>
                            <div class="card-body" id="documentInfo">
                                <!-- Populated via JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <div class="btn-group" id="verificationActions">
                    <button type="button" class="btn btn-success" onclick="approveFromPreview()">
                        <i class="fas fa-check"></i> Approve
                    </button>
                    <button type="button" class="btn btn-danger" onclick="rejectFromPreview()">
                        <i class="fas fa-times"></i> Reject
                    </button>
                    <button type="button" class="btn btn-warning" onclick="flagFromPreview()">
                        <i class="fas fa-flag"></i> Flag
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rejection Reason Modal -->
<div class="modal fade" id="rejectionModal" tabindex="-1" aria-labelledby="rejectionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectionModalLabel">Reject Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="rejectionForm">
                    <div class="mb-3">
                        <label for="rejectionReason" class="form-label">Reason for Rejection</label>
                        <select class="form-select" id="rejectionReason" required>
                            <option value="">Select reason...</option>
                            <option value="document_unclear">Document is unclear/illegible</option>
                            <option value="incomplete_information">Incomplete information</option>
                            <option value="invalid_document">Invalid or expired document</option>
                            <option value="wrong_document_type">Wrong document type</option>
                            <option value="security_concern">Security concern</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="rejectionNotes" class="form-label">Additional Notes</label>
                        <textarea class="form-control" id="rejectionNotes" rows="3" 
                                  placeholder="Provide additional details about the rejection..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="submitRejection()">
                    <i class="fas fa-times"></i> Reject Document
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentDocumentId = null;
let currentDocumentType = null;

// Selection functions
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.document-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateBulkActions();
}

function updateBulkActions() {
    const checkedBoxes = document.querySelectorAll('.document-checkbox:checked');
    const bulkActions = document.querySelector('.btn-group');
    
    if (checkedBoxes.length > 0) {
        // Show bulk actions
    } else {
        // Hide bulk actions
    }
}

// Document actions
function previewDocument(documentId, documentType) {
    currentDocumentId = documentId;
    currentDocumentType = documentType;
    
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
    
    // Load document preview
    fetch(`/GSCMS/admin/documents/${documentType}/${documentId}/preview`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayDocumentPreview(data.document);
                displayDocumentInfo(data.document);
            } else {
                document.getElementById('documentPreview').innerHTML = 
                    '<div class="alert alert-danger">Failed to load document preview</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('documentPreview').innerHTML = 
                '<div class="alert alert-danger">Error loading document preview</div>';
        });
}

function displayDocumentPreview(document) {
    const previewDiv = document.getElementById('documentPreview');
    
    if (document.mime_type && document.mime_type.startsWith('image/')) {
        previewDiv.innerHTML = `<img src="${document.preview_url}" class="img-fluid" alt="Document preview">`;
    } else if (document.mime_type === 'application/pdf') {
        previewDiv.innerHTML = `<iframe src="${document.preview_url}" width="100%" height="600px"></iframe>`;
    } else {
        previewDiv.innerHTML = `<div class="alert alert-info">Preview not available for this file type</div>`;
    }
}

function displayDocumentInfo(document) {
    const infoDiv = document.getElementById('documentInfo');
    infoDiv.innerHTML = `
        <dl class="row">
            <dt class="col-sm-4">Type</dt>
            <dd class="col-sm-8">${document.document_type_label || document.document_type}</dd>
            
            <dt class="col-sm-4">Participant</dt>
            <dd class="col-sm-8">${document.participant_name}</dd>
            
            <dt class="col-sm-4">School</dt>
            <dd class="col-sm-8">${document.school_name}</dd>
            
            <dt class="col-sm-4">Submitted</dt>
            <dd class="col-sm-8">${new Date(document.created_at).toLocaleDateString()}</dd>
            
            <dt class="col-sm-4">File Size</dt>
            <dd class="col-sm-8">${document.formatted_file_size || 'N/A'}</dd>
            
            <dt class="col-sm-4">Status</dt>
            <dd class="col-sm-8">
                <span class="badge bg-${getStatusColor(document.verification_status)}">
                    ${document.verification_status}
                </span>
            </dd>
        </dl>
    `;
}

function approveDocument(documentId, documentType) {
    if (confirm('Are you sure you want to approve this document?')) {
        performDocumentAction('approve', documentId, documentType);
    }
}

function rejectDocument(documentId, documentType) {
    currentDocumentId = documentId;
    currentDocumentType = documentType;
    
    const modal = new bootstrap.Modal(document.getElementById('rejectionModal'));
    modal.show();
}

function flagDocument(documentId, documentType) {
    const reason = prompt('Please provide a reason for flagging this document:');
    if (reason) {
        performDocumentAction('flag', documentId, documentType, { reason: reason });
    }
}

function submitRejection() {
    const reason = document.getElementById('rejectionReason').value;
    const notes = document.getElementById('rejectionNotes').value;
    
    if (!reason) {
        alert('Please select a reason for rejection');
        return;
    }
    
    performDocumentAction('reject', currentDocumentId, currentDocumentType, {
        reason: reason,
        notes: notes
    });
    
    bootstrap.Modal.getInstance(document.getElementById('rejectionModal')).hide();
}

function performDocumentAction(action, documentId, documentType, data = {}) {
    const requestData = {
        action: action,
        document_id: documentId,
        document_type: documentType,
        ...data
    };
    
    fetch('/GSCMS/admin/documents/verification-action', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the row in the table
            updateDocumentRow(documentId, data.document);
            
            // Close any open modals
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) modalInstance.hide();
            });
        } else {
            alert('Action failed: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Action failed');
    });
}

function updateDocumentRow(documentId, document) {
    const row = document.getElementById(`document-row-${documentId}`);
    if (row) {
        // Update status badge
        const statusCell = row.cells[5]; // Status column
        const statusColor = getStatusColor(document.verification_status);
        statusCell.innerHTML = `<span class="badge bg-${statusColor}">${document.verification_status}</span>`;
        
        // Update actions column if needed
        if (document.verification_status !== 'pending') {
            const actionsCell = row.cells[7]; // Actions column
            const buttons = actionsCell.querySelectorAll('button:not(.btn-outline-primary)');
            buttons.forEach(button => button.remove());
        }
    }
}

function bulkApprove() {
    const checkedBoxes = document.querySelectorAll('.document-checkbox:checked');
    if (checkedBoxes.length === 0) {
        alert('Please select documents to approve');
        return;
    }
    
    if (confirm(`Are you sure you want to approve ${checkedBoxes.length} documents?`)) {
        const documents = Array.from(checkedBoxes).map(checkbox => ({
            id: checkbox.value,
            type: checkbox.dataset.type
        }));
        
        fetch('/GSCMS/admin/documents/bulk-approve', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ documents: documents })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Bulk approve failed: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bulk approve failed');
        });
    }
}

// Modal action functions
function approveFromPreview() {
    approveDocument(currentDocumentId, currentDocumentType);
}

function rejectFromPreview() {
    rejectDocument(currentDocumentId, currentDocumentType);
}

function flagFromPreview() {
    flagDocument(currentDocumentId, currentDocumentType);
}

// Utility functions
function getStatusColor(status) {
    const colors = {
        'pending': 'warning',
        'verified': 'success',
        'failed': 'danger',
        'flagged': 'info',
        'expired': 'secondary'
    };
    return colors[status] || 'secondary';
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Update bulk actions on checkbox change
    document.querySelectorAll('.document-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });
});
</script>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>