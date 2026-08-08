<?php
/**
 * admin_actions.php — Handles all admin CRUD operations for menu items.
 * 
 * Receives POST requests from admin.php and redirects back with status.
 * Supported actions: toggle_visibility, update_item, add_item, delete_item,
 *                    toggle_page, generate_hash
 */

session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

// ── Security: require admin login + valid CSRF token ────────
auth_require(['admin']);
csrf_verify();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php');
    exit;
}

$action = $_POST['action'] ?? '';

// ── Helper: redirect back with status ───────────────────────────
function redirect_back(string $status, string $msg): void {
    header('Location: admin.php?status=' . urlencode($status) . '&msg=' . urlencode($msg));
    exit;
}

// ── Helper: handle image upload ─────────────────────────────────
function handle_image_upload(string $field = 'item_image'): ?string {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // No file uploaded
    }

    $file = $_FILES[$field];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    // Validate file type — SVG excluded (XSS risk)
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed_types)) {
        return null;
    }

    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return null;
    }

    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (!$ext) {
        $ext_map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
        ];
        $ext = $ext_map[$mime] ?? 'jpg';
    }
    $filename = uniqid('menu_', true) . '.' . $ext;
    $upload_dir = __DIR__ . '/../Image_Content/';
    $dest = $upload_dir . $filename;

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return '../Image_Content/' . $filename;
    }

    return null;
}

// ═══════════════════════════════════════════════════════════════
// ACTION: Toggle Visibility
// ═══════════════════════════════════════════════════════════════
if ($action === 'toggle_visibility') {
    // Detect AJAX request (fetch sends this automatically via FormData)
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
               (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    $item_id = $_POST['item_id'] ?? '';
    if (!$item_id) {
        if ($is_ajax) { header('Content-Type: application/json'); echo json_encode(['ok' => false, 'msg' => 'Missing item ID.']); exit; }
        redirect_back('error', 'Missing item ID.');
    }

    try {
        $stmt = $pdo->prepare("UPDATE menu_items SET is_visible = NOT is_visible WHERE id = ?");
        $stmt->execute([$item_id]);

        if ($stmt->rowCount() > 0) {
            if ($is_ajax) { header('Content-Type: application/json'); echo json_encode(['ok' => true]); exit; }
            redirect_back('success', 'Item visibility toggled successfully.');
        } else {
            if ($is_ajax) { header('Content-Type: application/json'); echo json_encode(['ok' => false, 'msg' => 'Item not found.']); exit; }
            redirect_back('error', 'Item not found.');
        }
    } catch (PDOException $e) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'Database error. Please try again.']);
            exit;
        }
        error_log('[admin_actions] toggle_visibility DB error: ' . $e->getMessage());
        redirect_back('error', 'A database error occurred. Please try again.');
    }
}

// ═══════════════════════════════════════════════════════════════
// ACTION: Update Item
// ═══════════════════════════════════════════════════════════════
elseif ($action === 'update_item') {
    $item_id     = trim($_POST['item_id'] ?? '');
    $name        = trim($_POST['item_name'] ?? '');
    $category    = trim($_POST['item_category'] ?? '');
    $description = trim($_POST['item_description'] ?? '');
    $price       = floatval($_POST['item_price'] ?? 0);
    $badge       = trim($_POST['item_badge'] ?? 'Veg');
    $is_visible  = isset($_POST['item_visible']) ? 1 : 0;
    $sort_order  = intval($_POST['item_sort_order'] ?? 0);

    if (!$item_id || !$name || !$category || !$description || $price <= 0) {
        redirect_back('error', 'Please fill in all required fields.');
    }

    // Handle image upload
    $new_image = handle_image_upload('item_image');

    try {
        if ($new_image) {
            $stmt = $pdo->prepare(
                "UPDATE menu_items SET name = ?, category = ?, description = ?, price = ?, 
                 badge = ?, image_path = ?, is_visible = ?, sort_order = ? WHERE id = ?"
            );
            $stmt->execute([$name, $category, $description, $price, $badge, $new_image, $is_visible, $sort_order, $item_id]);
        } else {
            $stmt = $pdo->prepare(
                "UPDATE menu_items SET name = ?, category = ?, description = ?, price = ?, 
                 badge = ?, is_visible = ?, sort_order = ? WHERE id = ?"
            );
            $stmt->execute([$name, $category, $description, $price, $badge, $is_visible, $sort_order, $item_id]);
        }

        redirect_back('success', "\"$name\" updated successfully.");
    } catch (PDOException $e) {
        error_log('[admin_actions] update_item DB error: ' . $e->getMessage());
        redirect_back('error', 'A database error occurred. Please try again.');
    }
}

// ═══════════════════════════════════════════════════════════════
// ACTION: Add New Item
// ═══════════════════════════════════════════════════════════════
elseif ($action === 'add_item') {
    $item_id     = trim($_POST['item_id'] ?? '');
    $name        = trim($_POST['item_name'] ?? '');
    $category    = trim($_POST['item_category'] ?? '');
    $description = trim($_POST['item_description'] ?? '');
    $price       = floatval($_POST['item_price'] ?? 0);
    $badge       = trim($_POST['item_badge'] ?? 'Veg');
    $is_visible  = isset($_POST['item_visible']) ? 1 : 0;
    $sort_order  = intval($_POST['item_sort_order'] ?? 0);

    if (!$item_id || !$name || !$category || !$description || $price <= 0) {
        redirect_back('error', 'Please fill in all required fields.');
    }

    // Check for duplicate ID
    $check = $pdo->prepare("SELECT COUNT(*) FROM menu_items WHERE id = ?");
    $check->execute([$item_id]);
    if ($check->fetchColumn() > 0) {
        redirect_back('error', "Item ID \"$item_id\" already exists. Please use a unique ID.");
    }

    // Handle image upload
    $image_path = handle_image_upload('item_image');
    if (!$image_path) {
        $image_path = '../Image_Content/image.svg'; // Default placeholder
    }

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO menu_items (id, category, name, description, price, badge, image_path, is_visible, sort_order) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$item_id, $category, $name, $description, $price, $badge, $image_path, $is_visible, $sort_order]);

        redirect_back('success', "\"$name\" added to the menu!");
    } catch (PDOException $e) {
        error_log('[admin_actions] add_item DB error: ' . $e->getMessage());
        redirect_back('error', 'A database error occurred. Please try again.');
    }
}

// ═══════════════════════════════════════════════════════════════
// ACTION: Delete Item
// ═══════════════════════════════════════════════════════════════
elseif ($action === 'delete_item') {
    $item_id = trim($_POST['item_id'] ?? '');
    if (!$item_id) {
        redirect_back('error', 'Missing item ID.');
    }

    try {
        // Get the item name for the success message
        $name_stmt = $pdo->prepare("SELECT name FROM menu_items WHERE id = ?");
        $name_stmt->execute([$item_id]);
        $item_name = $name_stmt->fetchColumn() ?: 'Item';

        $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
        $stmt->execute([$item_id]);

        if ($stmt->rowCount() > 0) {
            redirect_back('success', "\"$item_name\" has been deleted.");
        } else {
            redirect_back('error', 'Item not found.');
        }
    } catch (PDOException $e) {
        error_log('[admin_actions] delete_item DB error: ' . $e->getMessage());
        redirect_back('error', 'A database error occurred. Please try again.');
    }
}

// ═══════════════════════════════════════════════════════════════
// ACTION: Toggle Page Visibility
// ═══════════════════════════════════════════════════════════════
elseif ($action === 'toggle_page') {
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
               (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    $page = strtolower(trim($_POST['page'] ?? ''));
    $allowed_pages = ['about', 'contact', 'surprise'];

    if (!in_array($page, $allowed_pages, true)) {
        if ($is_ajax) { header('Content-Type: application/json'); echo json_encode(['ok' => false, 'msg' => 'Invalid page.']); exit; }
        redirect_back('error', 'Invalid page.');
    }

    $settings_file = __DIR__ . '/../includes/page_settings.json';
    $settings = json_decode(file_get_contents($settings_file), true) ?? [];
    $settings[$page] = !($settings[$page] ?? true);
    $new_state = $settings[$page];

    if (file_put_contents($settings_file, json_encode($settings, JSON_PRETTY_PRINT)) !== false) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true, 'visible' => $new_state]);
            exit;
        }
        redirect_back('success', 'Page visibility updated.');
    } else {
        if ($is_ajax) { header('Content-Type: application/json'); echo json_encode(['ok' => false, 'msg' => 'Could not write settings file.']); exit; }
        redirect_back('error', 'Could not save settings. Check file permissions.');
    }
}

// ═══════════════════════════════════════════════════════════════
// ACTION: Generate bcrypt hash (for credentials helper)
// ═══════════════════════════════════════════════════════════════
elseif ($action === 'generate_hash') {
    header('Content-Type: application/json');
    $password = $_POST['password'] ?? '';
    if (strlen($password) < 6) {
        echo json_encode(['ok' => false, 'msg' => 'Password must be at least 6 characters.']);
        exit;
    }
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    echo json_encode(['ok' => true, 'hash' => $hash]);
    exit;
}

// ═══════════════════════════════════════════════════════════════
// Unknown action
// ═══════════════════════════════════════════════════════════════
else {
    redirect_back('error', 'Unknown action.');
}
