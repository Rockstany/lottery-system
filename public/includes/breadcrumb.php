<?php
/**
 * Breadcrumb Navigation Component
 * GetToKnow SAAS Platform v4.0
 *
 * Usage:
 * $breadcrumbs = [
 *     ['label' => 'Dashboard', 'url' => '/public/group-admin/dashboard.php'],
 *     ['label' => 'Lottery System', 'url' => '/public/group-admin/lottery.php'],
 *     ['label' => 'Current Page', 'url' => null]
 * ];
 * include __DIR__ . '/../includes/breadcrumb.php';
 */

if (!isset($breadcrumbs) || empty($breadcrumbs)) {
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => null]
    ];
}
?>

<style>
.breadcrumb-container {
    background: var(--gray-100, #f8f9fa);
    padding: var(--spacing-md, 16px) 0;
    margin-bottom: var(--spacing-lg, 24px);
    border-bottom: 1px solid var(--gray-200, #e9ecef);
}

.breadcrumb {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--spacing-sm, 8px);
    font-size: 14px;
    color: var(--gray-700, #495057);
}

.breadcrumb a {
    color: var(--primary-color, #667eea);
    text-decoration: none;
    transition: color 0.2s ease;
}

.breadcrumb a:hover {
    color: var(--primary-dark, #5568d3);
    text-decoration: underline;
}

.breadcrumb .separator {
    color: var(--gray-400, #ced4da);
    user-select: none;
}

.breadcrumb .current {
    color: var(--gray-900, #212529);
    font-weight: 500;
}

.breadcrumb .home-icon {
    font-size: 16px;
    vertical-align: middle;
}

@media (max-width: 768px) {
    .breadcrumb {
        font-size: 13px;
    }

    /* Collapse middle items on mobile */
    .breadcrumb-collapse {
        display: none;
    }

    .breadcrumb .breadcrumb-ellipsis {
        display: inline;
    }
}

@media (min-width: 769px) {
    .breadcrumb .breadcrumb-ellipsis {
        display: none;
    }
}
</style>

<div class="breadcrumb-container no-print">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <?php
            $total = count($breadcrumbs);
            $isMobile = (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Mobile|Android|iPhone/i', $_SERVER['HTTP_USER_AGENT']));

            foreach ($breadcrumbs as $index => $crumb):
                $isLast = ($index === $total - 1);
                $isFirst = ($index === 0);

                // Show ellipsis on mobile for middle items
                if ($isMobile && $total > 3 && $index > 0 && $index < $total - 1):
                    if ($index === 1):
            ?>
                <span class="breadcrumb-ellipsis">...</span>
                <span class="separator">›</span>
                    <?php endif; ?>
                    <span class="breadcrumb-collapse" style="display: none;">
            <?php endif; ?>

                <?php if ($isFirst): ?>
                    <!-- First item (Dashboard) with home icon -->
                    <?php if ($crumb['url']): ?>
                        <a href="<?php echo htmlspecialchars($crumb['url']); ?>" title="Go to <?php echo htmlspecialchars($crumb['label']); ?>">
                            <span class="home-icon">🏠</span> <?php echo htmlspecialchars($crumb['label']); ?>
                        </a>
                    <?php else: ?>
                        <span class="current">
                            <span class="home-icon">🏠</span> <?php echo htmlspecialchars($crumb['label']); ?>
                        </span>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Other items -->
                    <?php if ($crumb['url'] && !$isLast): ?>
                        <a href="<?php echo htmlspecialchars($crumb['url']); ?>" title="Go to <?php echo htmlspecialchars($crumb['label']); ?>">
                            <?php echo htmlspecialchars($crumb['label']); ?>
                        </a>
                    <?php else: ?>
                        <span class="current"><?php echo htmlspecialchars($crumb['label']); ?></span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($isMobile && $total > 3 && $index > 0 && $index < $total - 1): ?>
                    </span>
                <?php endif; ?>

                <!-- Add separator if not last item -->
                <?php if (!$isLast): ?>
                    <span class="separator" aria-hidden="true">›</span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </div>
</div>

<script>
// Add click tracking for breadcrumbs (optional analytics)
document.querySelectorAll('.breadcrumb a').forEach(link => {
    link.addEventListener('click', function(e) {
        // Optional: Track breadcrumb navigation
        const label = this.textContent.trim();
        console.log('Breadcrumb navigation:', label);
    });
});
</script>
