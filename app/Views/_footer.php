<?php 

?>

<!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>GDE SciBOTICS 2025</h3>
                    <p>Empowering the next generation of innovators through robotics education and competition.</p>
                </div>
                
                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <p>📧 info@gdescibiotics.co.za</p>
                    <p>📞 +27 11 355 0000</p>
                    <p>📍 Gauteng Department of Education</p>
                </div>
                
                <div class="footer-section">
                    <h3>Partners</h3>
                    <p>Gauteng Department of Education</p>
                    <p>Sci-Bono Discovery Centre</p>
                </div>
                
                <div class="footer-section">
                    <h3>Follow Us</h3>
                    <div class="social-links">
                        <a href="https://facebook.com/gdeeducation" target="_blank" title="Facebook">📘</a>
                        <a href="https://twitter.com/gdeeducation" target="_blank" title="Twitter">🐦</a>
                        <a href="https://instagram.com/gdeeducation" target="_blank" title="Instagram">📷</a>
                        <a href="https://linkedin.com/company/gdeeducation" target="_blank" title="LinkedIn">💼</a>
                        <a href="https://youtube.com/gdeeducation" target="_blank" title="YouTube">📺</a>
                    </div>
                </div>
            </div>
            
            <div style="border-top: 1px solid #555; padding-top: 2rem; margin-top: 2rem;">
                <p>&copy; 2025 Gauteng Department of Education. All rights reserved. | 
                <a href="<?php echo baseUrl('privacy'); ?>">Privacy Policy</a> | 
                <a href="<?php echo baseUrl('terms'); ?>">Terms of Service</a></p>
            </div>
        </div>
    </footer>

    <?php 
    // Helper function to load script if it exists
    function loadScript($scriptName, $currentPage = null) {
        $documentRoot = $_SERVER['DOCUMENT_ROOT'];
        $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
        $fullPath = $documentRoot . $scriptPath . '/js/' . $scriptName;
        
        if (file_exists($fullPath)) {
            echo '<script src="' . baseUrl('js/' . $scriptName) . '"></script>' . "\n    ";
            return true;
        }
        return false;
    }
    
    // Get current page
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    
    if ($scriptPath !== '/' && strpos($uri, $scriptPath) === 0) {
        $uri = substr($uri, strlen($scriptPath));
    }
    
    $uri = trim($uri, '/');
    $currentPage = explode('/', $uri)[0] ?: 'home';
    
    // Load page-specific JavaScript files
    $pageScripts = [
        'home' => 'home_script.js',
        '' => 'home_script.js', // Root path also loads home script
        'admin' => 'admin_main.js',
        // 'blog' => 'blog.js',
        // 'contact' => 'contact.js',
        // 'register' => 'register.js',
        'categories' => 'categories.js',
        'phases' => 'phases.js'
    ];
    
    // Load current page script
    if (isset($pageScripts[$currentPage])) {
        loadScript($pageScripts[$currentPage]);
    }
    
    // Always try to load main.js (common functionality) last
    loadScript('main.js');
    ?>
    </body>
</html>