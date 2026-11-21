<?php
require_once __DIR__ . '/../includes/security.php';
require_once '../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if (!in_array('manage_settings', $_SESSION['permissions'] ?? [], true)) {
    header('Location: dashboard.php');
    exit;
}

define('INCLUDED', true);
$currentPage = 'achievements';
$pageTitle = 'Achievements';
$action = isset($_GET['action']) ? (string) $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$message = '';
$messageType = '';
$uploadDir = dirname(__DIR__) . '/assets/images/uploads';

if (!contentTableExists($conn, 'achievements')) {
    $message = 'Import deploy/upgrade-achievements.sql before managing achievements.';
    $messageType = 'error';
    $action = 'missing';
}

if ($action === 'delete' && $id > 0) {
    $stmt = $conn->prepare('SELECT photo FROM achievements WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $deleted = $conn->prepare('DELETE FROM achievements WHERE id = ?')->execute([$id]);

    if ($deleted) {
        removeStoredFile($uploadDir, $row['photo'] ?? null);
        $message = 'Achievement deleted.';
        $messageType = 'success';
    } else {
        $message = 'The achievement could not be deleted.';
        $messageType = 'error';
    }

    $action = 'list';
}

if ($action === 'toggle' && $id > 0) {
    $conn->prepare('UPDATE achievements SET is_active = NOT is_active WHERE id = ?')->execute([$id]);
    $message = 'Status updated.';
    $messageType = 'success';
    $action = 'list';
}

if ($action !== 'missing' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['move']) && empty($_POST['_confirm_action'])) {
    moveDisplayOrder($conn, 'achievements', (int) ($_POST['id'] ?? 0), (string) $_POST['move']);
    $message = 'Order updated.';
    $messageType = 'success';
    $action = 'list';
}

$editAchievement = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $conn->prepare('SELECT * FROM achievements WHERE id = ?');
    $stmt->execute([$id]);
    $editAchievement = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$editAchievement) {
        $message = 'Achievement not found.';
        $messageType = 'error';
        $action = 'list';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['_confirm_action']) && !isset($_POST['move']) && $action !== 'missing') {
    $id = (int) ($_POST['id'] ?? 0);
    $title = trim((string) ($_POST['title'] ?? ''));
    $year = trim((string) ($_POST['year'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $displayOrder = (int) ($_POST['display_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $errors = [];

    if ($title === '') {
        $errors[] = 'Title is required';
    }

    if ($year !== '' && (!preg_match('/^\d{4}$/', $year) || (int) $year < 1900 || (int) $year > 2100)) {
        $errors[] = 'Year must be a four-digit year between 1900 and 2100';
    }

    $photo = $id > 0 && is_array($editAchievement) ? ($editAchievement['photo'] ?? null) : null;

    if (!empty($_FILES['photo']['name'])) {
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            $errors[] = 'The photo folder could not be created';
        }

        $base = (string) $_FILES['photo']['name'];
        $base = preg_replace('/[^A-Za-z0-9._-]+/', '_', $base) ?? '';
        $filename = storedUploadName(time() . '_' . $base);
        $extension = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));

        if ($filename === null || !in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            $errors[] = 'Photo must be a JPG or PNG';
        } elseif ((int) $_FILES['photo']['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Photo must be under 5 MB';
        } elseif ($filename !== null && empty($errors) && move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . '/' . $filename)) {
            removeStoredFile($uploadDir, $photo);
            $photo = $filename;
        } elseif (empty($errors)) {
            $errors[] = 'The photo could not be saved';
        }
    }

    if ($errors === []) {
        try {
            $values = [
                $title,
                $year === '' ? null : (int) $year,
                $description === '' ? null : $description,
                storedUploadName(is_string($photo) ? $photo : null),
                $displayOrder,
                $isActive,
            ];

            if ($id > 0) {
                $values[] = $id;
                $saved = $conn->prepare('UPDATE achievements SET title = ?, year = ?, description = ?, photo = ?, display_order = ?, is_active = ? WHERE id = ?')->execute($values);
                $message = $saved ? 'Achievement updated.' : 'The achievement could not be updated.';
            } else {
                $saved = $conn->prepare('INSERT INTO achievements (title, year, description, photo, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?)')->execute($values);
                $message = $saved ? 'Achievement added.' : 'The achievement could not be added.';
            }

            $messageType = $saved ? 'success' : 'error';
            if ($saved) {
                $action = 'list';
            }
        } catch (PDOException $e) {
            $message = 'The operation failed. Please contact your administrator.';
            $messageType = 'error';
        }
    } else {
        $message = 'Please correct the following errors: <ul><li>' . implode('</li><li>', array_map(static fn (string $error): string => htmlspecialchars($error, ENT_QUOTES, 'UTF-8'), $errors)) . '</li></ul>';
        $messageType = 'error';
    }
}

$achievements = [];
if ($action === 'list') {
    $achievements = getAchievements($conn, false);
}

include 'includes/header.php';
?>

<div class="main-content">
    <div class="top-nav">
        <button class="menu-toggle" id="menu-toggle"><i class="fas fa-bars"></i></button>
        <h1>Achievements</h1>
        <?php if ($action === 'list'): ?>
            <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Achievement</a>
        <?php else: ?>
            <div></div>
        <?php endif; ?>
    </div>

    <div class="container">
        <?php if ($message !== ''): ?>
            <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($action === 'missing'): ?>
            <div class="panel"><p>Import <code>deploy/upgrade-achievements.sql</code> once, then reload this page.</p></div>
        <?php elseif ($action === 'add' || $action === 'edit'): ?>
            <div class="panel">
                <h3><?php echo $action === 'add' ? 'Add' : 'Edit'; ?> Achievement</h3>
                <form action="" method="post" enctype="multipart/form-data">
                    <?php if ($action === 'edit' && is_array($editAchievement)): ?>
                        <input type="hidden" name="id" value="<?php echo (int) $editAchievement['id']; ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input type="text" id="title" name="title" maxlength="160" value="<?php echo $action === 'edit' && is_array($editAchievement) ? htmlspecialchars((string) $editAchievement['title']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="year">Year</label>
                        <input type="number" id="year" name="year" min="1900" max="2100" value="<?php echo $action === 'edit' && is_array($editAchievement) && $editAchievement['year'] !== null ? (int) $editAchievement['year'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description"><?php echo $action === 'edit' && is_array($editAchievement) ? htmlspecialchars((string) ($editAchievement['description'] ?? '')) : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="photo">Photo</label>
                        <input type="file" id="photo" name="photo" accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                        <p class="note">JPG or PNG. Maximum 5 MB. Leave this empty to keep the current photo. Only achievements with a photo appear in the homepage slider.</p>
                        <?php if ($action === 'edit' && is_array($editAchievement) && storedUploadName($editAchievement['photo'] ?? null) !== null): ?>
                            <p><img src="<?php echo htmlspecialchars(publicAsset('assets/images/uploads/' . $editAchievement['photo'])); ?>" alt="Current photo" style="max-width: 200px; max-height: 150px;"></p>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="display_order">Display order</label>
                        <input type="number" id="display_order" name="display_order" min="0" value="<?php echo $action === 'edit' && is_array($editAchievement) ? (int) $editAchievement['display_order'] : 0; ?>">
                        <p class="note">Lower numbers appear first. The list also has up and down controls.</p>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-wrapper" for="is_active">
                            <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo ($action === 'edit' && is_array($editAchievement) ? (int) $editAchievement['is_active'] === 1 : true) ? 'checked' : ''; ?>>
                            Active
                        </label>
                    </div>
                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary"><?php echo $action === 'add' ? 'Add' : 'Update'; ?> Achievement</button>
                        <a href="?action=list" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="panel">
                <h3>Achievements</h3>
                <?php if ($achievements === []): ?>
                    <p>No achievements yet. <a href="?action=add">Add the first one</a>.</p>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Photo</th>
                                    <th>Title</th>
                                    <th>Year</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($achievements as $achievement): ?>
                                    <tr>
                                        <td>
                                            <?php echo (int) $achievement['display_order']; ?>
                                            <form method="post" style="display:inline">
                                                <input type="hidden" name="id" value="<?php echo (int) $achievement['id']; ?>">
                                                <input type="hidden" name="move" value="up">
                                                <button type="submit" title="Move up">Up</button>
                                            </form>
                                            <form method="post" style="display:inline">
                                                <input type="hidden" name="id" value="<?php echo (int) $achievement['id']; ?>">
                                                <input type="hidden" name="move" value="down">
                                                <button type="submit" title="Move down">Down</button>
                                            </form>
                                        </td>
                                        <td>
                                            <?php if (storedUploadName($achievement['photo'] ?? null) !== null): ?>
                                                <img src="<?php echo htmlspecialchars(publicAsset('assets/images/uploads/' . $achievement['photo'])); ?>" alt="" style="max-width: 60px; max-height: 45px;">
                                            <?php else: ?>
                                                &mdash;
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars((string) $achievement['title']); ?></td>
                                        <td><?php echo $achievement['year'] !== null ? (int) $achievement['year'] : ''; ?></td>
                                        <td><?php echo (int) $achievement['is_active'] === 1 ? 'Active' : 'Inactive'; ?></td>
                                        <td class="action-buttons">
                                            <a href="?action=edit&id=<?php echo (int) $achievement['id']; ?>" title="Edit"><i class="fas fa-edit" style="color:#007bff;"></i></a>
                                            <a href="?action=toggle&id=<?php echo (int) $achievement['id']; ?>" title="<?php echo (int) $achievement['is_active'] === 1 ? 'Deactivate' : 'Activate'; ?>"><i class="fas <?php echo (int) $achievement['is_active'] === 1 ? 'fa-toggle-on' : 'fa-toggle-off'; ?>" style="color:<?php echo (int) $achievement['is_active'] === 1 ? '#28a745' : '#6c757d'; ?>;"></i></a>
                                            <a href="?action=delete&id=<?php echo (int) $achievement['id']; ?>" title="Delete"><i class="fas fa-trash-alt" style="color:#dc3545;"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
