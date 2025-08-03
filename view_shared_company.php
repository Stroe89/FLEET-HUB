<?php
require_once 'db_connect.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    die('Link invalid sau expirat');
}

// Verify token and get company data
$sql = "SELECT c.* FROM date_companie c 
        JOIN share_links s ON c.user_id = s.user_id 
        WHERE s.token = ? AND s.expiry > NOW()";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Link invalid sau expirat');
}

$company_data = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Date Companie - <?= htmlspecialchars($company_data['nume_companie']) ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- BoxIcons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="card shadow-sm">
            <?php if (!empty($company_data['logo_path'])): ?>
            <div class="card-img-top text-center py-3">
                <img src="<?= htmlspecialchars($company_data['logo_path']) ?>" 
                     alt="Logo companie" 
                     style="max-height: 100px;">
            </div>
            <?php endif; ?>

            <div class="card-body">
                <h2 class="card-title text-center mb-4">
                    <?= htmlspecialchars($company_data['nume_companie']) ?>
                </h2>

                <div class="row g-4">
                    <!-- Informații Generale -->
                    <div class="col-md-6">
                        <h5 class="border-bottom pb-2 mb-3">Informații Generale</h5>
                        <p><strong>CUI:</strong> <?= htmlspecialchars($company_data['cui']) ?></p>
                        <p><strong>Nr. Reg. Com.:</strong> <?= htmlspecialchars($company_data['nr_reg_com']) ?></p>
                        <p><strong>Cod Fiscal:</strong> <?= htmlspecialchars($company_data['cod_fiscal']) ?></p>
                        <p><strong>Activitate Principală:</strong> <?= htmlspecialchars($company_data['activitate_principala']) ?></p>
                    </div>

                    <!-- Contact -->
                    <div class="col-md-6">
                        <h5 class="border-bottom pb-2 mb-3">Contact</h5>
                        <p><strong>Telefon:</strong> <?= htmlspecialchars($company_data['telefon']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($company_data['email']) ?></p>
                        <p><strong>Website:</strong> <a href="<?= htmlspecialchars($company_data['website']) ?>" target="_blank"><?= htmlspecialchars($company_data['website']) ?></a></p>
                    </div>

                    <!-- Adresă -->
                    <div class="col-md-6">
                        <h5 class="border-bottom pb-2 mb-3">Adresă</h5>
                        <p><?= htmlspecialchars($company_data['adresa']) ?></p>
                        <p><?= htmlspecialchars($company_data['oras']) ?>, <?= htmlspecialchars($company_data['judet']) ?></p>
                        <p>Cod Poștal: <?= htmlspecialchars($company_data['cod_postal']) ?></p>
                        <p>Țara: <?= htmlspecialchars($company_data['tara']) ?></p>
                    </div>

                    <!-- Informații Bancare -->
                    <div class="col-md-6">
                        <h5 class="border-bottom pb-2 mb-3">Informații Bancare</h5>
                        <p><strong>Bancă:</strong> <?= htmlspecialchars($company_data['bank_name']) ?></p>
                        <p><strong>IBAN:</strong> <?= htmlspecialchars($company_data['bank_iban']) ?></p>
                        <p><strong>SWIFT:</strong> <?= htmlspecialchars($company_data['bank_swift']) ?></p>
                    </div>
                </div>

                <?php if (!empty($company_data['slogan'])): ?>
                <div class="mt-4 text-center">
                    <p class="fst-italic">"<?= htmlspecialchars($company_data['slogan']) ?>"</p>
                </div>
                <?php endif; ?>
            </div>

            <div class="card-footer text-center text-muted">
                <small>Informații valabile la data: <?= date('d.m.Y') ?></small>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>