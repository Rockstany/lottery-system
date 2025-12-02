<?php
/**
 * Group Admin Navigation Menu
 * Include this file in all group-admin pages
 */

// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<style>
    .main-nav {
        background: var(--white);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        position: sticky;
        top: 0;
        z-index: 1000;
        margin-bottom: var(--spacing-xl);
    }
    .main-nav .container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0;
    }

    /* Hamburger Button */
    .nav-toggle {
        display: none;
        background: none;
        border: none;
        font-size: 24px;
        padding: var(--spacing-md);
        cursor: pointer;
        color: var(--gray-700);
    }

    .nav-links {
        display: flex;
        gap: 0;
        margin: 0;
        padding: 0;
        list-style: none;
        flex: 1;
    }
    .nav-link {
        padding: var(--spacing-md) var(--spacing-lg);
        color: var(--gray-700);
        text-decoration: none;
        transition: all var(--transition-base);
        border-bottom: 3px solid transparent;
        font-weight: 500;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: var(--spacing-xs);
    }
    .nav-link:hover {
        color: var(--primary-color);
        background: var(--gray-50);
        text-decoration: none;
    }
    .nav-link.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
        background: rgba(124, 58, 237, 0.05);
    }
    .nav-right {
        display: flex;
        gap: var(--spacing-md);
        align-items: center;
        padding: var(--spacing-md);
    }

    /* Mobile Styles */
    @media (max-width: 768px) {
        .nav-toggle {
            display: block;
        }

        .main-nav .container {
            flex-wrap: wrap;
        }

        .nav-links {
            display: none;
            flex-direction: column;
            width: 100%;
            order: 3;
        }

        .nav-links.active {
            display: flex;
        }

        .nav-link {
            border-bottom: none;
            border-left: 3px solid transparent;
            padding: var(--spacing-md) var(--spacing-lg);
        }

        .nav-link.active {
            border-left-color: var(--primary-color);
            border-bottom-color: transparent;
        }

        .nav-right {
            order: 2;
            padding: var(--spacing-sm) var(--spacing-md);
        }

        .nav-right .btn {
            padding: 8px 16px;
            font-size: 14px;
        }
    }
</style>

<nav class="main-nav">
    <div class="container">
        <!-- Hamburger Button (Mobile Only) -->
        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
            ☰
        </button>

        <ul class="nav-links" id="navLinks">
            <li>
                <a href="/public/group-admin/dashboard.php"
                   class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                    <span>🏠</span><span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="/public/group-admin/transactions.php"
                   class="nav-link <?php echo in_array($currentPage, ['transactions.php', 'transaction-create.php', 'transaction-upload.php', 'transaction-members.php', 'transaction-payment-record.php']) ? 'active' : ''; ?>">
                    <span>💰</span><span>Transactions</span>
                </a>
            </li>
            <li>
                <a href="/public/group-admin/lottery.php"
                   class="nav-link <?php echo in_array($currentPage, ['lottery.php', 'lottery-create.php', 'lottery-edit.php', 'lottery-books.php', 'lottery-books-generate.php', 'lottery-book-assign.php', 'lottery-distribution-setup.php', 'lottery-payments.php', 'lottery-payment-collect.php', 'lottery-reports.php', 'lottery-winners.php', 'lottery-commission-setup.php']) ? 'active' : ''; ?>">
                    <span>🎫</span><span>Lottery</span>
                </a>
            </li>
            <li>
                <a href="/public/group-admin/change-password.php"
                   class="nav-link <?php echo $currentPage === 'change-password.php' ? 'active' : ''; ?>">
                    <span>🔐</span><span>Password</span>
                </a>
            </li>
        </ul>
        <div class="nav-right">
            <a href="/public/logout.php" class="btn btn-sm btn-danger">
                Logout
            </a>
        </div>
    </div>
</nav>

<script>
// Hamburger menu toggle
(function() {
    const navToggle = document.getElementById('navToggle');
    const navLinks = document.getElementById('navLinks');

    if (navToggle && navLinks) {
        navToggle.addEventListener('click', function() {
            navLinks.classList.toggle('active');
            // Change icon
            this.textContent = navLinks.classList.contains('active') ? '✕' : '☰';
        });

        // Close menu when clicking on a link (mobile)
        const links = navLinks.querySelectorAll('.nav-link');
        links.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    navLinks.classList.remove('active');
                    navToggle.textContent = '☰';
                }
            });
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 768) {
                if (!navToggle.contains(event.target) && !navLinks.contains(event.target)) {
                    navLinks.classList.remove('active');
                    navToggle.textContent = '☰';
                }
            }
        });
    }
})();
</script>
