<?php
ob_start();
$layout = 'public';
$title = 'Step 3: Physical Address - School Registration';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <!-- Progress Header -->
        <div class="max-w-4xl mx-auto mb-8">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <!-- Progress Bar -->
                <div class="flex items-center justify-between mb-4">
                    <h1 class="text-2xl font-bold text-gray-900">School Registration</h1>
                    <span class="text-sm text-gray-500">Step 3 of 5</span>
                </div>
                
                <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                    <div class="bg-indigo-600 h-2 rounded-full" style="width: 60%"></div>
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
                        <div class="w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center font-semibold mb-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <span class="text-green-600 font-medium">Contact</span>
                    </div>
                    <div class="flex-1 h-px bg-green-200 mx-2"></div>
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-semibold mb-1">3</div>
                        <span class="text-indigo-600 font-medium">Address</span>
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
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">Physical Address</h2>
                        <p class="text-gray-600">
                            Please provide your school's complete physical address. This information will be used 
                            for official correspondence, venue planning, and district classification.
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

                    <form id="step3Form" method="POST" action="/register/school/process-step/3" class="space-y-6">
                        <!-- Address Line 1 -->
                        <div>
                            <label for="address_line1" class="block text-sm font-medium text-gray-700 mb-2">
                                Street Address <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="address_line1" 
                                   name="address_line1" 
                                   value="<?php echo htmlspecialchars($registration['address_line1'] ?? ''); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200"
                                   placeholder="123 Main Street, Suburb Name"
                                   minlength="20"
                                   required>
                            <p class="mt-1 text-sm text-gray-500">
                                Complete street address including street number, name, and suburb (minimum 20 characters)
                            </p>
                        </div>

                        <!-- Address Line 2 -->
                        <div>
                            <label for="address_line2" class="block text-sm font-medium text-gray-700 mb-2">
                                Additional Address Information <span class="text-gray-400">(Optional)</span>
                            </label>
                            <input type="text" 
                                   id="address_line2" 
                                   name="address_line2" 
                                   value="<?php echo htmlspecialchars($registration['address_line2'] ?? ''); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200"
                                   placeholder="Building name, floor, unit number (if applicable)">
                            <p class="mt-1 text-sm text-gray-500">
                                Additional address details such as building name, complex, or unit number
                            </p>
                        </div>

                        <!-- City and Postal Code -->
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label for="city" class="block text-sm font-medium text-gray-700 mb-2">
                                    City/Town <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="city" 
                                       name="city" 
                                       value="<?php echo htmlspecialchars($registration['city'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200"
                                       placeholder="Johannesburg"
                                       required>
                            </div>
                            
                            <div>
                                <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-2">
                                    Postal Code <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="postal_code" 
                                       name="postal_code" 
                                       value="<?php echo htmlspecialchars($registration['postal_code'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200"
                                       placeholder="2000"
                                       pattern="[0-9]{4}"
                                       maxlength="4"
                                       required>
                                <p class="mt-1 text-sm text-gray-500">
                                    4-digit South African postal code
                                </p>
                            </div>
                        </div>

                        <!-- District Selection -->
                        <div class="bg-blue-50 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                District Classification
                            </h3>
                            
                            <div>
                                <label for="district_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    GDE District <span class="text-red-500">*</span>
                                </label>
                                <select id="district_id" 
                                        name="district_id" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200"
                                        required>
                                    <option value="">Select your district</option>
                                    <?php if (isset($step_data['districts'])): ?>
                                        <?php foreach ($step_data['districts'] as $district): ?>
                                            <option value="<?php echo $district->id; ?>" 
                                                    <?php echo ($registration['district_id'] ?? '') == $district->id ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($district->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <!-- Fallback district options -->
                                        <option value="1" <?php echo ($registration['district_id'] ?? '') == '1' ? 'selected' : ''; ?>>Gauteng East</option>
                                        <option value="2" <?php echo ($registration['district_id'] ?? '') == '2' ? 'selected' : ''; ?>>Gauteng West</option>
                                        <option value="3" <?php echo ($registration['district_id'] ?? '') == '3' ? 'selected' : ''; ?>>Gauteng North</option>
                                        <option value="4" <?php echo ($registration['district_id'] ?? '') == '4' ? 'selected' : ''; ?>>Gauteng South</option>
                                        <option value="5" <?php echo ($registration['district_id'] ?? '') == '5' ? 'selected' : ''; ?>>Johannesburg Central</option>
                                        <option value="6" <?php echo ($registration['district_id'] ?? '') == '6' ? 'selected' : ''; ?>>Johannesburg East</option>
                                        <option value="7" <?php echo ($registration['district_id'] ?? '') == '7' ? 'selected' : ''; ?>>Johannesburg West</option>
                                        <option value="8" <?php echo ($registration['district_id'] ?? '') == '8' ? 'selected' : '';?>">Johannesburg South</option>
                                        <option value="9" <?php echo ($registration['district_id'] ?? '') == '9' ? 'selected' : ''; ?>>Johannesburg North</option>
                                        <option value="10" <?php echo ($registration['district_id'] ?? '') == '10' ? 'selected' : ''; ?>>Ekurhuleni East</option>
                                        <option value="11" <?php echo ($registration['district_id'] ?? '') == '11' ? 'selected' : ''; ?>>Ekurhuleni West</option>
                                        <option value="12" <?php echo ($registration['district_id'] ?? '') == '12' ? 'selected' : ''; ?>>Ekurhuleni North</option>
                                        <option value="13" <?php echo ($registration['district_id'] ?? '') == '13' ? 'selected' : ''; ?>>Ekurhuleni South</option>
                                        <option value="14" <?php echo ($registration['district_id'] ?? '') == '14' ? 'selected' : ''; ?>>Tshwane North</option>
                                        <option value="15" <?php echo ($registration['district_id'] ?? '') == '15' ? 'selected' : ''; ?>>Tshwane South</option>
                                        <option value="16" <?php echo ($registration['district_id'] ?? '') == '16' ? 'selected' : ''; ?>>Tshwane West</option>
                                        <option value="17" <?php echo ($registration['district_id'] ?? '') == '17' ? 'selected' : ''; ?>>Tshwane East</option>
                                        <option value="18" <?php echo ($registration['district_id'] ?? '') == '18' ? 'selected' : ''; ?>>Sedibeng East</option>
                                        <option value="19" <?php echo ($registration['district_id'] ?? '') == '19' ? 'selected' : ''; ?>>Sedibeng West</option>
                                        <option value="20" <?php echo ($registration['district_id'] ?? '') == '20' ? 'selected' : ''; ?>>West Rand</option>
                                    <?php endif; ?>
                                </select>
                                <p class="mt-1 text-sm text-blue-700">
                                    Select the GDE district where your school is located. This determines competition logistics and venue assignments.
                                </p>
                            </div>
                        </div>

                        <!-- Optional Fields -->
                        <div class="bg-gray-50 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 text-gray-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Additional Information <span class="text-gray-400">(Optional)</span>
                            </h3>
                            
                            <div class="space-y-4">
                                <!-- GPS Coordinates -->
                                <div>
                                    <label for="gps_coordinates" class="block text-sm font-medium text-gray-700 mb-2">
                                        GPS Coordinates <span class="text-gray-400">(Optional)</span>
                                    </label>
                                    <input type="text" 
                                           id="gps_coordinates" 
                                           name="gps_coordinates" 
                                           value="<?php echo htmlspecialchars($registration['gps_coordinates'] ?? ''); ?>"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200"
                                           placeholder="-26.1234567, 28.1234567">
                                    <p class="mt-1 text-sm text-gray-500">
                                        Latitude and longitude coordinates (useful for delivery and navigation purposes)
                                    </p>
                                </div>
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
                                        <strong>Address Verification:</strong> Please ensure your address is accurate and complete. 
                                        This address will be used for official mail delivery, equipment transport, and emergency contact purposes. 
                                        The district selection affects competition venue assignments and transport arrangements.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Navigation Footer -->
                <div class="bg-gray-50 px-8 py-4 rounded-b-lg">
                    <div class="flex items-center justify-between">
                        <a href="/register/school/step/2" 
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
                                    form="step3Form"
                                    class="inline-flex items-center px-6 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-lg hover:bg-indigo-700 transition duration-200">
                                Continue to Step 4
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
    const form = document.getElementById('step3Form');
    const saveProgressBtn = document.getElementById('saveProgressBtn');
    const postalCodeInput = document.getElementById('postal_code');
    const gpsInput = document.getElementById('gps_coordinates');
    
    // Format postal code input
    postalCodeInput.addEventListener('input', function(e) {
        // Remove non-digits
        let value = e.target.value.replace(/\D/g, '');
        
        // Limit to 4 digits
        if (value.length > 4) {
            value = value.substr(0, 4);
        }
        
        e.target.value = value;
    });
    
    // Validate GPS coordinates format
    gpsInput.addEventListener('blur', function(e) {
        const value = e.target.value.trim();
        if (value) {
            const gpsRegex = /^-?[0-9]+(\.[0-9]+)?,\s*-?[0-9]+(\.[0-9]+)?$/;
            if (!gpsRegex.test(value)) {
                this.classList.add('border-red-300', 'focus:border-red-500', 'focus:ring-red-500');
                this.classList.remove('border-gray-300', 'focus:border-indigo-500', 'focus:ring-indigo-500');
            } else {
                this.classList.remove('border-red-300', 'focus:border-red-500', 'focus:ring-red-500');
                this.classList.add('border-gray-300', 'focus:border-indigo-500', 'focus:ring-indigo-500');
            }
        }
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
    
    // Address validation
    const addressInput = document.getElementById('address_line1');
    addressInput.addEventListener('blur', function() {
        if (this.value.length < 20) {
            this.classList.add('border-red-300', 'focus:border-red-500', 'focus:ring-red-500');
            this.classList.remove('border-gray-300', 'focus:border-indigo-500', 'focus:ring-indigo-500');
        } else {
            this.classList.remove('border-red-300', 'focus:border-red-500', 'focus:ring-red-500');
            this.classList.add('border-gray-300', 'focus:border-indigo-500', 'focus:ring-indigo-500');
        }
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
        
        // Check address length
        if (addressInput.value.length < 20) {
            hasErrors = true;
        }
        
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