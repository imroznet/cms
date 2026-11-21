<?php
if (!defined('INCLUDED')) {
    die('Direct access not permitted');
}

$documents = getCompanyDocuments($conn, true);
?>

<section class="page-header">
    <div class="container">
        <h1>Documents</h1>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if ($documents === []): ?>
            <p>No documents are published yet.</p>
        <?php else: ?>
            <?php include __DIR__ . '/../includes/company-documents.php'; ?>
        <?php endif; ?>
    </div>
</section>
