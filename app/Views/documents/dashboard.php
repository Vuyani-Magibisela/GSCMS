<?php
$layout = 'layouts/admin';
ob_start();
?>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-2">Document Management Dashboard</h2>
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
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                <i class="fas fa-upload"></i> Upload Document
            </button>
            <button type="button" class="btn btn-primary" onclick="window.location.href='/GSCMS/admin/documents/verification-queue'">
                <i class="fas fa-check-circle"></i> Verification Queue
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <?php if ($userRole === 'admin'): ?>
            <!-- Admin Statistics -->
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Total Documents</h5>
                                <h3 class="mb-0"><?= number_format($stats['total_documents']) ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-file-alt fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Pending Verification</h5>
                                <h3 class="mb-0"><?= number_format($stats['pending_verification']) ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-clock fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Compliance Issues</h5>
                                <h3 class="mb-0"><?= number_format($stats['compliance_issues']) ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Storage Usage</h5>
                                <h3 class="mb-0"><?= $stats['storage_usage']['formatted'] ?></h3>
                                <small><?= number_format($stats['storage_usage']['percentage'], 1) ?>% of limit</small>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-database fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($userRole === 'school_coordinator'): ?>
            <!-- School Coordinator Statistics -->
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">School Documents</h5>
                        <h3 class="mb-0"><?= number_format($stats['school_documents']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Pending Verification</h5>
                        <h3 class="mb-0"><?= number_format($stats['pending_school_verification']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Completion Rate</h5>
                        <h3 class="mb-0"><?= number_format($stats['participant_completion'], 1) ?>%</h3>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Document Type Breakdown -->
    <?php if ($userRole === 'admin'): ?>
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Document Types Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>Consent Forms</span>
                                    <span class="badge bg-primary"><?= $stats['consent_forms']['total'] ?? 0 ?></span>
                                </div>
                                <div class="progress mt-1" style="height: 6px;">
                                    <div class="progress-bar bg-primary" style="width: <?= ($stats['consent_forms']['total'] ?? 0) / max(1, $stats['total_documents']) * 100 ?>%"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>Medical Forms</span>
                                    <span class="badge bg-success"><?= $stats['medical_forms']['total'] ?? 0 ?></span>
                                </div>
                                <div class="progress mt-1" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: <?= ($stats['medical_forms']['total'] ?? 0) / max(1, $stats['total_documents']) * 100 ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>ID Documents</span>
                                    <span class="badge bg-info"><?= $stats['id_documents']['total'] ?? 0 ?></span>
                                </div>
                                <div class="progress mt-1" style="height: 6px;">
                                    <div class="progress-bar bg-info" style="width: <?= ($stats['id_documents']['total'] ?? 0) / max(1, $stats['total_documents']) * 100 ?>%"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>Digital Signatures</span>
                                    <span class="badge bg-warning"><?= $stats['digital_signatures']['total'] ?? 0 ?></span>
                                </div>
                                <div class="progress mt-1" style="height: 6px;">
                                    <div class="progress-bar bg-warning" style="width: <?= ($stats['digital_signatures']['total'] ?? 0) / max(1, $stats['total_documents']) * 100 ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Verification Status</h5>
                </div>
                <div class="card-body">
                    <canvas id="verificationChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Pending Actions -->
    <?php if (!empty($pendingActions)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Pending Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php if ($userRole === 'admin'): ?>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 text-warning"><?= count($pendingActions['documents_to_verify'] ?? []) ?></div>
                                    <div class="small text-muted">Documents to Verify</div>
                                    <a href="/GSCMS/admin/documents/verification-queue" class="btn btn-sm btn-outline-warning mt-2">View Queue</a>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 text-danger"><?= count($pendingActions['expired_documents'] ?? []) ?></div>
                                    <div class="small text-muted">Expired Documents</div>
                                    <a href="/GSCMS/admin/documents?filter=expired" class="btn btn-sm btn-outline-danger mt-2">View List</a>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 text-info"><?= count($pendingActions['security_alerts'] ?? []) ?></div>
                                    <div class="small text-muted">Security Alerts</div>
                                    <a href="/GSCMS/admin/documents/security-audit" class="btn btn-sm btn-outline-info mt-2">View Audit</a>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 text-secondary"><?= count($pendingActions['compliance_issues'] ?? []) ?></div>
                                    <div class="small text-muted">Compliance Issues</div>
                                    <a href="/GSCMS/admin/documents/popia-compliance" class="btn btn-sm btn-outline-secondary mt-2">View Report</a>
                                </div>
                            </div>
                        <?php elseif ($userRole === 'school_coordinator'): ?>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <div class="h4 text-warning"><?= count($pendingActions['incomplete_participants'] ?? []) ?></div>
                                    <div class="small text-muted">Incomplete Participants</div>
                                    <a href="/GSCMS/school/participants?filter=incomplete" class="btn btn-sm btn-outline-warning mt-2">Complete Forms</a>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <div class="h4 text-danger"><?= count($pendingActions['expiring_documents'] ?? []) ?></div>
                                    <div class="small text-muted">Expiring Documents</div>
                                    <a href="/GSCMS/school/documents?filter=expiring" class="btn btn-sm btn-outline-danger mt-2">Renew Documents</a>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <div class="h4 text-info"><?= count($pendingActions['missing_signatures'] ?? []) ?></div>
                                    <div class="small text-muted">Missing Signatures</div>
                                    <a href="/GSCMS/school/signatures" class="btn btn-sm btn-outline-info mt-2">Get Signatures</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Activity -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($recentActivity)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Document</th>
                                        <th>Participant</th>
                                        <th>School</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentActivity as $activity): ?>
                                        <tr>
                                            <td><?= date('M j, Y H:i', strtotime($activity['updated_at'])) ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-file-alt text-muted me-2"></i>
                                                    <div>
                                                        <div class="fw-bold"><?= ucwords(str_replace('_', ' ', $activity['form_type'])) ?></div>
                                                        <small class="text-muted"><?= ucfirst($activity['type']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($activity['participant_name']) ?></td>
                                            <td><?= htmlspecialchars($activity['school_name']) ?></td>
                                            <td>
                                                <?php
                                                $statusColors = [
                                                    'pending' => 'warning',
                                                    'approved' => 'success',
                                                    'rejected' => 'danger',
                                                    'expired' => 'secondary'
                                                ];
                                                $badgeColor = $statusColors[$activity['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $badgeColor ?>"><?= ucfirst($activity['status']) ?></span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary btn-sm" 
                                                            onclick="viewDocument(<?= $activity['id'] ?>, '<?= $activity['type'] ?>')">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if ($userRole === 'admin' && $activity['status'] === 'pending'): ?>
                                                        <button type="button" class="btn btn-outline-success btn-sm" 
                                                                onclick="approveDocument(<?= $activity['id'] ?>, '<?= $activity['type'] ?>')">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No recent activity found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Document Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadModalLabel">Upload Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="documentUploadForm" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="document_type" class="form-label">Document Type</label>
                                <select class="form-select" id="document_type" name="document_type" required>
                                    <option value="">Select document type...</option>
                                    <option value="consent_form">Consent Form</option>
                                    <option value="medical_form">Medical Form</option>
                                    <option value="id_document">ID Document</option>
                                    <option value="birth_certificate">Birth Certificate</option>
                                    <option value="passport">Passport</option>
                                    <option value="medical_certificate">Medical Certificate</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="participant_id" class="form-label">Participant</label>
                                <select class="form-select" id="participant_id" name="participant_id" required>
                                    <option value="">Select participant...</option>
                                    <!-- Populated via JavaScript -->
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="document_file" class="form-label">Document File</label>
                        <input type="file" class="form-control" id="document_file" name="document_file" 
                               accept=".pdf,.jpg,.jpeg,.png" required>
                        <div class="form-text">Accepted formats: PDF, JPG, PNG. Maximum size: 5MB</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="document_notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="document_notes" name="document_notes" rows="3" 
                                  placeholder="Additional notes about this document..."></textarea>
                    </div>
                    
                    <div class="progress mb-3" id="uploadProgress" style="display: none;">
                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="uploadDocument()">
                    <i class="fas fa-upload"></i> Upload Document
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Chart for verification status (if admin)
<?php if ($userRole === 'admin'): ?>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('verificationChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Verified', 'Pending', 'Failed', 'Expired'],
            datasets: [{
                data: [
                    <?= $stats['consent_forms']['verified'] ?? 0 ?>,
                    <?= $stats['consent_forms']['pending'] ?? 0 ?>,
                    <?= $stats['consent_forms']['failed'] ?? 0 ?>,
                    <?= $stats['consent_forms']['expired'] ?? 0 ?>
                ],
                backgroundColor: ['#28a745', '#ffc107', '#dc3545', '#6c757d']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});
<?php endif; ?>

// Document management functions
function viewDocument(documentId, documentType) {
    window.location.href = `/GSCMS/admin/documents/${documentType}/${documentId}`;
}

function approveDocument(documentId, documentType) {
    if (confirm('Are you sure you want to approve this document?')) {
        fetch(`/GSCMS/admin/documents/${documentType}/${documentId}/approve`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error approving document: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to approve document');
        });
    }
}

function uploadDocument() {
    const form = document.getElementById('documentUploadForm');
    const formData = new FormData(form);
    const progressBar = document.querySelector('#uploadProgress .progress-bar');
    const uploadProgress = document.getElementById('uploadProgress');
    
    uploadProgress.style.display = 'block';
    
    fetch('/GSCMS/admin/documents/upload', {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#uploadModal').modal('hide');
            form.reset();
            location.reload();
        } else {
            alert('Upload failed: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Upload failed');
    })
    .finally(() => {
        uploadProgress.style.display = 'none';
        progressBar.style.width = '0%';
    });
}

// Load participants for upload form
document.addEventListener('DOMContentLoaded', function() {
    fetch('/GSCMS/api/participants')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('participant_id');
            data.forEach(participant => {
                const option = document.createElement('option');
                option.value = participant.id;
                option.textContent = `${participant.first_name} ${participant.last_name}`;
                select.appendChild(option);
            });
        })
        .catch(error => console.error('Error loading participants:', error));
});
</script>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>