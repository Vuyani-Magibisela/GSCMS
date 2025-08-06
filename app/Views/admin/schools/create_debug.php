<?php 
$layout = 'layouts/admin';
$pageCSS = ['/css/school-form.css'];
ob_start(); 
?>

<div style="background: red; padding: 20px; color: white; font-size: 24px;">
    TEST - FORM SHOULD APPEAR HERE
</div>

<div class="school-registration-container">
    <h1>School Registration Form</h1>
    <p>This is a test to see if the form container is rendering.</p>
    
    <form method="POST" action="/admin/schools">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? 'NO_TOKEN' ?>">
        
        <div style="background: blue; color: white; padding: 10px; margin: 10px 0;">
            Form content should be here
        </div>
        
        <label for="name">School Name:</label>
        <input type="text" id="name" name="name" required>
        
        <button type="submit">Submit</button>
    </form>
</div>

<?php 
$content = ob_get_clean(); 
include VIEW_PATH . '/' . $layout . '.php'; 
?>