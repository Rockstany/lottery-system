<!-- Footer Component -->
<footer class="app-footer">
    <div class="container">
        <div class="footer-content">
            <p class="footer-text">
                Need help or have questions? Contact us at
                <a href="tel:+917899015086" class="footer-link">
                    <svg class="footer-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                    </svg>
                    7899015086
                </a>
            </p>
            <p class="footer-copyright">
                &copy; <?php echo date('Y'); ?> <?php echo APP_NAME ?? 'Lottery Management System'; ?>. All rights reserved.
            </p>
        </div>
    </div>
</footer>

<style>
.app-footer {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: #e2e8f0;
    padding: 2rem 0;
    margin-top: 4rem;
    border-top: 3px solid #3b82f6;
}

.footer-content {
    text-align: center;
}

.footer-text {
    font-size: 1rem;
    margin: 0 0 0.75rem 0;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.footer-link {
    color: #60a5fa;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    border-radius: 0.375rem;
    transition: all 0.3s ease;
}

.footer-link:hover {
    color: #93c5fd;
    background: rgba(59, 130, 246, 0.1);
    transform: translateY(-1px);
}

.footer-icon {
    width: 1.125rem;
    height: 1.125rem;
}

.footer-copyright {
    font-size: 0.875rem;
    color: #94a3b8;
    margin: 0;
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .app-footer {
        padding: 1.5rem 0;
        margin-top: 2.5rem;
    }

    .footer-text {
        font-size: 0.9375rem;
        flex-direction: column;
        gap: 0.375rem;
    }

    .footer-link {
        padding: 0.5rem 1rem;
    }

    .footer-copyright {
        font-size: 0.8125rem;
        margin-top: 0.5rem;
    }
}

@media (max-width: 480px) {
    .app-footer {
        padding: 1.25rem 0;
        margin-top: 2rem;
    }

    .footer-text {
        font-size: 0.875rem;
    }

    .footer-link {
        font-size: 1rem;
    }
}
</style>
