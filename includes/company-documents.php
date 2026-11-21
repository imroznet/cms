<?php
if (!defined('INCLUDED')) {
    exit('Direct access not permitted');
}

$documents = isset($documents) && is_array($documents) ? $documents : [];
?>
<div class="company-documents">
    <?php foreach ($documents as $document): ?>
        <?php if (!is_array($document)) { continue; } ?>
        <article class="company-document">
            <h3><?php echo htmlspecialchars((string) ($document['title'] ?? '')); ?></h3>
            <p><strong>Type:</strong> <?php echo htmlspecialchars(documentTypeLabel((string) ($document['document_type'] ?? ''))); ?></p>
            <?php if (trim((string) ($document['document_number'] ?? '')) !== ''): ?>
                <p><strong>Number:</strong> <?php echo htmlspecialchars((string) $document['document_number']); ?></p>
            <?php endif; ?>
            <?php if (trim((string) ($document['issuing_authority'] ?? '')) !== ''): ?>
                <p><strong>Issuing authority:</strong> <?php echo htmlspecialchars((string) $document['issuing_authority']); ?></p>
            <?php endif; ?>
            <?php $issueDate = formatStoredDate($document['issue_date'] ?? null); ?>
            <?php if ($issueDate !== ''): ?>
                <p><strong>Issue date:</strong> <?php echo htmlspecialchars($issueDate); ?></p>
            <?php endif; ?>
            <?php $expiryDate = formatStoredDate($document['expiry_date'] ?? null); ?>
            <?php if ($expiryDate !== ''): ?>
                <p><strong>Expiry date:</strong> <?php echo htmlspecialchars($expiryDate); ?></p>
            <?php endif; ?>
            <?php $certificate = storedUploadName($document['certificate_file'] ?? null); ?>
            <?php if ($certificate !== null): ?>
                <p><a href="<?php echo htmlspecialchars(publicAsset('assets/documents/' . $certificate)); ?>" target="_blank" rel="noopener">View / Download</a></p>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</div>
