<?php
/**
 * scripts/seed_specializations.php
 * MMWGH LINGKOD — Database Specialization & Doctor Seeder
 *
 * Checks existing specializations and assigned doctors across General Medical,
 * Neurology, and Psychiatry. Inserts missing specializations and provisions
 * new dummy doctor profiles for each without duplicating existing records.
 */

// Handle CLI or Web execution
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

require_once __DIR__ . '/../api/config/db.php';

try {
    // 1. Ensure dedicated specializations catalog table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `specializations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `department` ENUM('general', 'neurology', 'psychiatry') NOT NULL,
            `name` VARCHAR(150) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_dept_spec` (`department`, `name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. Master List of Required Sub-Specializations by Department
    $masterList = [
        'general' => [
            'department_label' => 'General Medical',
            'specializations' => [
                'Internal Medicine',
                'Family Medicine & General Practice',
                'Routine Check-ups & Preventative Care',
                'Chronic Disease Management',
                'Pediatric Care',
                'General Surgery & Minor Procedures',
                'Preventive Healthcare & Physical Examination',
            ]
        ],
        'neurology' => [
            'department_label' => 'Neurology',
            'specializations' => [
                'Clinical Neurology',
                'Headache & Migraine Management',
                'Stroke Evaluation',
                'Epilepsy & Seizure Disorders',
                'Movement Disorders',
                'Neuromuscular Medicine',
                'Dementia Evaluation',
                'Neurological Diagnostic Testing',
            ]
        ],
        'psychiatry' => [
            'department_label' => 'Psychiatry',
            'specializations' => [
                'Adult Psychiatry',
                'Child & Adolescent Psychiatry',
                'Anxiety/Panic/Depression Management',
                'Mood Disorders',
                'Substance Abuse',
                'Geriatric Psychiatry',
                'Clinical Neuropsychiatry',
                'Psychotherapy',
            ]
        ],
    ];

    // 3. Pool of unique dummy doctor names for new assignments
    $dummyNames = [
        'general' => [
            'Dr. Angelo Mercado, MD',
            'Dr. Beatrice Alcantara, MD',
            'Dr. Carlo Valenzuela, MD',
            'Dr. Danielle Soriano, MD',
            'Dr. Eduardo Pascual, MD',
        ],
        'neurology' => [
            'Dr. Francesca David, MD',
            'Dr. Gerald Ocampo, MD',
            'Dr. Hannah Manalo, MD',
            'Dr. Ian Salazar, MD',
            'Dr. Jasmine Tolentino, MD',
            'Dr. Kenneth Aguilar, MD',
        ],
        'psychiatry' => [
            'Dr. Lourdes Castillo, MD',
            'Dr. Manuel Santiago, MD',
            'Dr. Nicole Dizon, MD',
            'Dr. Oscar Villanueva, MD',
            'Dr. Priscilla Evangelista, MD',
            'Dr. Rafael Navarro, MD',
        ],
    ];

    $nameIndex = ['general' => 0, 'neurology' => 0, 'psychiatry' => 0];

    // 4. Prepared Statements for Secure Queries & Inserts
    $stmtCheckDoc = $pdo->prepare("
        SELECT id, name, specialization FROM doctors 
        WHERE department = :dept AND LOWER(TRIM(specialization)) = LOWER(TRIM(:spec))
        LIMIT 1
    ");

    $stmtCheckSpec = $pdo->prepare("
        SELECT id, name FROM specializations 
        WHERE department = :dept AND LOWER(TRIM(name)) = LOWER(TRIM(:spec))
        LIMIT 1
    ");

    $stmtInsertSpec = $pdo->prepare("
        INSERT IGNORE INTO specializations (department, name) 
        VALUES (:dept, :name)
    ");

    $stmtInsertDoc = $pdo->prepare("
        INSERT INTO doctors (
            name, specialization, department, 
            schedule_start, schedule_end, status, 
            schedule_date, total_slots
        ) VALUES (:name, :spec, :dept, :start, :end, 'available', CURDATE(), 20)
    ");

    $stmtCheckDocName = $pdo->prepare("
        SELECT COUNT(*) FROM doctors WHERE LOWER(TRIM(name)) = LOWER(TRIM(:name))
    ");

    $skippedCount   = 0;
    $addedSpecCount = 0;
    $addedDocCount  = 0;
    $log            = [];

    // Begin Transaction
    $pdo->beginTransaction();

    foreach ($masterList as $dept => $info) {
        $deptLabel = $info['department_label'];

        foreach ($info['specializations'] as $specName) {
            // Check if specialization exists and is assigned to an existing doctor
            $stmtCheckDoc->execute([
                ':dept' => $dept,
                ':spec' => $specName
            ]);
            $existingDoc = $stmtCheckDoc->fetch(PDO::FETCH_ASSOC);

            // Check if specialization exists in specializations table
            $stmtCheckSpec->execute([
                ':dept' => $dept,
                ':spec' => $specName
            ]);
            $existingSpec = $stmtCheckSpec->fetch(PDO::FETCH_ASSOC);

            // Case A: Specialization is already in the database and assigned
            if ($existingDoc) {
                $skippedCount++;
                if (!$existingSpec) {
                    $stmtInsertSpec->execute([':dept' => $dept, ':name' => $specName]);
                }
                $log[] = "SKIP: [{$deptLabel}] \"{$specName}\" (Already assigned to {$existingDoc['name']})";
                continue;
            }

            // Case B: Specialization is missing -> insert into specializations table
            if (!$existingSpec) {
                $stmtInsertSpec->execute([':dept' => $dept, ':name' => $specName]);
                $addedSpecCount++;
            }

            // Generate an unused, realistic dummy doctor name
            $docName = '';
            while (true) {
                $idx = $nameIndex[$dept]++;
                if (isset($dummyNames[$dept][$idx])) {
                    $candidate = $dummyNames[$dept][$idx];
                } else {
                    $candidate = "Dr. " . ucfirst($dept) . " Specialist " . ($idx + 1) . ", MD";
                }
                $stmtCheckDocName->execute([':name' => $candidate]);
                if ((int)$stmtCheckDocName->fetchColumn() === 0) {
                    $docName = $candidate;
                    break;
                }
            }

            // Distribute schedule shifts (alternating morning and afternoon)
            $isMorning = ($addedDocCount % 2 === 0);
            $start = $isMorning ? '08:00:00' : '13:00:00';
            $end   = $isMorning ? '12:00:00' : '17:00:00';

            // Insert new linked doctor profile
            $stmtInsertDoc->execute([
                ':name'  => $docName,
                ':spec'  => $specName,
                ':dept'  => $dept,
                ':start' => $start,
                ':end'   => $end
            ]);

            $addedDocCount++;
            $log[] = "ADD:  [{$deptLabel}] \"{$specName}\" -> Assigned to {$docName} ({$start} - {$end})";
        }
    }

    $pdo->commit();

    // Echo Simple Summary Report
    echo "======================================================================\n";
    echo "MMWGH LINGKOD — Database Specialization & Doctor Seeder Report\n";
    echo "======================================================================\n";
    echo "Skipped: {$skippedCount} existing specializations. Added: {$addedSpecCount} new specializations and {$addedDocCount} new doctors.\n\n";
    echo "Detailed Breakdown:\n";
    foreach ($log as $entry) {
        echo "  " . $entry . "\n";
    }
    echo "======================================================================\n";

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "ERROR: Database update failed: " . $e->getMessage() . "\n";
}

