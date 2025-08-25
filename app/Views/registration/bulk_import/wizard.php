<?php
ob_start();
$layout = 'app';
$title = 'Bulk Import Wizard - Student Data Import';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="max-w-4xl mx-auto mb-8">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">Bulk Import Wizard</h1>
                        <p class="text-gray-600">
                            Upload your student data file for validation and import into the competition system.
                        </p>
                    </div>
                    
                    <a href="/bulk-import" 
                       class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Import Steps -->
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-sm">
                <!-- Progress Steps -->
                <div class="px-8 py-6 border-b border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-semibold text-gray-900">Import Process</h2>
                        <span id="currentStep" class="text-sm text-gray-500">Step 1 of 4</span>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col items-center">
                            <div id="step1-indicator" class="w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-semibold mb-2">1</div>
                            <span class="text-xs text-indigo-600 font-medium">Upload File</span>
                        </div>
                        <div class="flex-1 h-px bg-gray-200 mx-2" id="progress1"></div>
                        <div class="flex flex-col items-center">
                            <div id="step2-indicator" class="w-8 h-8 bg-gray-200 text-gray-500 rounded-full flex items-center justify-center font-semibold mb-2">2</div>
                            <span class="text-xs text-gray-500">Validate Data</span>
                        </div>
                        <div class="flex-1 h-px bg-gray-200 mx-2" id="progress2"></div>
                        <div class="flex flex-col items-center">
                            <div id="step3-indicator" class="w-8 h-8 bg-gray-200 text-gray-500 rounded-full flex items-center justify-center font-semibold mb-2">3</div>
                            <span class="text-xs text-gray-500">Review Results</span>
                        </div>
                        <div class="flex-1 h-px bg-gray-200 mx-2" id="progress3"></div>
                        <div class="flex flex-col items-center">
                            <div id="step4-indicator" class="w-8 h-8 bg-gray-200 text-gray-500 rounded-full flex items-center justify-center font-semibold mb-2">4</div>
                            <span class="text-xs text-gray-500">Import Complete</span>
                        </div>
                    </div>
                </div>

                <!-- Step Content -->
                <div class="p-8">
                    <!-- Step 1: File Upload -->
                    <div id="step1-content" class="step-content">
                        <div class="mb-8">
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">Upload Your File</h3>
                            <p class="text-gray-600">
                                Select a CSV or Excel file containing student information. Make sure to use our template format for best results.
                            </p>
                        </div>

                        <!-- File Upload Area -->
                        <div class="mb-6">
                            <div id="dropzone" class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-indigo-400 transition duration-200">
                                <div class="space-y-4">
                                    <div class="flex justify-center">
                                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-lg text-gray-600">
                                            Drag and drop your file here, or 
                                            <button id="browse-btn" class="text-indigo-600 hover:text-indigo-800 font-semibold underline">browse</button>
                                        </p>
                                        <p class="text-sm text-gray-500 mt-2">
                                            Supported formats: CSV, XLSX, XLS (Max size: <?php echo htmlspecialchars($max_file_size); ?>)
                                        </p>
                                    </div>
                                </div>
                                
                                <input type="file" id="file-input" class="hidden" accept=".csv,.xlsx,.xls" />
                            </div>
                        </div>

                        <!-- File Info Display -->
                        <div id="file-info" class="hidden mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-center">
                                <svg class="w-8 h-8 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <div class="flex-1">
                                    <h4 id="file-name" class="font-semibold text-gray-900"></h4>
                                    <p id="file-details" class="text-sm text-gray-600"></p>
                                </div>
                                <button id="remove-file" class="text-red-600 hover:text-red-800 ml-4">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Import Type Selection -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-3">Import Type</label>
                            <div class="grid grid-cols-1 gap-4">
                                <label class="relative flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="import_type" value="participants" class="sr-only" checked>
                                    <div class="w-4 h-4 border-2 border-indigo-600 rounded-full mr-3 flex items-center justify-center">
                                        <div class="w-2 h-2 bg-indigo-600 rounded-full"></div>
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900">Student Participants</div>
                                        <div class="text-sm text-gray-500">Import student information for competition participation</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Template Download Links -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-6">
                            <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                                <svg class="w-5 h-5 text-gray-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                                </svg>
                                Download Import Templates
                            </h4>
                            <div class="flex flex-wrap gap-3">
                                <a href="/bulk-import/download-template?format=csv" 
                                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    CSV Template
                                </a>
                                <a href="/bulk-import/download-template?format=xlsx" 
                                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-green-700 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    Excel Template
                                </a>
                            </div>
                        </div>

                        <!-- Upload Button -->
                        <div class="flex justify-end">
                            <button id="upload-btn" 
                                    class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                    disabled>
                                <span class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                    Upload and Validate
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Validation Progress -->
                    <div id="step2-content" class="step-content hidden">
                        <div class="text-center">
                            <div class="mb-8">
                                <h3 class="text-2xl font-bold text-gray-900 mb-2">Validating Your Data</h3>
                                <p class="text-gray-600">
                                    Please wait while we validate your file and check for any errors.
                                </p>
                            </div>

                            <!-- Loading Animation -->
                            <div class="mb-8">
                                <div class="w-16 h-16 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin mx-auto"></div>
                            </div>

                            <!-- Progress Info -->
                            <div id="validation-progress" class="max-w-md mx-auto">
                                <div class="bg-gray-200 rounded-full h-2 mb-4">
                                    <div id="progress-bar" class="bg-indigo-600 h-2 rounded-full transition-all duration-500" style="width: 0%"></div>
                                </div>
                                <p id="progress-text" class="text-sm text-gray-600">Starting validation...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Validation Results -->
                    <div id="step3-content" class="step-content hidden">
                        <div class="mb-8">
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">Validation Results</h3>
                            <p class="text-gray-600">
                                Review the validation results and choose how to proceed.
                            </p>
                        </div>

                        <div id="validation-results">
                            <!-- Results will be populated here -->
                        </div>
                    </div>

                    <!-- Step 4: Import Complete -->
                    <div id="step4-content" class="step-content hidden">
                        <div class="text-center">
                            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">Import Complete!</h3>
                            <p class="text-gray-600 mb-8">
                                Your student data has been successfully imported into the system.
                            </p>

                            <div id="import-summary" class="max-w-md mx-auto mb-8">
                                <!-- Import summary will be populated here -->
                            </div>

                            <div class="flex justify-center space-x-4">
                                <a href="/bulk-import" 
                                   class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition duration-200">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2h6l2 2h6a2 2 0 012 2z"/>
                                    </svg>
                                    Back to Dashboard
                                </a>
                                
                                <button id="new-import-btn" 
                                        class="inline-flex items-center px-6 py-3 border border-gray-300 bg-white text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition duration-200">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    Start New Import
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Import Wizard -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    let selectedFile = null;
    let currentImportId = null;
    
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('file-input');
    const browseBtn = document.getElementById('browse-btn');
    const fileInfo = document.getElementById('file-info');
    const uploadBtn = document.getElementById('upload-btn');
    const removeFileBtn = document.getElementById('remove-file');
    
    // File upload handling
    browseBtn.addEventListener('click', () => fileInput.click());
    dropzone.addEventListener('click', () => fileInput.click());
    
    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('border-indigo-400', 'bg-indigo-50');
    });
    
    dropzone.addEventListener('dragleave', () => {
        dropzone.classList.remove('border-indigo-400', 'bg-indigo-50');
    });
    
    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('border-indigo-400', 'bg-indigo-50');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFileSelect(files[0]);
        }
    });
    
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleFileSelect(e.target.files[0]);
        }
    });
    
    removeFileBtn.addEventListener('click', () => {
        selectedFile = null;
        fileInfo.classList.add('hidden');
        uploadBtn.disabled = true;
        fileInput.value = '';
    });
    
    function handleFileSelect(file) {
        const allowedTypes = ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        
        if (!allowedTypes.includes(file.type)) {
            alert('Please select a valid CSV or Excel file.');
            return;
        }
        
        if (file.size > 10 * 1024 * 1024) { // 10MB limit
            alert('File size must be less than 10MB.');
            return;
        }
        
        selectedFile = file;
        
        // Display file info
        document.getElementById('file-name').textContent = file.name;
        document.getElementById('file-details').textContent = `${(file.size / 1024).toFixed(1)} KB • ${file.type.split('/')[1].toUpperCase()}`;
        
        fileInfo.classList.remove('hidden');
        uploadBtn.disabled = false;
    }
    
    // Upload and validation process
    uploadBtn.addEventListener('click', async () => {
        if (!selectedFile) return;
        
        const formData = new FormData();
        formData.append('import_file', selectedFile);
        formData.append('import_type', document.querySelector('input[name="import_type"]:checked').value);
        
        try {
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = `
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Uploading...
                </span>
            `;
            
            const response = await fetch('/bulk-import/upload', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            
            const result = await response.json();
            
            if (result.success) {
                currentImportId = result.import_id;
                showStep(2);
                startValidationPolling();
            } else {
                throw new Error(result.message || 'Upload failed');
            }
            
        } catch (error) {
            alert('Upload failed: ' + error.message);
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = `
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    Upload and Validate
                </span>
            `;
        }
    });
    
    function showStep(stepNumber) {
        // Hide all steps
        document.querySelectorAll('.step-content').forEach(el => el.classList.add('hidden'));
        
        // Show current step
        document.getElementById(`step${stepNumber}-content`).classList.remove('hidden');
        
        // Update step indicators
        for (let i = 1; i <= 4; i++) {
            const indicator = document.getElementById(`step${i}-indicator`);
            const progress = document.getElementById(`progress${i}`);
            
            if (i < stepNumber) {
                // Completed step
                indicator.className = 'w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center font-semibold mb-2';
                indicator.innerHTML = '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>';
                if (progress) progress.className = 'flex-1 h-px bg-green-200 mx-2';
            } else if (i === stepNumber) {
                // Current step
                indicator.className = 'w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-semibold mb-2';
                indicator.textContent = i;
            } else {
                // Future step
                indicator.className = 'w-8 h-8 bg-gray-200 text-gray-500 rounded-full flex items-center justify-center font-semibold mb-2';
                indicator.textContent = i;
            }
        }
        
        // Update step counter
        document.getElementById('currentStep').textContent = `Step ${stepNumber} of 4`;
    }
    
    async function startValidationPolling() {
        let progress = 0;
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');
        
        const pollValidation = async () => {
            try {
                const response = await fetch(`/bulk-import/validation-status?import_id=${currentImportId}`, {
                    credentials: 'same-origin'
                });
                const result = await response.json();
                
                if (result.success) {
                    const status = result.status;
                    progress = Math.min(progress + 10, 90);
                    
                    progressBar.style.width = progress + '%';
                    progressText.textContent = `Validating... (${status})`;
                    
                    if (status === 'validation_complete' || status === 'validation_failed') {
                        progressBar.style.width = '100%';
                        setTimeout(() => showValidationResults(result), 1000);
                        return;
                    }
                    
                    setTimeout(pollValidation, 2000);
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                progressText.textContent = 'Validation failed: ' + error.message;
                progressBar.style.width = '100%';
                progressBar.className = 'bg-red-600 h-2 rounded-full transition-all duration-500';
            }
        };
        
        setTimeout(pollValidation, 1000);
    }
    
    function showValidationResults(validationResult) {
        showStep(3);
        
        const resultsContainer = document.getElementById('validation-results');
        const hasErrors = validationResult.progress.failed_records > 0;
        
        resultsContainer.innerHTML = `
            <div class="space-y-6">
                <div class="${hasErrors ? 'bg-red-50 border-red-200' : 'bg-green-50 border-green-200'} border rounded-lg p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-8 h-8 ${hasErrors ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'} rounded-full flex items-center justify-center mr-3">
                            ${hasErrors ? 
                                '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>' :
                                '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>'
                            }
                        </div>
                        <h3 class="text-lg font-semibold ${hasErrors ? 'text-red-900' : 'text-green-900'}">
                            ${hasErrors ? 'Validation Issues Found' : 'Validation Passed'}
                        </h3>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4 text-sm">
                        <div>
                            <div class="font-medium text-gray-900">Total Records</div>
                            <div class="text-2xl font-bold ${hasErrors ? 'text-red-600' : 'text-green-600'}">${validationResult.progress.total_records || 0}</div>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">Valid Records</div>
                            <div class="text-2xl font-bold text-green-600">${(validationResult.progress.total_records || 0) - (validationResult.progress.failed_records || 0)}</div>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">Errors</div>
                            <div class="text-2xl font-bold text-red-600">${validationResult.progress.failed_records || 0}</div>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4">
                    ${hasErrors ? 
                        `<a href="/bulk-import/${currentImportId}/validation-results" class="inline-flex items-center px-4 py-2 border border-red-300 bg-white text-red-700 font-medium rounded-lg hover:bg-red-50 transition duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                            View Error Details
                        </a>` : ''
                    }
                    
                    ${!hasErrors ? 
                        `<button id="execute-import-btn" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition duration-200">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            Execute Import
                        </button>` : 
                        `<a href="/bulk-import" class="inline-flex items-center px-6 py-3 border border-gray-300 bg-white text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition duration-200">
                            Back to Dashboard
                        </a>`
                    }
                </div>
            </div>
        `;
        
        // Add event listener for execute import button
        const executeBtn = document.getElementById('execute-import-btn');
        if (executeBtn) {
            executeBtn.addEventListener('click', executeImport);
        }
    }
    
    async function executeImport() {
        const executeBtn = document.getElementById('execute-import-btn');
        
        try {
            executeBtn.disabled = true;
            executeBtn.innerHTML = `
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Importing...
                </span>
            `;
            
            const response = await fetch('/bulk-import/execute', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ import_id: currentImportId }),
                credentials: 'same-origin'
            });
            
            const result = await response.json();
            
            if (result.success) {
                showImportComplete(result.result);
            } else {
                throw new Error(result.message);
            }
            
        } catch (error) {
            alert('Import failed: ' + error.message);
            executeBtn.disabled = false;
            executeBtn.innerHTML = `
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Execute Import
                </span>
            `;
        }
    }
    
    function showImportComplete(importResult) {
        showStep(4);
        
        const summaryContainer = document.getElementById('import-summary');
        summaryContainer.innerHTML = `
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div class="text-center">
                        <div class="font-medium text-gray-900">Created</div>
                        <div class="text-2xl font-bold text-green-600">${importResult.created || 0}</div>
                    </div>
                    <div class="text-center">
                        <div class="font-medium text-gray-900">Updated</div>
                        <div class="text-2xl font-bold text-blue-600">${importResult.updated || 0}</div>
                    </div>
                </div>
                ${importResult.skipped > 0 ? 
                    `<div class="mt-4 pt-4 border-t border-green-200">
                        <div class="text-center">
                            <div class="font-medium text-gray-900">Skipped</div>
                            <div class="text-lg text-yellow-600">${importResult.skipped}</div>
                        </div>
                    </div>` : ''
                }
            </div>
        `;
    }
    
    // New import button
    document.getElementById('new-import-btn').addEventListener('click', () => {
        location.reload();
    });
});
</script>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>