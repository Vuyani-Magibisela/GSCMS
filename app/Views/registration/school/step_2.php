<?php
ob_start();
$layout = 'public';
$title = 'Step 2: Contact Information - School Registration';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <!-- Progress Header -->
        <div class="max-w-4xl mx-auto mb-8">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <!-- Progress Bar -->
                <div class="flex items-center justify-between mb-4">
                    <h1 class="text-2xl font-bold text-gray-900">School Registration</h1>
                    <span class="text-sm text-gray-500">Step 2 of 5</span>
                </div>
                
                <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                    <div class="bg-indigo-600 h-2 rounded-full" style="width: 40%"></div>
                </div>
                
                <!-- Step Indicators -->
                <div class="flex items-center justify-between text-xs">
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center font-semibold mb-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <span class="text-green-600 font-medium">School Info</span>
                    </div>
                    <div class="flex-1 h-px bg-green-200 mx-2"></div>
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-semibold mb-1">2</div>
                        <span class="text-indigo-600 font-medium">Contact</span>
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
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">Contact Information</h2>
                        <p class="text-gray-600">
                            Please provide contact details for your school principal and the designated competition coordinator. 
                            This information will be used for all official communications.
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

                    <form id="step2Form" method="POST" action="/register/school/process-step/2" class="space-y-8">
                        <!-- Principal Information -->
                        <div class="bg-gray-50 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Principal Information
                            </h3>
                            
                            <div class="grid md:grid-cols-1 gap-6">
                                <!-- Principal Name -->
                                <div>
                                    <label for="principal_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Principal Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           id="principal_name" 
                                           name="principal_name" 
                                           value="<?php echo htmlspecialchars($registration['principal_name'] ?? ''); ?>"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200"
                                           placeholder="Enter the full name of your principal"
                                           required>
                                </div>
                                
                                <!-- Principal Email and Phone -->
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="principal_email" class="block text-sm font-medium text-gray-700 mb-2">
                                            Principal Email <span class="text-red-500">*</span>
                                        </label>
                                        <input type="email" 
                                               id="principal_email" 
                                               name="principal_email" 
                                               value="<?php echo htmlspecialchars($registration['principal_email'] ?? ''); ?>"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200"
                                               placeholder="principal@school.co.za"
                                               required>
                                    </div>
                                    
                                    <div>
                                        <label for="principal_phone" class="block text-sm font-medium text-gray-700 mb-2">
                                            Principal Phone <span class="text-red-500">*</span>
                                        </label>
                                        <input type="tel" 
                                               id="principal_phone" 
                                               name="principal_phone" 
                                               value="<?php echo htmlspecialchars($registration['principal_phone'] ?? ''); ?>"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200"
                                               placeholder="011 123 4567"
                                               required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Competition Coordinator Information -->
                        <div class="bg-blue-50 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                Competition Coordinator
                            </h3>
                            <p class="text-sm text-blue-700 mb-4">
                                The competition coordinator will be the primary contact for all competition-related matters, 
                                including registration updates, deadlines, and event communications.
                            </p>
                            
                            <div class="grid md:grid-cols-1 gap-6">
                                <!-- Coordinator Name -->
                                <div>
                                    <label for="coordinator_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Coordinator Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           id="coordinator_name" 
                                           name="coordinator_name" 
                                           value="<?php echo htmlspecialchars($registration['coordinator_name'] ?? ''); ?>"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200"
                                           placeholder="Enter the coordinator's full name"
                                           required>
                                    <p class="mt-1 text-xs text-gray-500">
                                        This could be a teacher, HOD, or designated staff member
                                    </p>
                                </div>
                                
                                <!-- Coordinator Email and Phone -->
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="coordinator_email" class="block text-sm font-medium text-gray-700 mb-2">
                                            Coordinator Email <span class="text-red-500">*</span>
                                        </label>
                                        <input type="email" 
                                               id="coordinator_email" 
                                               name="coordinator_email" 
                                               value="<?php echo htmlspecialchars($registration['coordinator_email'] ?? ''); ?>"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200"
                                               placeholder="coordinator@school.co.za"
                                               required>
                                        <p class="mt-1 text-xs text-gray-500">Primary contact for updates</p>
                                    </div>
                                    
                                    <div>
                                        <label for="coordinator_phone" class="block text-sm font-medium text-gray-700 mb-2">
                                            Coordinator Phone <span class="text-red-500">*</span>
                                        </label>
                                        <input type="tel" 
                                               id="coordinator_phone" 
                                               name="coordinator_phone" 
                                               value="<?php echo htmlspecialchars($registration['coordinator_phone'] ?? ''); ?>"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200"
                                               placeholder="011 123 4567"
                                               required>
                                        <p class="mt-1 text-xs text-gray-500">For urgent communications</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- School General Contact -->
                        <div class="bg-gray-50 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 text-gray-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-2M7 21h2m3-18v18m2-18v18"/>
                                </svg>
                                School General Contact
                            </h3>
                            
                            <div>
                                <label for="school_email" class="block text-sm font-medium text-gray-700 mb-2">
                                    School Official Email <span class="text-red-500">*</span>
                                </label>
                                <input type="email" 
                                       id="school_email" 
                                       name="school_email" 
                                       value="<?php echo htmlspecialchars($registration['school_email'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200"
                                       placeholder="info@school.co.za"
                                       required>
                                <p class="mt-1 text-sm text-gray-500">
                                    Main school email address for official correspondence
                                </p>
                            </div>
                        </div>

                        <!-- Information Panel -->
                        <div class="bg-amber-50 border-l-4 border-amber-400 p-4 rounded">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-amber-700">
                                        <strong>Important:</strong> The competition coordinator will receive all updates, 
                                        deadlines, and important communications. Please ensure this person is actively 
                                        involved in managing your school's participation and can respond promptly to requests.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Navigation Footer -->
                <div class="bg-gray-50 px-8 py-4 rounded-b-lg">
                    <div class="flex items-center justify-between">
                        <a href="/register/school/step/1" 
                           class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Previous Step
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
                                    form="step2Form"
                                    class="inline-flex items-center px-6 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-lg hover:bg-indigo-700 transition duration-200">
                                Continue to Step 3
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
    const form = document.getElementById('step2Form');
    const saveProgressBtn = document.getElementById('saveProgressBtn');
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    
    // Format phone number inputs
    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            // Format as XXX XXX XXXX
            if (value.length >= 3) {
                value = value.substring(0, 3) + ' ' + value.substring(3);
            }
            if (value.length >= 7) {
                value = value.substring(0, 7) + ' ' + value.substring(7);
            }
            
            e.target.value = value.substring(0, 12); // Limit length
        });
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
        
        // Simulate save
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
    
    // Email validation
    const emailInputs = document.querySelectorAll('input[type="email"]');
    emailInputs.forEach(input => {
        input.addEventListener('blur', function() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (this.value && !emailRegex.test(this.value)) {
                this.classList.add('border-red-300', 'focus:border-red-500', 'focus:ring-red-500');
                this.classList.remove('border-gray-300', 'focus:border-indigo-500', 'focus:ring-indigo-500');
            } else {
                this.classList.remove('border-red-300', 'focus:border-red-500', 'focus:ring-red-500');
                this.classList.add('border-gray-300', 'focus:border-indigo-500', 'focus:ring-indigo-500');
            }
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>