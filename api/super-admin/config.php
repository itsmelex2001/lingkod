<?php
/**
 * api/super-admin/config.php
 * Endpoint for Global System Configurations:
 * - Hospital Information (Name, Telephone, Emergency Hotline, Address, Department Directory)
 * - Email / SMTP Verification Settings (Host, Port, Encryption, Username, Password, Sender)
 * - System Branding Asset Uploads (Logo & Favicon)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/auth-guard.php';
verifySuperAdminSession(true);

$configFile = __DIR__ . '/global-settings.json';

function getGlobalConfig(string $file): array {
    $defaults = [
        'hospital_name' => 'Mariveles Mental Wellness and General Hospital',
        'hospital_tagline' => 'Localized Integrated Network for Governance, Knowledge, and Operation in Digital Healthcare',
        'contact_phone' => '+63 47 935 4645',
        'emergency_hotline' => '0966-351-4518 / 0922-883-7756',
        'contact_email' => 'support@mmwgh.gov.ph',
        'hospital_address' => 'P. Monroe St., Brgy. Poblacion, Mariveles, Bataan, Philippines 2105',
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_encryption' => 'tls',
        'smtp_username' => 'mmwgh.notification@gmail.com',
        'smtp_password' => '', // masked in GET
        'smtp_sender_name' => 'MMWGH LINGKOD Notifications',
        'system_logo_url' => '/sdn/public/assets/mmwgh_logo.png',
        'updated_at' => null,
        'updated_by' => null
    ];

    if (!file_exists($file)) {
        file_put_contents($file, json_encode($defaults, JSON_PRETTY_PRINT));
        return $defaults;
    }

    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? array_merge($defaults, $data) : $defaults;
}

function saveGlobalConfig(string $file, array $data): bool {
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT)) !== false;
}

$method = $_SERVER['REQUEST_METHOD'];

// GET: Return configurations (masking SMTP password)
if ($method === 'GET') {
    $cfg = getGlobalConfig($configFile);
    $masked = $cfg;
    if (!empty($masked['smtp_password'])) {
        $masked['smtp_password'] = '••••••••';
    }
    echo json_encode(['success' => true, 'data' => $masked]);
    exit;
}

// POST: Save configurations
if ($method === 'POST') {
    // Check if multipart form upload (logo upload)
    if (!empty($_FILES['system_logo'])) {
        $file = $_FILES['system_logo'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['png', 'jpg', 'jpeg', 'webp', 'svg'];
            if (!in_array($ext, $allowed)) {
                echo json_encode(['success' => false, 'message' => 'Invalid image format. Allowed: PNG, JPG, WEBP, SVG.']);
                exit;
            }

            $destDir = __DIR__ . '/../../public/assets/uploads';
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0777, true);
            }
            $filename = 'logo_' . time() . '.' . $ext;
            $destPath = $destDir . '/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                $cfg = getGlobalConfig($configFile);
                $cfg['system_logo_url'] = '/sdn/public/assets/uploads/' . $filename;
                $cfg['updated_at'] = date('Y-m-d H:i:s');
                $cfg['updated_by'] = $_SESSION['user'] ?? 'super_admin';
                saveGlobalConfig($configFile, $cfg);

                echo json_encode(['success' => true, 'message' => 'System logo uploaded successfully.', 'logo_url' => $cfg['system_logo_url']]);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file.']);
                exit;
            }
        }
    }

    // JSON Input updates
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $section = $input['section'] ?? '';
    $cfg = getGlobalConfig($configFile);

    if ($section === 'hospital') {
        $cfg['hospital_name'] = trim($input['hospital_name'] ?? $cfg['hospital_name']);
        $cfg['hospital_tagline'] = trim($input['hospital_tagline'] ?? $cfg['hospital_tagline']);
        $cfg['contact_phone'] = trim($input['contact_phone'] ?? $cfg['contact_phone']);
        $cfg['emergency_hotline'] = trim($input['emergency_hotline'] ?? $cfg['emergency_hotline']);
        $cfg['contact_email'] = trim($input['contact_email'] ?? $cfg['contact_email']);
        $cfg['hospital_address'] = trim($input['hospital_address'] ?? $cfg['hospital_address']);
        $cfg['updated_at'] = date('Y-m-d H:i:s');
        $cfg['updated_by'] = $_SESSION['user'] ?? 'super_admin';

        saveGlobalConfig($configFile, $cfg);
        echo json_encode(['success' => true, 'message' => 'Hospital contact information saved.']);
        exit;
    }

    if ($section === 'smtp') {
        $cfg['smtp_host'] = trim($input['smtp_host'] ?? $cfg['smtp_host']);
        $cfg['smtp_port'] = (int)($input['smtp_port'] ?? $cfg['smtp_port']);
        $cfg['smtp_encryption'] = trim($input['smtp_encryption'] ?? $cfg['smtp_encryption']);
        $cfg['smtp_username'] = trim($input['smtp_username'] ?? $cfg['smtp_username']);
        $cfg['smtp_sender_name'] = trim($input['smtp_sender_name'] ?? $cfg['smtp_sender_name']);

        $pass = trim($input['smtp_password'] ?? '');
        if ($pass !== '' && $pass !== '••••••••') {
            $cfg['smtp_password'] = $pass;
        }

        $cfg['updated_at'] = date('Y-m-d H:i:s');
        $cfg['updated_by'] = $_SESSION['user'] ?? 'super_admin';

        saveGlobalConfig($configFile, $cfg);
        echo json_encode(['success' => true, 'message' => 'SMTP verification settings saved.']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid configuration section.']);
    exit;
}

