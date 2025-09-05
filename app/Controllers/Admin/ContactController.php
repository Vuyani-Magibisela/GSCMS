<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Contact;
use App\Models\School;
use App\Models\User;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use Exception;

/**
 * Contact Management Controller
 * Handles CRUD operations for school contacts
 */
class ContactController extends BaseController
{
    /**
     * Display list of contacts
     */
    public function index()
    {
        try {
            $request = new Request();
            $schoolId = $request->get('school_id');
            $contactType = $request->get('contact_type');
            $search = $request->get('search');
            
            // Build search criteria
            $criteria = [];
            if ($schoolId) {
                $criteria['school_id'] = $schoolId;
            }
            if ($contactType) {
                $criteria['contact_type'] = $contactType;
            }
            if ($search) {
                $criteria['name'] = $search;
            }
            
            // Get contacts
            $contactModel = new Contact();
            $contacts = $contactModel::search($criteria);
            
            // Get schools for filter dropdown
            $schoolModel = new School();
            $schools = $schoolModel->all();
            
            // Set up breadcrumbs
            $breadcrumbs = [
                ['title' => 'Admin', 'url' => '/admin/dashboard', 'icon' => 'fas fa-shield-alt'],
                ['title' => 'School Contacts', 'url' => '/admin/contacts']
            ];

            return $this->view('admin/contacts/index', [
                'title' => 'School Contacts - GSCMS',
                'pageTitle' => 'School Contact Management',
                'pageSubtitle' => 'Manage contact information for schools',
                'breadcrumbs' => $breadcrumbs,
                'contacts' => $contacts,
                'schools' => $schools,
                'filters' => [
                    'school_id' => $schoolId,
                    'contact_type' => $contactType,
                    'search' => $search
                ],
                'contactTypes' => Contact::getAvailableTypes(),
                'statuses' => Contact::getAvailableStatuses()
            ]);

        } catch (Exception $e) {
            error_log("Contact index error: " . $e->getMessage());
            return $this->errorResponse('Failed to load contacts: ' . $e->getMessage());
        }
    }

    /**
     * Show contact details
     */
    public function show($id)
    {
        try {
            $contactModel = new Contact();
            $contact = $contactModel->find($id);
            
            if (!$contact) {
                return $this->errorResponse('Contact not found', 404);
            }
            
            // Get school information
            $schoolModel = new School();
            $school = $schoolModel->find($contact->school_id);
            
            // Set up breadcrumbs
            $breadcrumbs = [
                ['title' => 'Admin', 'url' => '/admin/dashboard', 'icon' => 'fas fa-shield-alt'],
                ['title' => 'School Contacts', 'url' => '/admin/contacts'],
                ['title' => $contact->getFullName(), 'url' => "/admin/contacts/{$id}"]
            ];

            return $this->view('admin/contacts/show', [
                'title' => 'Contact Details - GSCMS',
                'pageTitle' => $contact->getFullName(),
                'pageSubtitle' => $contact->position . ' - ' . ($school ? $school->name : 'Unknown School'),
                'breadcrumbs' => $breadcrumbs,
                'contact' => $contact,
                'school' => $school
            ]);

        } catch (Exception $e) {
            error_log("Contact show error: " . $e->getMessage());
            return $this->errorResponse('Failed to load contact: ' . $e->getMessage());
        }
    }

    /**
     * Show create contact form
     */
    public function create()
    {
        try {
            $request = new Request();
            $schoolId = $request->get('school_id');
            
            // Get school if specified
            $school = null;
            if ($schoolId) {
                $schoolModel = new School();
                $school = $schoolModel->find($schoolId);
                if (!$school) {
                    return $this->errorResponse('School not found', 404);
                }
            }
            
            // Get all schools for dropdown
            $schoolModel = new School();
            $schools = $schoolModel->all();
            
            // Get users associated with the selected school
            $schoolUsers = [];
            if ($schoolId) {
                $userModel = new User();
                $schoolUsers = $userModel->getBySchool($schoolId);
            }
            
            // Set up breadcrumbs
            $breadcrumbs = [
                ['title' => 'Admin', 'url' => '/admin/dashboard', 'icon' => 'fas fa-shield-alt'],
                ['title' => 'School Contacts', 'url' => '/admin/contacts'],
                ['title' => 'Add New Contact', 'url' => '/admin/contacts/create']
            ];

            return $this->view('admin/contacts/create', [
                'title' => 'Add New Contact - GSCMS',
                'pageTitle' => 'Add New School Contact',
                'pageSubtitle' => $school ? 'Adding contact for ' . $school->name : 'Add contact information',
                'breadcrumbs' => $breadcrumbs,
                'school' => $school,
                'schools' => $schools,
                'schoolUsers' => $schoolUsers,
                'selectedSchoolId' => $schoolId,
                'contactTypes' => Contact::getAvailableTypes(),
                'statuses' => Contact::getAvailableStatuses(),
                'communicationPreferences' => Contact::getCommunicationPreferences(),
                'languagePreferences' => Contact::getLanguagePreferences()
            ]);

        } catch (Exception $e) {
            error_log("Contact create form error: " . $e->getMessage());
            return $this->errorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }

    /**
     * Store new contact
     */
    public function store()
    {
        try {
            $request = new Request();
            $data = $request->all();
            
            // Validate required fields
            $validator = new Validator();
            $validation = $validator->validate($data, [
                'school_id' => 'required',
                'contact_type' => 'required',
                'first_name' => 'required|max:100',
                'last_name' => 'required|max:100',
                'position' => 'required|max:100',
                'email' => 'required|email|max:255',
                'phone' => 'max:20',
                'mobile' => 'max:20',
                'status' => 'required'
            ]);

            if (!$validation['valid']) {
                $errorMessages = [];
                foreach ($validation['errors'] as $field => $fieldErrors) {
                    if (is_array($fieldErrors)) {
                        $errorMessages = array_merge($errorMessages, $fieldErrors);
                    } else {
                        $errorMessages[] = $fieldErrors;
                    }
                }
                $this->flash('error', 'Please fix the following errors: ' . implode(', ', $errorMessages));
                return $this->redirect('/admin/contacts/create?school_id=' . ($data['school_id'] ?? ''));
            }

            // Check if school exists
            $schoolModel = new School();
            $school = $schoolModel->find($data['school_id']);
            if (!$school) {
                $this->flash('error', 'Selected school does not exist.');
                return $this->redirect('/admin/contacts/create');
            }

            // Handle existing user selection
            if (!empty($data['existing_user_id'])) {
                $userModel = new User();
                $selectedUser = $userModel->find($data['existing_user_id']);
                
                if (!$selectedUser) {
                    $this->flash('error', 'Selected user not found.');
                    return $this->redirect('/admin/contacts/create?school_id=' . ($data['school_id'] ?? ''));
                }
                
                // Override form data with user information
                $data['first_name'] = $selectedUser->first_name;
                $data['last_name'] = $selectedUser->last_name;
                $data['email'] = $selectedUser->email;
                $data['phone'] = $selectedUser->phone ?? $data['phone'] ?? '';
                $data['school_id'] = $selectedUser->school_id; // Use user's school
                
                // Set default position and contact type based on user role
                if (empty($data['position'])) {
                    $rolePositions = [
                        'super_admin' => 'System Administrator',
                        'competition_admin' => 'Competition Administrator',
                        'school_coordinator' => 'School Coordinator',
                        'team_coach' => 'Team Coach',
                        'judge' => 'Judge',
                        'participant' => 'Participant'
                    ];
                    $data['position'] = $rolePositions[$selectedUser->role] ?? ucwords(str_replace('_', ' ', $selectedUser->role));
                }
                
                if (empty($data['contact_type'])) {
                    $roleContactTypes = [
                        'super_admin' => 'administrative',
                        'competition_admin' => 'administrative',
                        'school_coordinator' => 'coordinator',
                        'team_coach' => 'coordinator',
                        'judge' => 'other',
                        'participant' => 'other'
                    ];
                    $data['contact_type'] = $roleContactTypes[$selectedUser->role] ?? 'other';
                }
            }

            // Handle primary contact logic
            if (isset($data['is_primary']) && $data['is_primary']) {
                // Remove primary flag from other contacts for this school
                $this->updatePrimaryContacts($data['school_id']);
            }

            // Create contact
            $contact = new Contact();
            $contact->school_id = $data['school_id'];
            $contact->contact_type = $data['contact_type'];
            $contact->title = $data['title'] ?? null;
            $contact->first_name = $data['first_name'];
            $contact->last_name = $data['last_name'];
            $contact->position = $data['position'];
            $contact->email = $data['email'];
            $contact->phone = $data['phone'] ?? null;
            $contact->mobile = $data['mobile'] ?? null;
            $contact->fax = $data['fax'] ?? null;
            $contact->address = $data['address'] ?? null;
            $contact->is_primary = isset($data['is_primary']) ? 1 : 0;
            $contact->is_emergency = isset($data['is_emergency']) ? 1 : 0;
            $contact->language_preference = $data['language_preference'] ?? Contact::LANG_ENGLISH;
            $contact->communication_preference = $data['communication_preference'] ?? Contact::COMM_EMAIL;
            $contact->status = $data['status'] ?? Contact::STATUS_ACTIVE;
            $contact->notes = $data['notes'] ?? null;

            if ($contact->save()) {
                $this->flash('success', "Contact {$contact->getFullName()} has been created successfully!");
                
                // Redirect based on context
                if ($request->get('redirect_to_school')) {
                    return $this->redirect("/admin/schools/{$data['school_id']}");
                } else {
                    return $this->redirect("/admin/contacts/{$contact->id}");
                }
            } else {
                $this->flash('error', 'Failed to create contact. Please try again.');
                return $this->redirect('/admin/contacts/create?school_id=' . $data['school_id']);
            }

        } catch (Exception $e) {
            error_log("Contact store error: " . $e->getMessage());
            $this->flash('error', 'An error occurred while creating the contact: ' . $e->getMessage());
            return $this->redirect('/admin/contacts/create');
        }
    }

    /**
     * Show edit contact form
     */
    public function edit($id)
    {
        try {
            $contactModel = new Contact();
            $contact = $contactModel->find($id);
            
            if (!$contact) {
                return $this->errorResponse('Contact not found', 404);
            }
            
            // Get school information
            $schoolModel = new School();
            $school = $schoolModel->find($contact->school_id);
            $schools = $schoolModel->all();
            
            // Set up breadcrumbs
            $breadcrumbs = [
                ['title' => 'Admin', 'url' => '/admin/dashboard', 'icon' => 'fas fa-shield-alt'],
                ['title' => 'School Contacts', 'url' => '/admin/contacts'],
                ['title' => $contact->getFullName(), 'url' => "/admin/contacts/{$id}"],
                ['title' => 'Edit', 'url' => "/admin/contacts/{$id}/edit"]
            ];

            return $this->view('admin/contacts/edit', [
                'title' => 'Edit Contact - GSCMS',
                'pageTitle' => 'Edit Contact: ' . $contact->getFullName(),
                'pageSubtitle' => $school ? $school->name : 'Unknown School',
                'breadcrumbs' => $breadcrumbs,
                'contact' => $contact,
                'school' => $school,
                'schools' => $schools,
                'contactTypes' => Contact::getAvailableTypes(),
                'statuses' => Contact::getAvailableStatuses(),
                'communicationPreferences' => Contact::getCommunicationPreferences(),
                'languagePreferences' => Contact::getLanguagePreferences()
            ]);

        } catch (Exception $e) {
            error_log("Contact edit form error: " . $e->getMessage());
            return $this->errorResponse('Failed to load edit form: ' . $e->getMessage());
        }
    }

    /**
     * Update contact
     */
    public function update($id)
    {
        try {
            $contactModel = new Contact();
            $contact = $contactModel->find($id);
            
            if (!$contact) {
                return $this->errorResponse('Contact not found', 404);
            }
            
            $request = new Request();
            $data = $request->all();
            
            // Validate required fields
            $validator = new Validator();
            $validation = $validator->validate($data, [
                'school_id' => 'required',
                'contact_type' => 'required',
                'first_name' => 'required|max:100',
                'last_name' => 'required|max:100',
                'position' => 'required|max:100',
                'email' => 'required|email|max:255',
                'phone' => 'max:20',
                'mobile' => 'max:20',
                'status' => 'required'
            ]);

            if (!$validation['valid']) {
                $errorMessages = [];
                foreach ($validation['errors'] as $field => $fieldErrors) {
                    if (is_array($fieldErrors)) {
                        $errorMessages = array_merge($errorMessages, $fieldErrors);
                    } else {
                        $errorMessages[] = $fieldErrors;
                    }
                }
                $this->flash('error', 'Please fix the following errors: ' . implode(', ', $errorMessages));
                return $this->redirect("/admin/contacts/{$id}/edit");
            }

            // Check if school exists
            $schoolModel = new School();
            $school = $schoolModel->find($data['school_id']);
            if (!$school) {
                $this->flash('error', 'Selected school does not exist.');
                return $this->redirect("/admin/contacts/{$id}/edit");
            }

            // Handle primary contact logic
            if (isset($data['is_primary']) && $data['is_primary'] && !$contact->isPrimary()) {
                // Remove primary flag from other contacts for this school
                $this->updatePrimaryContacts($data['school_id'], $id);
            }

            // Update contact
            $contact->school_id = $data['school_id'];
            $contact->contact_type = $data['contact_type'];
            $contact->title = $data['title'] ?? null;
            $contact->first_name = $data['first_name'];
            $contact->last_name = $data['last_name'];
            $contact->position = $data['position'];
            $contact->email = $data['email'];
            $contact->phone = $data['phone'] ?? null;
            $contact->mobile = $data['mobile'] ?? null;
            $contact->fax = $data['fax'] ?? null;
            $contact->address = $data['address'] ?? null;
            $contact->is_primary = isset($data['is_primary']) ? 1 : 0;
            $contact->is_emergency = isset($data['is_emergency']) ? 1 : 0;
            $contact->language_preference = $data['language_preference'] ?? $contact->language_preference;
            $contact->communication_preference = $data['communication_preference'] ?? $contact->communication_preference;
            $contact->status = $data['status'];
            $contact->notes = $data['notes'] ?? null;

            if ($contact->save()) {
                $this->flash('success', "Contact {$contact->getFullName()} has been updated successfully!");
                return $this->redirect("/admin/contacts/{$id}");
            } else {
                $this->flash('error', 'Failed to update contact. Please try again.');
                return $this->redirect("/admin/contacts/{$id}/edit");
            }

        } catch (Exception $e) {
            error_log("Contact update error: " . $e->getMessage());
            $this->flash('error', 'An error occurred while updating the contact: ' . $e->getMessage());
            return $this->redirect("/admin/contacts/{$id}/edit");
        }
    }

    /**
     * Delete contact
     */
    public function destroy($id)
    {
        try {
            $contactModel = new Contact();
            $contact = $contactModel->find($id);
            
            if (!$contact) {
                return $this->errorResponse('Contact not found', 404);
            }
            
            $contactName = $contact->getFullName();
            $schoolId = $contact->school_id;
            
            if ($contact->delete()) {
                $this->flash('success', "Contact {$contactName} has been deleted successfully!");
            } else {
                $this->flash('error', 'Failed to delete contact. Please try again.');
            }
            
            // Check if we should redirect to school page or contacts list
            $request = new Request();
            if ($request->get('redirect_to_school')) {
                return $this->redirect("/admin/schools/{$schoolId}");
            }
            
            return $this->redirect('/admin/contacts');

        } catch (Exception $e) {
            error_log("Contact delete error: " . $e->getMessage());
            $this->flash('error', 'An error occurred while deleting the contact: ' . $e->getMessage());
            return $this->redirect('/admin/contacts');
        }
    }

    /**
     * Get contacts for a specific school (AJAX endpoint)
     */
    public function getBySchool($schoolId)
    {
        try {
            $contactModel = new Contact();
            $contacts = $contactModel::getBySchool($schoolId);
            
            return $this->jsonResponse([
                'success' => true,
                'contacts' => $contacts->toArray()
            ]);

        } catch (Exception $e) {
            error_log("Get contacts by school error: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to load contacts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update status (AJAX endpoint)
     */
    public function updateStatus()
    {
        try {
            $request = new Request();
            $data = $request->all();
            
            if (!isset($data['contact_id']) || !isset($data['status'])) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Contact ID and status are required'
                ], 400);
            }
            
            $contactModel = new Contact();
            $contact = $contactModel->find($data['contact_id']);
            if (!$contact) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Contact not found'
                ], 404);
            }
            
            $contact->status = $data['status'];
            
            if ($contact->save()) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Contact status updated successfully'
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Failed to update contact status'
                ], 500);
            }

        } catch (Exception $e) {
            error_log("Contact status update error: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to update primary contacts
     */
    private function updatePrimaryContacts($schoolId, $excludeId = null)
    {
        try {
            $contact = new Contact();
            $query = $contact->db->table('contacts')
                ->where('school_id', $schoolId)
                ->where('is_primary', 1);
                
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            
            $query->update(['is_primary' => 0]);
            
        } catch (Exception $e) {
            error_log("Update primary contacts error: " . $e->getMessage());
        }
    }
}