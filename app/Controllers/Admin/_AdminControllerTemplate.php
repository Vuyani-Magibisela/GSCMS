<?php
/**
 * Admin Controller Template
 *
 * Copy this file and rename it for your new admin controller.
 * This template includes all the common patterns and error handling.
 *
 * USAGE:
 * 1. Copy this file and rename: YourFeatureController.php
 * 2. Replace "YourFeature" with your actual feature name throughout
 * 3. Update the model imports and table references
 * 4. Add your specific business logic
 * 5. Create corresponding views in app/Views/admin/yourfeature/
 * 6. Add routes to routes/web.php in the admin group
 */

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
// Add your model imports here
// use App\Models\YourModel;

class YourFeatureController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin(); // ✅ This method is available in BaseController
    }

    /**
     * Display feature overview (index page)
     */
    public function index()
    {
        try {
            // Get your data with proper error handling
            $items = $this->getItemsWithStats();
            $stats = $this->getFeatureStats();

            return $this->view('admin/yourfeature/index', [
                'items' => $items,
                'stats' => $stats,
                'title' => 'Your Feature Management',
                'pageTitle' => 'Your Feature',
                'pageSubtitle' => 'Manage and monitor your feature'
            ]);

        } catch (\Exception $e) {
            error_log("YourFeature index error: " . $e->getMessage());
            $this->flash('error', 'Error loading your feature: ' . $e->getMessage());
            return $this->redirect('/admin/dashboard');
        }
    }

    /**
     * Show create form
     */
    public function create()
    {
        try {
            // Get any reference data needed for the form
            $referenceData = $this->getReferenceData();

            return $this->view('admin/yourfeature/create', [
                'referenceData' => $referenceData,
                'title' => 'Create Your Feature',
                'pageTitle' => 'Create New Item',
                'pageSubtitle' => 'Add a new item to your feature'
            ]);

        } catch (\Exception $e) {
            error_log("YourFeature create form error: " . $e->getMessage());
            $this->flash('error', 'Error loading create form: ' . $e->getMessage());
            return $this->redirect('/admin/yourfeature');
        }
    }

    /**
     * Store new item
     */
    public function store()
    {
        try {
            // Validate input
            $validation = $this->validateItemData();
            if (!$validation['valid']) {
                $this->flash('error', 'Validation failed: ' . implode(', ', $validation['errors']));
                return $this->redirect('/admin/yourfeature/create');
            }

            // Create item with proper data sanitization
            $itemData = [
                'name' => $this->input('name'),
                'description' => $this->input('description'),
                'status' => $this->input('status', 'active'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
                // Add your specific fields here
            ];

            // Use your model's create method
            $itemId = YourModel::create($itemData);

            if ($itemId) {
                $this->flash('success', 'Item created successfully!');
                return $this->redirect('/admin/yourfeature/' . $itemId);
            } else {
                $this->flash('error', 'Failed to create item');
                return $this->redirect('/admin/yourfeature/create');
            }

        } catch (\Exception $e) {
            error_log("YourFeature store error: " . $e->getMessage());
            $this->flash('error', 'Error creating item: ' . $e->getMessage());
            return $this->redirect('/admin/yourfeature/create');
        }
    }

    /**
     * Show specific item
     */
    public function show($id)
    {
        try {
            $item = YourModel::find($id);
            if (!$item) {
                $this->flash('error', 'Item not found');
                return $this->redirect('/admin/yourfeature');
            }

            // Get related data
            $relatedData = $this->getRelatedData($id);

            return $this->view('admin/yourfeature/show', [
                'item' => $item,
                'relatedData' => $relatedData,
                'title' => $item['name'],
                'pageTitle' => $item['name'],
                'pageSubtitle' => 'Item Details'
            ]);

        } catch (\Exception $e) {
            error_log("YourFeature show error: " . $e->getMessage());
            $this->flash('error', 'Error loading item: ' . $e->getMessage());
            return $this->redirect('/admin/yourfeature');
        }
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        try {
            $item = YourModel::find($id);
            if (!$item) {
                $this->flash('error', 'Item not found');
                return $this->redirect('/admin/yourfeature');
            }

            $referenceData = $this->getReferenceData();

            return $this->view('admin/yourfeature/edit', [
                'item' => $item,
                'referenceData' => $referenceData,
                'title' => 'Edit Item',
                'pageTitle' => 'Edit Item',
                'pageSubtitle' => $item['name']
            ]);

        } catch (\Exception $e) {
            error_log("YourFeature edit form error: " . $e->getMessage());
            $this->flash('error', 'Error loading edit form: ' . $e->getMessage());
            return $this->redirect('/admin/yourfeature');
        }
    }

    /**
     * Update item
     */
    public function update($id)
    {
        try {
            $item = YourModel::find($id);
            if (!$item) {
                $this->flash('error', 'Item not found');
                return $this->redirect('/admin/yourfeature');
            }

            // Validate input
            $validation = $this->validateItemData();
            if (!$validation['valid']) {
                $this->flash('error', 'Validation failed: ' . implode(', ', $validation['errors']));
                return $this->redirect('/admin/yourfeature/' . $id . '/edit');
            }

            // Update item
            $itemData = [
                'name' => $this->input('name'),
                'description' => $this->input('description'),
                'status' => $this->input('status'),
                'updated_at' => date('Y-m-d H:i:s')
                // Add your specific fields here
            ];

            $updated = YourModel::update($id, $itemData);

            if ($updated) {
                $this->flash('success', 'Item updated successfully!');
                return $this->redirect('/admin/yourfeature/' . $id);
            } else {
                $this->flash('error', 'Failed to update item');
                return $this->redirect('/admin/yourfeature/' . $id . '/edit');
            }

        } catch (\Exception $e) {
            error_log("YourFeature update error: " . $e->getMessage());
            $this->flash('error', 'Error updating item: ' . $e->getMessage());
            return $this->redirect('/admin/yourfeature/' . $id . '/edit');
        }
    }

    /**
     * Delete item
     */
    public function destroy($id)
    {
        try {
            $item = YourModel::find($id);
            if (!$item) {
                $this->flash('error', 'Item not found');
                return $this->redirect('/admin/yourfeature');
            }

            // Check if item can be deleted (add your business logic)
            if ($this->hasRelatedData($id)) {
                $this->flash('error', 'Cannot delete item: it has related data');
                return $this->redirect('/admin/yourfeature/' . $id);
            }

            $deleted = YourModel::delete($id);

            if ($deleted) {
                $this->flash('success', 'Item deleted successfully!');
            } else {
                $this->flash('error', 'Failed to delete item');
            }

            return $this->redirect('/admin/yourfeature');

        } catch (\Exception $e) {
            error_log("YourFeature delete error: " . $e->getMessage());
            $this->flash('error', 'Error deleting item: ' . $e->getMessage());
            return $this->redirect('/admin/yourfeature');
        }
    }

    // ========================================================================
    // PRIVATE HELPER METHODS
    // ========================================================================

    /**
     * Get items with statistics for the index page
     */
    private function getItemsWithStats()
    {
        // Example query - modify for your needs
        $query = "SELECT y.*,
                         COUNT(DISTINCT r.id) as related_count
                  FROM your_table y
                  LEFT JOIN related_table r ON y.id = r.your_table_id
                  GROUP BY y.id
                  ORDER BY y.created_at DESC";

        return $this->db->query($query);
    }

    /**
     * Get feature statistics for dashboard
     */
    private function getFeatureStats()
    {
        $stats = [
            'total' => 0,
            'active' => 0,
            'inactive' => 0
        ];

        try {
            $result = $this->db->query("
                SELECT
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
                    COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive
                FROM your_table
            ");

            if (!empty($result)) {
                $stats = $result[0];
            }
        } catch (\Exception $e) {
            error_log("Error getting feature stats: " . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Get reference data for forms
     */
    private function getReferenceData()
    {
        // Example: Get dropdown options, related tables, etc.
        return [
            'statuses' => [
                'active' => 'Active',
                'inactive' => 'Inactive',
                'draft' => 'Draft'
            ]
            // Add your reference data here
        ];
    }

    /**
     * Get related data for a specific item
     */
    private function getRelatedData($itemId)
    {
        // Example: Get related records, counts, etc.
        return $this->db->query("
            SELECT r.*
            FROM related_table r
            WHERE r.your_table_id = ?
            ORDER BY r.created_at DESC
        ", [$itemId]);
    }

    /**
     * Check if item has related data (for deletion validation)
     */
    private function hasRelatedData($itemId)
    {
        $result = $this->db->query("SELECT COUNT(*) as count FROM related_table WHERE your_table_id = ?", [$itemId]);
        return ($result[0]['count'] ?? 0) > 0;
    }

    /**
     * Validate item data for create/update operations
     */
    private function validateItemData()
    {
        $errors = [];

        // Required fields
        if (empty($this->input('name'))) {
            $errors[] = 'Name is required';
        }

        // Length validation
        if (strlen($this->input('name')) > 255) {
            $errors[] = 'Name must be less than 255 characters';
        }

        // Status validation
        $validStatuses = ['active', 'inactive', 'draft'];
        if (!in_array($this->input('status'), $validStatuses)) {
            $errors[] = 'Invalid status';
        }

        // Add your specific validation rules here

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}