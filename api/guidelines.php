<?php
/**
 * api/guidelines.php
 * Clinical Decision Support System (CDSS) - Guidelines & Formulary Lookup Endpoint
 *
 * GET /api/guidelines.php?query={searchQuery}&department={dept}
 * Returns: JSON Array of matching clinical guidelines with diagnosis, treatment_plan,
 *          medicine_name, dosage_instructions, and default_quantity.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';

try {
    $rawQuery = trim($_GET['query'] ?? '');
    $department = trim($_GET['department'] ?? '');

    // Handle empty query gracefully
    if ($rawQuery === '') {
        echo json_encode([]);
        exit;
    }

    $searchTerm = '%' . $rawQuery . '%';
    $prefixTerm = $rawQuery . '%';

    if (!empty($department)) {
        $sql = "
            SELECT id, department, diagnosis, treatment_plan, medicine_name, dosage_instructions, default_quantity
            FROM clinical_guidelines
            WHERE (diagnosis LIKE :searchTerm OR medicine_name LIKE :searchTerm2)
              AND (department LIKE :deptTerm OR LOWER(department) LIKE '%general%')
            ORDER BY 
                CASE 
                    WHEN diagnosis LIKE :prefixTerm THEN 1
                    WHEN diagnosis LIKE :searchTerm3 THEN 2
                    ELSE 3
                END,
                diagnosis ASC
            LIMIT 15
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':searchTerm', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':searchTerm2', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':deptTerm', '%' . $department . '%', PDO::PARAM_STR);
        $stmt->bindValue(':prefixTerm', $prefixTerm, PDO::PARAM_STR);
        $stmt->bindValue(':searchTerm3', $searchTerm, PDO::PARAM_STR);
    } else {
        $sql = "
            SELECT id, department, diagnosis, treatment_plan, medicine_name, dosage_instructions, default_quantity
            FROM clinical_guidelines
            WHERE diagnosis LIKE :searchTerm OR medicine_name LIKE :searchTerm2
            ORDER BY 
                CASE 
                    WHEN diagnosis LIKE :prefixTerm THEN 1
                    WHEN diagnosis LIKE :searchTerm3 THEN 2
                    ELSE 3
                END,
                diagnosis ASC
            LIMIT 15
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':searchTerm', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':searchTerm2', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':prefixTerm', $prefixTerm, PDO::PARAM_STR);
        $stmt->bindValue(':searchTerm3', $searchTerm, PDO::PARAM_STR);
    }

    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results ?: []);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error'   => 'Failed to query clinical guidelines.',
        'message' => $e->getMessage()
    ]);
}

