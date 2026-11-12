<?php
/**
 * scripts/seed_clinical_guidelines.php
 * Clinical Decision Support System (CDSS) Database Seeder
 */

require_once __DIR__ . '/../api/config/db.php';

try {
    // 1. Create table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `clinical_guidelines` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `department` VARCHAR(100) NOT NULL,
            `diagnosis` VARCHAR(255) NOT NULL,
            `treatment_plan` TEXT NOT NULL,
            `medicine_name` VARCHAR(255) NOT NULL,
            `dosage_instructions` VARCHAR(255) NOT NULL,
            `default_quantity` VARCHAR(100) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_guidelines_diagnosis` (`diagnosis`),
            INDEX `idx_guidelines_department` (`department`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $guidelines = [
        // General Medical
        [
            'department'          => 'General Medical',
            'diagnosis'           => 'Essential Hypertension (High Blood Pressure)',
            'treatment_plan'      => 'Regular monitoring of blood pressure, blood sugar, and cholesterol levels. Healthy diet, regular physical activity, sodium reduction (<2g/day), weight management, and smoking cessation.',
            'medicine_name'       => 'Amlodipine 5mg tablet',
            'dosage_instructions' => '1 tablet once daily',
            'default_quantity'    => '30 tablets'
        ],
        [
            'department'          => 'General Medical',
            'diagnosis'           => 'Type 2 Diabetes Mellitus',
            'treatment_plan'      => 'Self-monitoring of blood glucose, dietary modification (low glycemic index carbohydrates), regular moderate aerobic exercise, and annual diabetic foot/eye examinations.',
            'medicine_name'       => 'Metformin 500mg tablet',
            'dosage_instructions' => '1 tablet twice daily with meals',
            'default_quantity'    => '60 tablets'
        ],
        [
            'department'          => 'General Medical',
            'diagnosis'           => 'Acute Upper Respiratory Tract Infection (URTI)',
            'treatment_plan'      => 'Adequate hydration, warm saline gargles, voice rest, fever control, and symptomatic relief. Follow-up if symptoms persist past 7 days.',
            'medicine_name'       => 'Paracetamol 500mg tablet',
            'dosage_instructions' => '1 tablet every 4 to 6 hours as needed for fever or body aches',
            'default_quantity'    => '20 tablets'
        ],
        [
            'department'          => 'General Medical',
            'diagnosis'           => 'Bronchial Asthma (Acute Exacerbation)',
            'treatment_plan'      => 'Avoidance of known environmental triggers (dust, cold air, smoke). Instruction on proper MDI inhaler and spacer technique.',
            'medicine_name'       => 'Salbutamol 100mcg Inhaler',
            'dosage_instructions' => '2 puffs every 4 to 6 hours as needed for wheezing or dyspnea',
            'default_quantity'    => '1 canister'
        ],

        // Neurology
        [
            'department'          => 'Neurology',
            'diagnosis'           => 'Migraine with or without Aura',
            'treatment_plan'      => 'Avoidance of identified dietary or environmental triggers. Establishment of a consistent sleep and meal schedule. Hydration and rest in a dark, quiet environment during attacks.',
            'medicine_name'       => 'Sumatriptan 50mg tablet',
            'dosage_instructions' => '1 tablet at the onset of a migraine attack',
            'default_quantity'    => '6 tablets'
        ],
        [
            'department'          => 'Neurology',
            'diagnosis'           => 'Tension-Type Headache (Frequent Episodic)',
            'treatment_plan'      => 'Stress reduction, posture correction, ergonomic workplace setup, neck stretching exercises, and trigger identification.',
            'medicine_name'       => 'Ibuprofen 400mg tablet',
            'dosage_instructions' => '1 tablet every 8 hours after meals as needed',
            'default_quantity'    => '15 tablets'
        ],
        [
            'department'          => 'Neurology',
            'diagnosis'           => 'Peripheral Neuropathy (Diabetic / Nutritional)',
            'treatment_plan'      => 'Strict glycemic control, daily foot inspection for micro-trauma, avoid walking barefoot, supportive footwear, and smoking cessation.',
            'medicine_name'       => 'Pregabalin 75mg capsule',
            'dosage_instructions' => '1 capsule once daily at bedtime',
            'default_quantity'    => '30 capsules'
        ],

        // Psychiatry
        [
            'department'          => 'Psychiatry',
            'diagnosis'           => 'Generalized Anxiety Disorder (GAD)',
            'treatment_plan'      => 'Cognitive Behavioral Therapy (CBT). Breathing exercises, relaxation techniques, and mindfulness. Regular sleep schedule and caffeine restriction.',
            'medicine_name'       => 'Escitalopram 10 mg tablet',
            'dosage_instructions' => 'Take once daily as prescribed',
            'default_quantity'    => '30 tablets'
        ],
        [
            'department'          => 'Psychiatry',
            'diagnosis'           => 'Major Depressive Disorder (Single / Recurrent Episode)',
            'treatment_plan'      => 'Psychotherapy (CBT/Interpersonal Therapy), behavioral activation, daily sunlight exposure, structured routine, and psychoeducation on medication adherence.',
            'medicine_name'       => 'Sertraline 50mg tablet',
            'dosage_instructions' => '1 tablet once daily in the morning',
            'default_quantity'    => '30 tablets'
        ],
        [
            'department'          => 'Psychiatry',
            'diagnosis'           => 'Insomnia Disorder (Non-Organic)',
            'treatment_plan'      => 'Cognitive Behavioral Therapy for Insomnia (CBT-I), sleep hygiene education (consistent wake time, dark/cool sleep environment, no screen exposure 1 hr before bed).',
            'medicine_name'       => 'Zolpidem 10mg tablet',
            'dosage_instructions' => '1 tablet at bedtime as needed for sleep (short-term use)',
            'default_quantity'    => '14 tablets'
        ]
    ];

    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM `clinical_guidelines` WHERE `diagnosis` = ?");
    $insertStmt = $pdo->prepare("
        INSERT INTO `clinical_guidelines` (`department`, `diagnosis`, `treatment_plan`, `medicine_name`, `dosage_instructions`, `default_quantity`)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $seededCount = 0;
    foreach ($guidelines as $g) {
        $checkStmt->execute([$g['diagnosis']]);
        if ($checkStmt->fetchColumn() == 0) {
            $insertStmt->execute([
                $g['department'],
                $g['diagnosis'],
                $g['treatment_plan'],
                $g['medicine_name'],
                $g['dosage_instructions'],
                $g['default_quantity']
            ]);
            $seededCount++;
        }
    }

    echo json_encode([
        'success'      => true,
        'message'      => "Clinical guidelines table verified. Seeded $seededCount new entries.",
        'total_seeded' => $seededCount
    ], JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

