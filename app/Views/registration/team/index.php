<?php
ob_start();
$layout = 'app';
$title = 'Team Registration Dashboard - GDE SciBOTICS Competition 2025';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <!-- Header Section -->
        <div class="mb-8">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">Team Registration</h1>
                        <p class="text-gray-600">
                            Manage your school's team registrations for the GDE SciBOTICS Competition
                        </p>
                    </div>
                    
                    <?php if ($can_register_new): ?>
                        <a href="/register/team/select-category" 
                           class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition duration-200">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Register New Team
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Deadline Info -->
                <?php if (isset($deadline_info) && !$deadline_info['is_overdue']): ?>
                <div class="mt-4 bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                <strong>Team Registration Deadline:</strong> 
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
        </div>

        <!-- Category Overview -->
        <div class="grid lg:grid-cols-3 gap-6 mb-8">
            <div class="lg:col-span-2">
                <!-- Existing Team Registrations -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                            <svg class="w-6 h-6 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.196-2.121L17 20zM9 20H4v-2a3 3 0 015.196-2.121L9 20zm8-10a3 3 0 11-6 0 3 3 0 016 0zm-10 0a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Your Team Registrations
                        </h2>
                    </div>
                    
                    <div class="p-6">
                        <?php if (empty($team_registrations)): ?>
                            <div class="text-center py-8">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.196-2.121L17 20zM9 20H4v-2a3 3 0 015.196-2.121L9 20zm8-10a3 3 0 11-6 0 3 3 0 016 0zm-10 0a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No Team Registrations</h3>
                                <p class="text-gray-500 mb-6">You haven't registered any teams yet.</p>
                                
                                <?php if ($can_register_new): ?>
                                    <a href="/register/team/select-category" 
                                       class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition duration-200">
                                        Register Your First Team
                                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($team_registrations as $registration): ?>
                                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition duration-200">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <div class="flex items-center mb-2">
                                                    <h3 class="text-lg font-semibold text-gray-900 mr-3">
                                                        <?php echo htmlspecialchars($registration->team_name); ?>
                                                    </h3>
                                                    
                                                    <!-- Status Badge -->
                                                    <?php
                                                    $statusColors = [
                                                        'draft' => 'bg-gray-100 text-gray-800',
                                                        'submitted' => 'bg-blue-100 text-blue-800',
                                                        'under_review' => 'bg-yellow-100 text-yellow-800',
                                                        'approved' => 'bg-green-100 text-green-800',
                                                        'rejected' => 'bg-red-100 text-red-800',
                                                        'withdrawn' => 'bg-gray-100 text-gray-800'
                                                    ];
                                                    $statusColor = $statusColors[$registration->registration_status] ?? 'bg-gray-100 text-gray-800';
                                                    ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusColor; ?>">
                                                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $registration->registration_status))); ?>
                                                    </span>
                                                </div>
                                                
                                                <div class="text-sm text-gray-600 space-y-1">
                                                    <div class="flex items-center">
                                                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                                        </svg>
                                                        Category: <strong><?php echo htmlspecialchars($registration->category->name ?? 'Not specified'); ?></strong>
                                                    </div>
                                                    <div class="flex items-center">
                                                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                                                        </svg>
                                                        Participants: <strong><?php echo $registration->participant_count; ?>/<?php echo $registration->max_participants; ?></strong>
                                                    </div>
                                                    <div class="flex items-center">
                                                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                        Completion: <strong><?php echo $registration->calculateCompletionPercentage(); ?>%</strong>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-center space-x-2 ml-4">
                                                <a href="/register/team/<?php echo $registration->id; ?>" 
                                                   class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-indigo-600 bg-indigo-50 rounded-md hover:bg-indigo-100 transition duration-200">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                    View
                                                </a>
                                                
                                                <?php if (in_array($registration->registration_status, ['draft', 'submitted']) && !$registration->locked_for_modifications): ?>
                                                    <a href="/register/team/<?php echo $registration->id; ?>/edit" 
                                                       class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-600 bg-gray-50 rounded-md hover:bg-gray-100 transition duration-200">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                        </svg>
                                                        Edit
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Category Availability Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                            <svg class="w-6 h-6 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                            Category Status
                        </h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="space-y-3">
                            <?php foreach ($categories as $category): ?>
                                <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                                    <div class="flex-1">
                                        <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($category->name); ?></h3>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($category->description ?? ''); ?></p>
                                    </div>
                                    
                                    <div class="ml-3">
                                        <?php if ($category->existing_registration): ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                Registered
                                            </span>
                                        <?php elseif ($category->can_register): ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Available
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800" 
                                                  title="<?php echo htmlspecialchars($category->violation_reason ?? 'Not available'); ?>">
                                                Limited
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if ($can_register_new): ?>
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <a href="/register/team/select-category" 
                                   class="w-full inline-flex items-center justify-center px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    Register New Team
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="mt-6 bg-white rounded-lg shadow-sm">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Registration Summary</h3>
                        
                        <div class="space-y-3">
                            <?php
                            $totalRegistrations = count($team_registrations);
                            $completedRegistrations = array_filter($team_registrations, function($reg) {
                                return in_array($reg->registration_status, ['approved', 'submitted']);
                            });
                            $draftRegistrations = array_filter($team_registrations, function($reg) {
                                return $reg->registration_status === 'draft';
                            });
                            ?>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Total Teams</span>
                                <span class="font-semibold text-gray-900"><?php echo $totalRegistrations; ?></span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Submitted/Approved</span>
                                <span class="font-semibold text-green-600"><?php echo count($completedRegistrations); ?></span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">In Progress</span>
                                <span class="font-semibold text-yellow-600"><?php echo count($draftRegistrations); ?></span>
                            </div>
                            
                            <div class="flex justify-between items-center pt-2 border-t border-gray-200">
                                <span class="text-sm text-gray-600">Categories Available</span>
                                <span class="font-semibold text-indigo-600">
                                    <?php echo count(array_filter($categories, function($cat) { return $cat->can_register; })); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Help Section -->
        <div class="bg-blue-50 border-l-4 border-blue-400 p-6 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-lg font-medium text-blue-800 mb-2">Team Registration Guidelines</h3>
                    <div class="text-sm text-blue-700 space-y-2">
                        <p><strong>Registration Limits:</strong> Schools can register a maximum of one team per category.</p>
                        <p><strong>Team Size:</strong> Teams must have between 2-4 participants depending on the category.</p>
                        <p><strong>Coaches Required:</strong> Each team must have at least one designated coach.</p>
                        <p><strong>Documentation:</strong> All teams require completed consent forms and registration documents.</p>
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