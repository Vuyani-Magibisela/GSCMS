<!-- Footer -->
<footer>
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h3>GDE SciBOTICS 2025</h3>
                <p>Empowering the next generation of innovators through robotics education and competition excellence.</p>
                <div class="footer-logo">
                    <span class="footer-brand">🤖 SciBOTICS</span>
                </div>
            </div>
            
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="#home">Home</a></li>
                    <li><a href="#categories">Categories</a></li>
                    <li><a href="#phases">Competition Phases</a></li>
                    <li><a href="<?= htmlspecialchars($loginUrl ?? '/auth/login') ?>">Login</a></li>
                    <li><a href="<?= htmlspecialchars($registerUrl ?? '/auth/register') ?>">Register</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Contact Info</h3>
                <div class="contact-info">
                    <p><i class="fas fa-envelope"></i> info@gdescibiotics.co.za</p>
                    <p><i class="fas fa-phone"></i> +27 11 355 0000</p>
                    <p><i class="fas fa-map-marker-alt"></i> Gauteng Department of Education</p>
                    <p><i class="fas fa-calendar"></i> Competition Date: September 27, 2025</p>
                </div>
            </div>
            
            <div class="footer-section">
                <h3>Partners & Sponsors</h3>
                <div class="partners">
                    <p><strong>Gauteng Department of Education</strong></p>
                    <p><strong>Sci-Bono Discovery Centre</strong></p>
                </div>
                
                <h4 style="margin-top: 1.5rem;">Follow Us</h4>
                <div class="social-links">
                    <a href="#" title="Facebook" class="social-link facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" title="Twitter" class="social-link twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" title="Instagram" class="social-link instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" title="LinkedIn" class="social-link linkedin">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                    <a href="#" title="YouTube" class="social-link youtube">
                        <i class="fab fa-youtube"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="footer-bottom-content">
                <p>&copy; <?= date('Y') ?> Sci-Bono Discovery Center. All rights reserved.</p>
                <div class="footer-bottom-links">
                    <a href="#privacy">Privacy Policy</a>
                    <span class="separator">|</span>
                    <a href="#terms">Terms of Service</a>
                    <span class="separator">|</span>
                    <a href="#accessibility">Accessibility</a>
                </div>
            </div>
        </div>
    </div>
</footer>