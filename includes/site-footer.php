<?php

declare(strict_types=1);

/**
 * Shared DWU footer markup — include on every page via renderSiteFooter() or require.
 */
?>
<footer class="site-footer" role="contentinfo">
    <div class="footer-grid">
        <div class="footer-col">
            <h3>About</h3>
            <p>A national and leading University in Papua New Guinea. Open to all, serving society through our quality of research, teaching, learning and community service in a Christian environment.</p>
        </div>

        <div class="footer-col">
            <h3>Contact</h3>
            <div class="footer-contact-line">
                <span>DWU Madang Campus |</span>
                <svg class="footer-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M4 4h16v16H4z"></path><path d="M4 9h16"></path><path d="M9 4v5"></path>
                </svg>
                <span>PO Box 483,</span>
            </div>
            <div class="footer-contact-line">
                <span>Madang, Papua New Guinea</span>
                <svg class="footer-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"></path>
                </svg>
                <span>4222937 Fax: 4222812</span>
            </div>
            <div class="footer-contact-line">
                <svg class="footer-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <path d="M22 6l-10 7L2 6"></path>
                </svg>
                <a href="mailto:info@dwu.ac.pg">info@dwu.ac.pg</a>
                <span>|</span>
                <svg class="footer-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"></path>
                </svg>
                <a href="https://www.dwu.ac.pg" target="_blank" rel="noopener noreferrer">www.dwu.ac.pg</a>
            </div>
            <div class="footer-contact-line">
                <span>|</span>
                <a href="https://intranet.dwu.ac.pg" target="_blank" rel="noopener noreferrer">intranet.dwu.ac.pg</a>
            </div>
        </div>

        <div class="footer-col">
            <h3>Support</h3>
            <p>
                Copyright &copy; <?= date('Y') ?> Divine Word University. All Rights Reserved. Maintained by<br>
                <a href="https://www.dwu.ac.pg" target="_blank" rel="noopener noreferrer">Centre for Learning and Teaching</a>
            </p>
        </div>
    </div>
</footer>
