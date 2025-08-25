<?php
ob_start();
$layout = 'public';
$title = 'School Registration - GDE SciBOTICS Competition 2025';
?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-12">
    <div class="container mx-auto px-4">
        <!-- Header Section -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-indigo-100 rounded-full mb-6">
                <svg class="w-10 h-10 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-2M7 21h2m3-18v18m2-18v18m-5-8h4m-4-4h4"/>
                </svg>
            </div>
            <h1 class="text-4xl font-bold text-gray-900 mb-4">
                School Registration
            </h1>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                Join the GDE SciBOTICS Competition 2025 - Register your school to participate in South Africa's premier robotics competition
            </p>
        </div>

        <?php if (isset($competition_info)): ?>
        <!-- Competition Info -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8 max-w-4xl mx-auto">
            <div class="flex items-center mb-4">
                <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                <h2 class="text-xl font-semibold text-gray-900">
                    <?php echo htmlspecialchars($competition_info['name']); ?> Registration Open
                </h2>
            </div>
            
            <?php if (isset($deadline_info) && !$deadline_info['is_overdue']): ?>
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            <strong>Registration Deadline:</strong> 
                            <?php echo htmlspecialchars($deadline_info['formatted_deadline']); ?>
                            <?php if ($deadline_info['days_remaining'] > 0): ?>
                                <span class="font-semibold">(<?php echo $deadline_info['days_remaining']; ?> days remaining)</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Registration Options -->
        <div class="max-w-4xl mx-auto">
            <div class="grid md:grid-cols-2 gap-8">
                <!-- New Registration -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-8">
                        <div class="w-16 h-16 bg-indigo-100 rounded-lg flex items-center justify-center mb-6">
                            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">New Registration</h3>
                        <p class="text-gray-600 mb-6">
                            Start your school's registration process for the GDE SciBOTICS Competition. 
                            Complete our 5-step wizard to submit your application.
                        </p>
                        
                        <!-- Registration Steps Preview -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-6">
                            <h4 class="font-semibold text-gray-900 mb-3">Registration Steps:</h4>
                            <ul class="space-y-2 text-sm text-gray-600">
                                <li class="flex items-center">
                                    <span class="w-6 h-6 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center text-xs font-semibold mr-3">1</span>
                                    School Information
                                </li>
                                <li class="flex items-center">
                                    <span class="w-6 h-6 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center text-xs font-semibold mr-3">2</span>
                                    Contact Details
                                </li>
                                <li class="flex items-center">
                                    <span class="w-6 h-6 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center text-xs font-semibold mr-3">3</span>
                                    Physical Address
                                </li>
                                <li class="flex items-center">
                                    <span class="w-6 h-6 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center text-xs font-semibold mr-3">4</span>
                                    School Details
                                </li>
                                <li class="flex items-center">
                                    <span class="w-6 h-6 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center text-xs font-semibold mr-3">5</span>
                                    Competition Preferences
                                </li>
                            </ul>
                        </div>

                        <a href="/register/school/create" 
                           class="w-full bg-indigo-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-indigo-700 transition duration-200 flex items-center justify-center">
                            Start Registration
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Resume Registration -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-8">
                        <div class="w-16 h-16 bg-green-100 rounded-lg flex items-center justify-center mb-6">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Resume Registration</h3>
                        <p class="text-gray-600 mb-6">
                            Continue with a previously saved registration. Your progress is automatically 
                            saved as you complete each step.
                        </p>
                        
                        <div class="bg-amber-50 border-l-4 border-amber-400 p-4 rounded mb-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-amber-700">
                                        Registration progress is saved automatically. You can safely continue from where you left off.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <a href="/register/school/resume" 
                           class="w-full bg-green-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-green-700 transition duration-200 flex items-center justify-center">
                            Continue Registration
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Additional Actions -->
            <div class="mt-8 text-center">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Already Registered?</h3>
                    <p class="text-gray-600 mb-6">
                        Check the status of your existing registration or make updates to your application.
                    </p>
                    <a href="/register/school/status" 
                       class="inline-flex items-center px-6 py-3 border border-indigo-600 text-indigo-600 rounded-lg font-semibold hover:bg-indigo-50 transition duration-200">
                        Check Registration Status
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Help Section -->
            <div class="mt-8 bg-gray-50 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <svg class="w-6 h-6 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Need Help?
                </h3>
                <div class="grid md:grid-cols-2 gap-4 text-sm text-gray-600">
                    <div>
                        <p class="font-medium text-gray-900 mb-2">Registration Requirements:</p>
                        <ul class="space-y-1">
                            <li>• Valid EMIS number</li>
                            <li>• School registration certificate</li>
                            <li>• Principal and coordinator contact details</li>
                            <li>• Physical address and district information</li>
                        </ul>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 mb-2">Support Contact:</p>
                        <ul class="space-y-1">
                            <li>• Email: support@gdescibiotics.co.za</li>
                            <li>• Phone: 011 355 0000</li>
                            <li>• Hours: Mon-Fri 8:00 AM - 4:30 PM</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>