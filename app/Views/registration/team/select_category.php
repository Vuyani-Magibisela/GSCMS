<?php
ob_start();
$layout = 'app';
$title = 'Select Competition Category - Team Registration';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <!-- Header Section -->
        <div class="max-w-4xl mx-auto mb-8">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">Select Competition Category</h1>
                        <p class="text-gray-600">
                            Choose the category for your new team registration. Each school can register one team per category.
                        </p>
                    </div>
                    
                    <a href="/register/team" 
                       class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Back to Dashboard
                    </a>
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
        </div>

        <!-- Category Selection -->
        <div class="max-w-4xl mx-auto">
            <?php if (empty($categories)): ?>
                <!-- No Categories Available -->
                <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900 mb-2">No Categories Available</h2>
                    <p class="text-gray-600 mb-6">
                        All available categories have been registered or you have reached the maximum team registration limit.
                    </p>
                    <a href="/register/team" 
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition duration-200">
                        Return to Dashboard
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            <?php else: ?>
                <!-- Category Cards -->
                <div class="grid md:grid-cols-2 gap-6">
                    <?php foreach ($categories as $category): ?>
                        <div class="bg-white rounded-lg shadow-sm border-2 border-transparent hover:border-indigo-200 transition duration-200">
                            <div class="p-6">
                                <!-- Category Header -->
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <h3 class="text-xl font-bold text-gray-900 mb-2">
                                            <?php echo htmlspecialchars($category->name); ?>
                                        </h3>
                                        <p class="text-sm text-gray-600 mb-3">
                                            <?php echo htmlspecialchars($category->description ?? ''); ?>
                                        </p>
                                    </div>
                                    
                                    <!-- Category Badge -->
                                    <div class="ml-4">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                            <?php echo htmlspecialchars($category->code ?? 'CAT'); ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- Category Details -->
                                <div class="space-y-3 mb-6">
                                    <!-- Participant Requirements -->
                                    <div class="flex items-center text-sm text-gray-600">
                                        <svg class="w-4 h-4 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                                        </svg>
                                        <strong>Team Size:</strong> <?php echo $category->min_participants ?? 2; ?>-<?php echo $category->max_participants ?? 4; ?> participants
                                    </div>
                                    
                                    <!-- Grade Requirements -->
                                    <?php if (isset($category->grade_requirements)): ?>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <svg class="w-4 h-4 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                        </svg>
                                        <strong>Grades:</strong> <?php echo htmlspecialchars($category->grade_requirements); ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Age Requirements -->
                                    <?php if (isset($category->age_min) && isset($category->age_max)): ?>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <svg class="w-4 h-4 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <strong>Age Range:</strong> <?php echo $category->age_min; ?>-<?php echo $category->age_max; ?> years
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Competition Format -->
                                    <?php if (isset($category->competition_format)): ?>
                                    <div class="flex items-start text-sm text-gray-600">
                                        <svg class="w-4 h-4 text-gray-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                        </svg>
                                        <div>
                                            <strong>Format:</strong> <?php echo htmlspecialchars($category->competition_format); ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Requirements Badge -->
                                <?php if (isset($category->special_requirements)): ?>
                                <div class="bg-amber-50 border-l-4 border-amber-400 p-3 mb-4 rounded">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <svg class="w-4 h-4 text-amber-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                        <div class="ml-2">
                                            <p class="text-xs text-amber-700">
                                                <strong>Special Requirements:</strong> <?php echo htmlspecialchars($category->special_requirements); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Action Button -->
                                <div class="pt-4 border-t border-gray-200">
                                    <a href="/register/team/create?category_id=<?php echo $category->id; ?>" 
                                       class="w-full inline-flex items-center justify-center px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition duration-200">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                        Register Team in <?php echo htmlspecialchars($category->name); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Information Panel -->
            <div class="mt-8 bg-blue-50 border-l-4 border-blue-400 p-6 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-medium text-blue-800 mb-2">Category Selection Guidelines</h3>
                        <div class="text-sm text-blue-700 space-y-2">
                            <p><strong>One Team Per Category:</strong> Each school can only register one team per competition category.</p>
                            <p><strong>Age & Grade Requirements:</strong> All participants must meet the age and grade requirements for the selected category.</p>
                            <p><strong>Team Composition:</strong> Teams must have the required number of participants as specified for each category.</p>
                            <p><strong>Coach Assignment:</strong> Each team must have at least one qualified coach assigned before submission.</p>
                            <p><strong>Category Changes:</strong> Once submitted, category changes are not permitted. Choose carefully!</p>
                        </div>
                        
                        <div class="mt-4 p-3 bg-blue-100 rounded">
                            <p class="text-sm text-blue-800">
                                <strong>Need Help?</strong> Contact our support team at 
                                <a href="mailto:support@gdescibiotics.co.za" class="underline">support@gdescibiotics.co.za</a> 
                                if you're unsure which category is best for your team.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for enhanced interactions -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const categoryCards = document.querySelectorAll('.bg-white.rounded-lg.shadow-sm');
    
    categoryCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.classList.add('shadow-lg');
            this.classList.remove('shadow-sm');
        });
        
        card.addEventListener('mouseleave', function() {
            this.classList.remove('shadow-lg');
            this.classList.add('shadow-sm');
        });
    });
    
    // Confirmation dialog for category selection
    const registerButtons = document.querySelectorAll('a[href*="category_id"]');
    
    registerButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const categoryName = this.textContent.replace('Register Team in ', '').trim();
            
            if (!confirm(`Are you sure you want to register a team in the ${categoryName} category? This cannot be changed after submission.`)) {
                e.preventDefault();
            }
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>