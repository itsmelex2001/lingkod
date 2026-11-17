<?php
/**
 * api/guidelines.php
 * Clinical Diagnosis Guidelines, Existing Diagnoses & Formulary Lookup Endpoint
 *
 * GET /api/guidelines.php?query={searchQuery}&department={dept}
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';

function ensureAndSeedGuidelines(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `clinical_guidelines` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `department` VARCHAR(80) NOT NULL,
            `diagnosis` VARCHAR(200) NOT NULL,
            `treatment_plan` TEXT NOT NULL,
            `medicine_name` VARCHAR(255) DEFAULT NULL,
            `dosage_instructions` VARCHAR(255) DEFAULT NULL,
            `default_quantity` VARCHAR(80) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $count = (int)$pdo->query("SELECT COUNT(*) FROM `clinical_guidelines`")->fetchColumn();
    if ($count > 0) return;

    $seed = [
        // General Medical
        ['General Medical', 'Essential Hypertension', 'Low-sodium DASH diet, daily home blood pressure monitoring, stress reduction, regular aerobic exercise, and follow-up BP recheck after 2-4 weeks.', 'LOSARTAN 50 mg tablet (as potassium salt)', '1 tab OD in the morning', '30 tabs'],
        ['General Medical', 'Stage 2 Hypertension', 'Strict low-salt diet, daily BP log, cardiovascular risk screening, and outpatient BP review after 2 weeks.', 'AMLODIPINE 10 mg tablet (as besilate / camsylate)', '1 tab OD in the morning', '30 tabs'],
        ['General Medical', 'Type 2 Diabetes Mellitus', 'Medical nutrition therapy (low glycemic index diet), 150 mins/week moderate exercise, foot care education, and fasting blood sugar / HbA1c monitoring in 3 months.', 'METFORMIN 500 mg tablet (as hydrochloride)', '1 tab BID with or after meals', '60 tabs'],
        ['General Medical', 'Acute Upper Respiratory Tract Infection (URTI)', 'Adequate oral hydration, warm saline gargle, rest, fever/pain control, and return precautions for persistent fever >3 days or shortness of breath.', 'PARACETAMOL 500 mg tablet', '1-2 tabs q4-6h PRN for fever/pain', '20 tabs'],
        ['General Medical', 'Community-Acquired Pneumonia (Mild-Moderate)', 'Complete full antibiotic course, increase oral fluid intake, rest, monitor temperature and respiratory status; return immediately for dyspnea.', 'AMOXICILLIN 500 mg capsule (as trihydrate)', '1 cap TID x 7 days', '21 caps'],
        ['General Medical', 'Acute Bronchitis / Bronchial Asthma', 'Avoid airway triggers and smoke exposure, oral hydration, bronchodilator relief as needed, and return if wheezing worsens.', 'SALBUTAMOL MDI: 100 mcg per dose x 200 doses', '1-2 puffs q4-6h PRN bronchospasm', '1 canister'],
        ['General Medical', 'Acute Gastroenteritis', 'Aggressive oral rehydration solution after every loose stool, bland BRAT diet, hand hygiene education, and red-flag precautions for dehydration.', 'ORAL REHYDRATION SALT (ORS 75) sachet', 'Dissolve 1 sachet in 1L clean water, drink as needed', '5 sachets'],
        ['General Medical', 'Uncomplicated Urinary Tract Infection (UTI)', 'Complete full antimicrobial regimen, increase water intake (2-3L/day), avoid holding urine, and follow-up if dysuria persists.', 'CIPROFLOXACIN 500 mg tablet (as hydrochloride)', '1 tab BID x 7 days', '14 tabs'],
        ['General Medical', 'Gastroesophageal Reflux Disease (GERD) / Gastritis', 'Avoid spicy, fatty, acidic foods, coffee, and late-night meals; elevate head of bed and avoid lying down within 3 hours after eating.', 'OMEPRAZOLE 20 mg capsule', '1 cap OD 30 mins before breakfast', '14-30 caps'],
        ['General Medical', 'Allergic Rhinitis', 'Allergen and dust avoidance, saline nasal hygiene, adequate hydration, and bedtime antihistamine therapy.', 'CETIRIZINE 10 mg tablet (as dihydrochloride)', '1 tab OD at bedtime', '10-20 tabs'],
        ['General Medical', 'Dyslipidemia', 'Low-saturated-fat high-fiber diet, regular aerobic exercise, tobacco cessation, and repeat lipid profile after 8-12 weeks.', 'ATORVASTATIN 20 mg tablet (as calcium)', '1 tab OD at bedtime', '30 tabs'],
        ['General Medical', 'Musculoskeletal Strain / Osteoarthritis', 'Rest affected joint/muscle, warm/cold compress therapy, gentle stretching, and short-term analgesic after meals.', 'IBUPROFEN 400 mg tablet', '1 tab TID after meals PRN pain', '15 tabs'],

        // Neurology
        ['Neurology', 'Migraine without Aura', 'Maintain headache diary, identify and avoid triggers (sleep deprivation, dehydration, bright glare), adequate hydration, and prophylactic/abortive regimen.', 'TOPIRAMATE 25 mg Tablet', '1 tab OD at bedtime x 1 week then BID', '30 tabs'],
        ['Neurology', 'Tension-Type Headache', 'Stress management, ergonomic posture correction, adequate sleep hygiene, hydration, and non-narcotic acute pain relief.', 'PARACETAMOL 500 mg tablet', '1-2 tabs q4-6h PRN for fever/pain', '20 tabs'],
        ['Neurology', 'Epilepsy / Seizure Disorder', 'Strict anti-epileptic medication adherence without missed doses, seizure precautions (avoid swimming alone/heights/driving), sleep hygiene, and neuro follow-up.', 'LEVETIRACETAM 500 mg Film-coated tablet', '1 tab BID for seizure prophylaxis', '60 tabs'],
        ['Neurology', 'Peripheral Neuropathy', 'Nerve protection care, glycemic/vitamin optimization, daily foot inspection for sensory loss, and neuropathic agent titration.', 'GABAPENTIN 300 mg capsule', '1 cap TID for neuropathic pain', '30-90 caps'],
        ['Neurology', 'Trigeminal / Neuropathic Neuralgia', 'Avoid facial cold/touch triggers, adhere strictly to neuropathic stabilizer schedule, and review liver/hematologic labs on follow-up.', 'CARBAMAZEPINE 200 mg tablet', '1 tab BID, titrate as directed', '60 tabs'],
        ['Neurology', 'Post-Ischemic Stroke (CVA) Secondary Prevention', 'Strict blood pressure and lipid control, daily antiplatelet adherence, physical/occupational rehabilitation, and aspiration/fall precautions.', 'CLOPIDOGREL 75 mg tablet', '1 tab OD daily after meals', '30 tabs'],
        ['Neurology', 'Benign Paroxysmal Positional Vertigo (BPPV)', 'Slow head position changes, fall precautions, vestibular habituation (Brandt-Daroff/Epley maneuvers), and short-term vestibular suppressant.', 'BETAHISTINE 16 mg Tablet', '1 tab TID with food', '30 tabs'],
        ['Neurology', 'Parkinson\'s Disease', 'Administer dopaminergic therapy 30 minutes before meals for optimal absorption, physical therapy gait/balance training, and fall prevention.', 'LEVODOPA + CARBIDOPA 100 mg / 25 mg tablet', '1 tab TID 30 mins before meals', '90 tabs'],
        ['Neurology', 'Alzheimer\'s Disease / Mild Cognitive Impairment', 'Structured daily cognitive routine, caregiver supervision for medications and safety, hydration, and neurocognitive follow-up.', 'DONEPEZIL 5 mg Tablet', '1 tab OD at bedtime', '30 tabs'],

        // OPU Psychiatry
        ['OPU Psychiatry', 'Generalized Anxiety Disorder (GAD)', 'Cognitive behavioral coping techniques, structured sleep and breathing hygiene, caffeine reduction, and daily SSRI maintenance with 4-week reassessment.', 'ESCITALOPRAM 10 mg tablet (as Oxalate)', '1 tab OD morning or bedtime', '30 tabs'],
        ['OPU Psychiatry', 'Major Depressive Disorder (MDD)', 'Supportive psychotherapy, behavioral activation and daily routine building, suicide safety check, and continuous SSRI adherence.', 'SERTRALINE 50 mg tablet (as Hydrochloride)', '1 tab OD in the morning or bedtime', '30 tabs'],
        ['OPU Psychiatry', 'Panic Disorder', 'Psychoeducation on panic cycle, diaphragmatic breathing during attacks, SSRI maintenance, and short-term PRN acute anxiolytic only if severe.', 'FLUOXETINE 20 mg dispersible capsule', '1 cap OD in the morning after breakfast', '30 caps'],
        ['OPU Psychiatry', 'Schizophrenia / Psychotic Spectrum Disorder', 'Long-term antipsychotic adherence, family psychoeducation, metabolic/weight monitoring, and outpatient psychiatric follow-up in 2-4 weeks.', 'RISPERIDONE 2 mg tablet', '1 tab OD at bedtime or BID', '30-60 tabs'],
        ['OPU Psychiatry', 'Bipolar I / II Disorder', 'Mood charting, regular sleep-wake schedule, mood stabilizer adherence, and periodic serum level / renal-thyroid monitoring.', 'VALPROIC ACID 500 mg controlled release tablet', '1 tab BID with meals (bipolar stabilization)', '60 tabs'],
        ['OPU Psychiatry', 'Insomnia Disorder', 'Strict sleep hygiene (fixed wake time, dark quiet bedroom, avoid screens/caffeine before bed) alongside short-term sleep-onset support.', 'QUETIAPINE 25 mg tablet (as fumarate)', '1 tab at bedtime for sleep/anxiety', '30 tabs'],
        ['OPU Psychiatry', 'Drug-Induced Extrapyramidal Symptoms (EPS)', 'Administer anticholinergic therapy, review offending antipsychotic dose with psychiatrist, and monitor resolution of stiffness/tremor.', 'BIPERIDEN 2 mg tablet (as hydrochloride)', '1 tab BID for drug-induced parkinsonism', '30-60 tabs']
    ];

    $ins = $pdo->prepare("
        INSERT INTO `clinical_guidelines`
            (department, diagnosis, treatment_plan, medicine_name, dosage_instructions, default_quantity)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    foreach ($seed as $row) {
        $ins->execute($row);
    }
}

function normalizeDeptKey(string $dept): string {
    $d = strtolower(trim($dept));
    if ($d === '') return '';
    if (str_contains($d, 'neuro')) return 'neurology';
    if (str_contains($d, 'psych') || str_contains($d, 'opu')) return 'psychiatry';
    if (str_contains($d, 'general') || str_contains($d, 'medical')) return 'general';
    return $d;
}

try {
    ensureAndSeedGuidelines($pdo);

    $rawQuery   = trim($_GET['query'] ?? '');
    $department = trim($_GET['department'] ?? '');
    $deptKey    = normalizeDeptKey($department);

    $deptClause = '';
    $deptBind   = null;
    if ($deptKey === 'neurology') {
        $deptClause = "AND (LOWER(department) LIKE '%neuro%' OR LOWER(department) LIKE '%general%')";
        $deptBind = '%neuro%';
    } elseif ($deptKey === 'psychiatry') {
        $deptClause = "AND (LOWER(department) LIKE '%psych%' OR LOWER(department) LIKE '%opu%' OR LOWER(department) LIKE '%general%')";
        $deptBind = '%psych%';
    } elseif ($deptKey === 'general') {
        $deptClause = "AND LOWER(department) LIKE '%general%'";
        $deptBind = '%general%';
    }

    if ($rawQuery === '') {
        $sql = "
            SELECT id, department, diagnosis, treatment_plan, medicine_name, dosage_instructions, default_quantity, 'guideline' AS source
            FROM clinical_guidelines
            WHERE 1=1 $deptClause
            ORDER BY
                " . ($deptBind ? "CASE WHEN LOWER(department) LIKE :deptPref THEN 0 ELSE 1 END," : "") . "
                diagnosis ASC
            LIMIT 25
        ";
        $stmt = $pdo->prepare($sql);
        if ($deptBind) $stmt->bindValue(':deptPref', $deptBind, PDO::PARAM_STR);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $searchTerm = '%' . $rawQuery . '%';
        $prefixTerm = $rawQuery . '%';
        $sql = "
            SELECT id, department, diagnosis, treatment_plan, medicine_name, dosage_instructions, default_quantity, 'guideline' AS source
            FROM clinical_guidelines
            WHERE (diagnosis LIKE :q1 OR treatment_plan LIKE :q2 OR medicine_name LIKE :q3)
            $deptClause
            ORDER BY
                " . ($deptBind ? "CASE WHEN LOWER(department) LIKE :deptPref THEN 0 ELSE 1 END," : "") . "
                CASE
                    WHEN diagnosis LIKE :p1 THEN 1
                    WHEN diagnosis LIKE :q4 THEN 2
                    ELSE 3
                END,
                diagnosis ASC
            LIMIT 20
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':q1', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':q2', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':q3', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':p1', $prefixTerm, PDO::PARAM_STR);
        $stmt->bindValue(':q4', $searchTerm, PDO::PARAM_STR);
        if ($deptBind) $stmt->bindValue(':deptPref', $deptBind, PDO::PARAM_STR);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Merge distinct existing diagnoses recorded in completed patient consultations
    $seenDiag = [];
    foreach ($results as $r) {
        $seenDiag[strtolower(trim((string)$r['diagnosis']))] = true;
    }

    try {
        if ($rawQuery === '') {
            $pStmt = $pdo->query("
                SELECT department, medical_history_diagnosis, medical_history_treatment, medical_history_prescription, active_medications
                FROM patients
                WHERE medical_history_diagnosis IS NOT NULL AND TRIM(medical_history_diagnosis) <> ''
                ORDER BY updated_at DESC
                LIMIT 30
            ");
        } else {
            $pStmt = $pdo->prepare("
                SELECT department, medical_history_diagnosis, medical_history_treatment, medical_history_prescription, active_medications
                FROM patients
                WHERE medical_history_diagnosis IS NOT NULL
                  AND TRIM(medical_history_diagnosis) <> ''
                  AND medical_history_diagnosis LIKE ?
                ORDER BY updated_at DESC
                LIMIT 20
            ");
            $pStmt->execute(['%' . $rawQuery . '%']);
        }
        $pRows = $pStmt ? $pStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        foreach ($pRows as $pr) {
            $diagText = trim((string)($pr['medical_history_diagnosis'] ?? ''));
            if ($diagText === '') continue;
            $key = strtolower($diagText);
            if (isset($seenDiag[$key])) continue;
            $seenDiag[$key] = true;

            $medName = '';
            $dosage = '';
            $qty = '';
            if (!empty($pr['active_medications'])) {
                $decoded = json_decode($pr['active_medications'], true);
                if (is_array($decoded) && !empty($decoded[0])) {
                    $medName = trim((string)($decoded[0]['medicine_name'] ?? ''));
                    $dosage  = trim((string)($decoded[0]['dosage'] ?? ''));
                    $qty     = trim((string)($decoded[0]['quantity'] ?? ''));
                }
            }
            $results[] = [
                'id'                  => null,
                'department'          => trim((string)($pr['department'] ?: 'General Medical')),
                'diagnosis'           => $diagText,
                'treatment_plan'      => trim((string)($pr['medical_history_treatment'] ?? '')),
                'medicine_name'       => $medName,
                'dosage_instructions' => $dosage,
                'default_quantity'    => $qty,
                'source'              => 'recorded'
            ];
        }
    } catch (Throwable $ignorePatientDiag) {}

    echo json_encode(array_values($results));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error'   => 'Failed to query clinical guidelines.',
        'message' => $e->getMessage()
    ]);
}

