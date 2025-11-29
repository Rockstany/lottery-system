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
    }
    .nav-link:hover {
        color: var(--primary-color);
        background: var(--gray-50);
        text-decoration: none;
    }
    .nav-link.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
        background: var(--primary-color);
        background-opacity: 0.05;
    }
    .nav-right {
        display: flex;
        gap: var(--spacing-md);
        align-items: center;
        padding: var(--spacing-md);
    }
    @media (max-width: 768px) {
        .nav-links {
            flex-direction: column;
            width: 100%;
        }
        .main-nav .container {
            flex-direction: column;
        }
        .nav-right {
            width: 100%;
            justify-content: center;
            border-top: 1px solid var(--gray-200);
        }
    }
</style>

<nav class="main-nav">
    <div class="container">
        <ul class="nav-links">
            <li>
                <a href="/public/group-admin/dashboard.php"
                   class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                    🏠 Dashboard
                </a>
            </li>
            <li>
                <a href="/public/group-admin/transactions.php"
                   class="nav-link <?php echo in_array($currentPage, ['transactions.php', 'transaction-create.php', 'transaction-upload.php', 'transaction-members.php', 'transaction-payment-record.php']) ? 'active' : ''; ?>">
                    💰 Transactions
                </a>
            </li>
            <li>
                <a href="/public/group-admin/lottery.php"
                   class="nav-link <?php echo in_array($currentPage, ['lottery.php', 'lottery-create.php', 'lottery-edit.php', 'lottery-books.php', 'lottery-books-generate.php', 'lottery-book-assign.php', 'lottery-distribution-setup.php', 'lottery-payments.php', 'lottery-payment-collect.php', 'lottery-reports.php']) ? 'active' : ''; ?>">
                    🎫 Lottery
                </a>
            </li>
            <li>
                <a href="/public/group-admin/change-password.php"
                   class="nav-link <?php echo $currentPage === 'change-password.php' ? 'active' : ''; ?>">
                    🔐 Password
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
