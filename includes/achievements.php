<?php
if (!defined('INCLUDED')) {
    exit('Direct access not permitted');
}

$achievements = isset($achievements) && is_array($achievements) ? $achievements : [];
?>
<div class="achievements-list">
    <?php foreach ($achievements as $achievement): ?>
        <?php if (!is_array($achievement)) { continue; } ?>
        <article class="achievement">
            <?php $photo = storedUploadName($achievement['photo'] ?? null); ?>
            <?php if ($photo !== null): ?>
                <img src="<?php echo htmlspecialchars(publicAsset('assets/images/uploads/' . $photo)); ?>" alt="<?php echo htmlspecialchars((string) ($achievement['title'] ?? '')); ?>">
            <?php endif; ?>
            <h3><?php echo htmlspecialchars((string) ($achievement['title'] ?? '')); ?></h3>
            <?php if (trim((string) ($achievement['year'] ?? '')) !== ''): ?>
                <p><strong>Year:</strong> <?php echo (int) $achievement['year']; ?></p>
            <?php endif; ?>
            <?php if (trim((string) ($achievement['description'] ?? '')) !== ''): ?>
                <p><?php echo nl2br(htmlspecialchars((string) $achievement['description'])); ?></p>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</div>
