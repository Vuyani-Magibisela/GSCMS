<?php
ob_start();
$layout = 'public';
$title = 'Step 1: School Information - School Registration';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <!-- Progress Header -->
        <div class="max-w-4xl mx-auto mb-8">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <!-- Progress Bar -->
                <div class="flex items-center justify-between mb-4">
                    <h1 class="text-2xl font-bold text-gray-900">School Registration</h1>
                    <span class="text-sm text-gray-500">Step 1 of 5</span>
                </div>
                
                <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                    <div class="bg-indigo-600 h-2 rounded-full" style="width: 20%"></div>
                </div>
                
                <!-- Step Indicators -->
                <div class="flex items-center justify-between text-xs">
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-semibold mb-1">1</div>
                        <span class="text-indigo-600 font-medium">School Info</span>
                    </div>
                    <div class="flex-1 h-px bg-gray-200 mx-2"></div>
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 bg-gray-200 text-gray-500 rounded-full flex items-center justify-center font-semibold mb-1">2</div>
                        <span class="text-gray-500">Contact</span>
                    </div>
                    <div class="flex-1 h-px bg-gray-200 mx-2"></div>
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 bg-gray-200 text-gray-500 rounded-full flex items-center justify-center font-semibold mb-1">3</div>
                        <span class="text-gray-500">Address</span>
                    </div>
                    <div class="flex-1 h-px bg-gray-200 mx-2"></div>
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 bg-gray-200 text-gray-500 rounded-full flex items-center justify-center font-semibold mb-1">4</div>
                        <span class="text-gray-500">Details</span>
                    </div>
                    <div class="flex-1 h-px bg-gray-200 mx-2"></div>
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 bg-gray-200 text-gray-500 rounded-full flex items-center justify-center font-semibold mb-1">5</div>
                        <span class="text-gray-500">Competition</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Form -->
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-sm">
                <div class="p-8">
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">School Information</h2>
                        <p class="text-gray-600">
                            Please provide basic information about your school. This information will be used 
                            to verify your school's eligibility for the competition.
                        </p>
                    </div>

                    <!-- Error Messages -->
                    <?php if (isset($validation_errors) && !empty($validation_errors)): ?>
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Please correct the following errors:</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <ul class="list-disc list-inside space-y-1">
                                        <?php foreach ($validation_errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <form id="step1Form" method="POST" action="/register/school/process-step/1" class="space-y-6">
                        <!-- School Name -->
                        <div>
                            <label for="school_name" class="block text-sm font-medium text-gray-700 mb-2">
                                School Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="school_name" 
                                   name="school_name" 
                                   value="<?php echo htmlspecialchars($registration['school_name'] ?? ''); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200"
                                   placeholder="Enter the full official name of your school"
                                   required>
                            <p class="mt-1 text-sm text-gray-500">
                                Enter the exact name as it appears on official documents
                            </p>
                        </div>

                        <!-- EMIS Number -->
                        <div>
                            <label for="emis_number" class="block text-sm font-medium text-gray-700 mb-2">
                                EMIS Number <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="emis_number" 
                                   name="emis_number" 
                                   value="<?php echo htmlspecialchars($registration['emis_number'] ?? ''); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200"
                                   placeholder="e.g., 123456789012"
                                   pattern="[0-9]{8,12}"
                                   maxlength="12"
                                   required>
                            <p class="mt-1 text-sm text-gray-500">
                                Your school's 8-12 digit EMIS (Education Management Information System) number
                            </p>
                        </div>

                        <!-- Registration Number -->
                        <div>
                            <label for="registration_number" class="block text-sm font-medium text-gray-700 mb-2">
                                School Registration Number <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="registration_number" 
                                   name="registration_number" 
                                   value="<?php echo htmlspecialchars($registration['registration_number'] ?? ''); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200"
                                   placeholder="Enter your school registration number"
                                   required>
                            <p class="mt-1 text-sm text-gray-500">
                                Official registration number issued by the Department of Education
                            </p>
                        </div>

                        <!-- School Type -->
                        <div>
                            <label for="school_type" class="block text-sm font-medium text-gray-700 mb-2">
                                School Type <span class="text-red-500">*</span>
                            </label>
                            <select id="school_type" 
                                    name="school_type" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200"
                                    required>
                                <option value="">Select school type</option>
                                <option value="public" <?php echo ($registration['school_type'] ?? '') === 'public' ? 'selected' : ''; ?>>
                                    Public School
                                </option>
                                <option value="private" <?php echo ($registration['school_type'] ?? '') === 'private' ? 'selected' : ''; ?>>
                                    Private School
                                </option>
                                <option value="independent" <?php echo ($registration['school_type'] ?? '') === 'independent' ? 'selected' : ''; ?>>
                                    Independent School
                                </option>
                                <option value="special" <?php echo ($registration['school_type'] ?? '') === 'special' ? 'selected' : ''; ?>>
                                    Special Needs School
                                </option>
                                <option value="technical" <?php echo ($registration['school_type'] ?? '') === 'technical' ? 'selected' : ''; ?>>
                                    Technical/Vocational School
                                </option>
                            </select>
                            <p class="mt-1 text-sm text-gray-500">
                                Select the category that best describes your school
                            </p>
                        </div>

                        <!-- Information Panel -->
                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        <strong>Important:</strong> The information you provide must match your official school records. 
                                        Incorrect information may delay or prevent your registration approval. If you need to make 
                                        changes after submission, please contact our support team.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Navigation Footer -->
                <div class="bg-gray-50 px-8 py-4 rounded-b-lg">
                    <div class="flex items-center justify-between">
                        <a href="/register/school" 
                           class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Back to Start
                        </a>
                        
                        <div class="flex items-center space-x-3">
                            <button type="button" 
                                    id="saveProgressBtn"
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                Save Progress
                            </button>
                            
                            <button type="submit" 
                                    form="step1Form"
                                    class="inline-flex items-center px-6 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-lg hover:bg-indigo-700 transition duration-200">
                                Continue to Step 2
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for form enhancement -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('step1Form');
    const saveProgressBtn = document.getElementById('saveProgressBtn');
    const emisInput = document.getElementById('emis_number');
    
    // Format EMIS number input
    emisInput.addEventListener('input', function(e) {
        // Remove non-digits
        let value = e.target.value.replace(/\D/g, '');
        
        // Limit to 12 digits
        if (value.length > 12) {
            value = value.substr(0, 12);
        }
        
        e.target.value = value;
    });
    
    // Save progress functionality
    saveProgressBtn.addEventListener('click', function() {
        const formData = new FormData(form);
        
        // Show loading state
        saveProgressBtn.innerHTML = `
            <svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Saving...
        `;
        
        // Simulate save (you would implement actual AJAX save here)
        setTimeout(() => {
            saveProgressBtn.innerHTML = `
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Progress Saved
            `;
            saveProgressBtn.classList.remove('bg-indigo-50', 'border-indigo-200', 'text-indigo-700', 'hover:bg-indigo-100');
            saveProgressBtn.classList.add('bg-green-50', 'border-green-200', 'text-green-700', 'hover:bg-green-100');
            
            // Reset after 2 seconds
            setTimeout(() => {
                saveProgressBtn.innerHTML = `
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    Save Progress
                `;
                saveProgressBtn.classList.remove('bg-green-50', 'border-green-200', 'text-green-700', 'hover:bg-green-100');
                saveProgressBtn.classList.add('bg-indigo-50', 'border-indigo-200', 'text-indigo-700', 'hover:bg-indigo-100');
            }, 2000);
        }, 1000);
    });
    
    // Form validation
    form.addEventListener('submit', function(e) {
        let hasErrors = false;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                hasErrors = true;
                field.classList.add('border-red-300', 'focus:border-red-500', 'focus:ring-red-500');
                field.classList.remove('border-gray-300', 'focus:border-indigo-500', 'focus:ring-indigo-500');
            } else {
                field.classList.remove('border-red-300', 'focus:border-red-500', 'focus:ring-red-500');
                field.classList.add('border-gray-300', 'focus:border-indigo-500', 'focus:ring-indigo-500');
            }
        });
        
        if (hasErrors) {
            e.preventDefault();
            window.scrollTo({top: 0, behavior: 'smooth'});
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>