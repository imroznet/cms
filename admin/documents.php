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
$currentPage = 'documents';
$pageTitle = 'Company Documents';
$action = isset($_GET['action']) ? (string) $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$message = '';
$messageType = '';
$uploadDir = dirname(__DIR__) . '/assets/documents';
$types = [
    'incorporation' => 'Incorporation',
    'trade_license' => 'Trade License',
    'bin' => 'BIN',
    'tin' => 'TIN',
    'other' => 'Other',
];

if (!contentTableExists($conn, 'company_documents')) {
    $message = 'Import deploy/upgrade-company-documents.sql before managing documents.';
    $messageType = 'error';
    $action = 'missing';
}

if ($action === 'delete' && $id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare('SELECT certificate_file FROM company_documents WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $deleted = $conn->prepare('DELETE FROM company_documents WHERE id = ?')->execute([$id]);

    if ($deleted) {
        removeStoredFile($uploadDir, $row['certificate_file'] ?? null);
        $message = 'Document deleted.';
        $messageType = 'success';
    } else {
        $message = 'The document could not be deleted.';
        $messageType = 'error';
    }

    $action = 'list';
}

if ($action === 'toggle' && $id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->prepare('UPDATE company_documents SET is_active = NOT is_active WHERE id = ?')->execute([$id]);
    $message = 'Status updated.';
    $messageType = 'success';
    $action = 'list';
}

if ($action !== 'missing' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['move']) && empty($_POST['_confirm_action'])) {
    moveDisplayOrder($conn, 'company_documents', (int) ($_POST['id'] ?? 0), (string) $_POST['move']);
    $message = 'Order updated.';
    $messageType = 'success';
    $action = 'list';
}

$editDocument = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $conn->prepare('SELECT * FROM company_documents WHERE id = ?');
    $stmt->execute([$id]);
    $editDocument = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$editDocument) {
        $message = 'Document not found.';
        $messageType = 'error';
        $action = 'list';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['_confirm_action']) && !isset($_POST['move']) && $action !== 'missing') {
    $id = (int) ($_POST['id'] ?? 0);
    $title = trim((string) ($_POST['title'] ?? ''));
    $documentType = (string) ($_POST['document_type'] ?? '');
    $documentNumber = trim((string) ($_POST['document_number'] ?? ''));
    $authority = trim((string) ($_POST['issuing_authority'] ?? ''));
    $issueDate = trim((string) ($_POST['issue_date'] ?? ''));
    $expiryDate = trim((string) ($_POST['expiry_date'] ?? ''));
    $displayOrder = (int) ($_POST['display_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $errors = [];

    if ($title === '') {
        $errors[] = 'Title is required';
    }

    if (!isset($types[$documentType])) {
        $errors[] = 'Choose a document type';
    }

    foreach (['Issue date' => $issueDate, 'Expiry date' => $expiryDate] as $label => $value) {
        if ($value !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $errors[] = $label . ' must be a real date';
        }
    }

    $certificate = $id > 0 && is_array($editDocument) ? ($editDocument['certificate_file'] ?? null) : null;

    if (!empty($_FILES['certificate']['name'])) {
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            $errors[] = 'The certificate folder could not be created';
        }

        $filename = storedUploadName((string) $_FILES['certificate']['name']);
        $extension = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));

        if ($filename === null || !in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            $errors[] = 'Certificate must be a PDF, JPG, or PNG';
        } elseif ((int) $_FILES['certificate']['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Certificate must be under 5 MB';
        } elseif ($filename !== null && empty($errors) && move_uploaded_file($_FILES['certificate']['tmp_name'], $uploadDir . '/' . $filename)) {
            removeStoredFile($uploadDir, $certificate);
            $certificate = $filename;
        } elseif (empty($errors)) {
            $errors[] = 'The certificate could not be saved';
        }
    }

    if ($errors === []) {
        try {
            $values = [
                $title,
                $documentType,
                $documentNumber === '' ? null : $documentNumber,
                $authority === '' ? null : $authority,
                $issueDate === '' ? null : $issueDate,
                $expiryDate === '' ? null : $expiryDate,
                storedUploadName(is_string($certificate) ? $certificate : null),
                $displayOrder,
                $isActive,
            ];

            if ($id > 0) {
                $values[] = $id;
                $saved = $conn->prepare('UPDATE company_documents SET title = ?, document_type = ?, document_number = ?, issuing_authority = ?, issue_date = ?, expiry_date = ?, certificate_file = ?, display_order = ?, is_active = ? WHERE id = ?')->execute($values);
                $message = $saved ? 'Document updated.' : 'The document could not be updated.';
            } else {
                $saved = $conn->prepare('INSERT INTO company_documents (title, document_type, document_number, issuing_authority, issue_date, expiry_date, certificate_file, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute($values);
                $message = $saved ? 'Document added.' : 'The document could not be added.';
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

$documents = [];
if ($action === 'list') {
    $documents = getCompanyDocuments($conn, false);
}

include 'includes/header.php';
?>

<div class="main-content">
    <div class="top-nav">
        <button class="menu-toggle" id="menu-toggle"><i class="fas fa-bars"></i></button>
        <h1>Company Documents</h1>
        <?php if ($action === 'list'): ?>
            <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Document</a>
        <?php else: ?>
            <div></div>
        <?php endif; ?>
    </div>

    <div class="container">
        <?php if ($message !== ''): ?>
            <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($action === 'missing'): ?>
            <div class="panel"><p>Import <code>deploy/upgrade-company-documents.sql</code> once, then reload this page.</p></div>
        <?php elseif ($action === 'add' || $action === 'edit'): ?>
            <div class="panel">
                <h3><?php echo $action === 'add' ? 'Add' : 'Edit'; ?> Document</h3>
                <form action="" method="post" enctype="multipart/form-data">
                    <?php if ($action === 'edit' && is_array($editDocument)): ?>
                        <input type="hidden" name="id" value="<?php echo (int) $editDocument['id']; ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input type="text" id="title" name="title" maxlength="160" value="<?php echo $action === 'edit' && is_array($editDocument) ? htmlspecialchars((string) $editDocument['title']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="document_type">Type *</label>
                        <select id="document_type" name="document_type" required>
                            <?php foreach ($types as $value => $label): ?>
                                <option value="<?php echo htmlspecialchars($value); ?>" <?php echo ($action === 'edit' && is_array($editDocument) && $editDocument['document_type'] === $value) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="document_number">Document number</label>
                        <input type="text" id="document_number" name="document_number" maxlength="80" value="<?php echo $action === 'edit' && is_array($editDocument) ? htmlspecialchars((string) ($editDocument['document_number'] ?? '')) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="issuing_authority">Issuing authority</label>
                        <input type="text" id="issuing_authority" name="issuing_authority" maxlength="160" value="<?php echo $action === 'edit' && is_array($editDocument) ? htmlspecialchars((string) ($editDocument['issuing_authority'] ?? '')) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="issue_date">Issue date</label>
                        <input type="date" id="issue_date" name="issue_date" value="<?php echo $action === 'edit' && is_array($editDocument) ? htmlspecialchars(formatStoredDate($editDocument['issue_date'] ?? '') !== '' ? (string) $editDocument['issue_date'] : '') : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="expiry_date">Expiry date</label>
                        <input type="date" id="expiry_date" name="expiry_date" value="<?php echo $action === 'edit' && is_array($editDocument) ? htmlspecialchars(formatStoredDate($editDocument['expiry_date'] ?? '') !== '' ? (string) $editDocument['expiry_date'] : '') : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="certificate">Certificate</label>
                        <input type="file" id="certificate" name="certificate" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                        <p class="note">PDF, JPG, or PNG. Maximum 5 MB. Leave this empty to keep the current file.</p>
                        <?php if ($action === 'edit' && is_array($editDocument) && storedUploadName($editDocument['certificate_file'] ?? null) !== null): ?>
                            <p><a href="<?php echo htmlspecialchars(publicAsset('assets/documents/' . $editDocument['certificate_file'])); ?>" target="_blank" rel="noopener">Current certificate</a></p>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="display_order">Display order</label>
                        <input type="number" id="display_order" name="display_order" min="0" value="<?php echo $action === 'edit' && is_array($editDocument) ? (int) $editDocument['display_order'] : 0; ?>">
                        <p class="note">Lower numbers appear first. The list also has up and down controls.</p>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-wrapper" for="is_active">
                            <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo ($action === 'edit' && is_array($editDocument) ? (int) $editDocument['is_active'] === 1 : true) ? 'checked' : ''; ?>>
                            Active
                        </label>
                    </div>
                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary"><?php echo $action === 'add' ? 'Add' : 'Update'; ?> Document</button>
                        <a href="?action=list" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="panel">
                <h3>Documents</h3>
                <?php if ($documents === []): ?>
                    <p>No documents yet. <a href="?action=add">Add the first one</a>.</p>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Number</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $document): ?>
                                    <tr>
                                        <td>
                                            <?php echo (int) $document['display_order']; ?>
                                            <form method="post" style="display:inline">
                                                <input type="hidden" name="id" value="<?php echo (int) $document['id']; ?>">
                                                <input type="hidden" name="move" value="up">
                                                <button type="submit" title="Move up">Up</button>
                                            </form>
                                            <form method="post" style="display:inline">
                                                <input type="hidden" name="id" value="<?php echo (int) $document['id']; ?>">
                                                <input type="hidden" name="move" value="down">
                                                <button type="submit" title="Move down">Down</button>
                                            </form>
                                        </td>
                                        <td><?php echo htmlspecialchars((string) $document['title']); ?></td>
                                        <td><?php echo htmlspecialchars(documentTypeLabel((string) $document['document_type'])); ?></td>
                                        <td><?php echo htmlspecialchars((string) ($document['document_number'] ?? '')); ?></td>
                                        <td><?php echo (int) $document['is_active'] === 1 ? 'Active' : 'Inactive'; ?></td>
                                        <td class="action-buttons">
                                            <a href="?action=edit&id=<?php echo (int) $document['id']; ?>" title="Edit"><i class="fas fa-edit" style="color:#007bff;"></i></a>
                                            <form method="post" action="?action=toggle&id=<?php echo (int) $document['id']; ?>" style="display:inline;margin-right:10px">
                                                <input type="hidden" name="_confirm_action" value="1">
                                                <button type="submit" title="<?php echo (int) $document['is_active'] === 1 ? 'Deactivate' : 'Activate'; ?>" style="background:none;border:0;padding:0;cursor:pointer;"><i class="fas <?php echo (int) $document['is_active'] === 1 ? 'fa-toggle-on' : 'fa-toggle-off'; ?>" style="color:<?php echo (int) $document['is_active'] === 1 ? '#28a745' : '#6c757d'; ?>;"></i></button>
                                            </form>
                                            <form method="post" action="?action=delete&id=<?php echo (int) $document['id']; ?>" style="display:inline;margin-right:10px" onsubmit="return confirm('Are you sure you want to delete this document?');">
                                                <input type="hidden" name="_confirm_action" value="1">
                                                <button type="submit" title="Delete" style="background:none;border:0;padding:0;cursor:pointer;"><i class="fas fa-trash-alt" style="color:#dc3545;"></i></button>
                                            </form>
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
