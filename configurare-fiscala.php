<?php
session_start();
require_once 'db_connect.php';
require_once 'template/header.php';

// Verifică autentificarea
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Mesaje de succes sau eroare din sesiune
$success_message = '';
$error_message = '';

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Preluăm configurațiile fiscale existente (ar trebui să fie un singur rând cu id=1)
$configurari_fiscale = [
    'cota_tva' => 19.00,
    'moneda_implicita' => 'RON'
];

$sql_config = "SELECT * FROM configurari_fiscale WHERE id = 1";
$result_config = $conn->query($sql_config);
if ($result_config && $result_config->num_rows > 0) {
    $configurari_fiscale = array_merge($configurari_fiscale, $result_config->fetch_assoc());
}

// Preluăm lista de conturi bancare
$conturi_bancare_list = [];
$sql_conturi = "SELECT * FROM conturi_bancare WHERE id_companie = 1 ORDER BY nume_banca ASC";
$result_conturi = $conn->query($sql_conturi);
if ($result_conturi) {
    while ($row = $result_conturi->fetch_assoc()) {
        $conturi_bancare_list[] = $row;
    }
}
$conn->close();

// Monede disponibile (extindeți după nevoi)
$monede_disponibile = ['RON', 'EUR', 'USD', 'GBP'];
?>

<title>NTS TOUR | Configurări Fiscale</title>

<style>
    /* Stiluri generale preluate din tema */
    body, html, .main-content {
        color: #ffffff !important;
    }
    .text-muted, .text-secondary {
        color: #e0e0e0 !important;
    }
    h1, h2, h3, h4, h5, h6 {
        color: #ffffff !important;
    }
    p, span, strong, label, small {
        color: #ffffff !important;
    }
    .card {
        background-color: #2a3042 !important;
        color: #e0e0e0 !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.75rem !important;
    }
    .card-header, .modal-header, .modal-footer {
        background-color: #3b435a !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: #ffffff !important;
    }
    .modal-content {
        background-color: #2a3042 !important;
        color: #e0e0e0 !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.75rem !important;
    }
    .modal-title {
        color: #ffffff !important;
    }
    .btn-close {
        filter: invert(1);
    }
    .form-label {
        color: #e0e0e0 !important;
    }
    .form-control, .form-select, .form-check-label {
        background-color: #1a2035 !important;
        color: #e0e0e0 !important;
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        border-radius: 0.5rem !important;
    }
    .form-control::placeholder {
        color: #b0b0b0 !important;
        opacity: 0.7 !important;
    }
    .form-control:focus, .form-select:focus {
        border-color: #6a90f1 !important;
        box-shadow: 0 0 0 0.25rem rgba(106, 144, 241, 0.25) !important;
    }
    .form-check-input:checked {
        background-color: #0d6efd !important;
        border-color: #0d6efd !important;
    }
    hr {
        border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
    }
    .btn-primary, .btn-secondary, .btn-danger, .btn-info, .btn-warning, .btn-success, .btn-outline-primary, .btn-outline-danger {
        border-radius: 0.5rem !important;
        padding: 0.75rem 1.5rem !important;
        font-weight: bold !important;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out !important;
    }
    .btn-primary:hover, .btn-secondary:hover, .btn-danger:hover, .btn-info:hover, .btn-warning:hover, .btn-success:hover, .btn-outline-primary:hover, .btn-outline-danger:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2) !important;
    }
    .alert {
        color: #ffffff !important;
    }
    .alert-info {
        background-color: #203354 !important;
        border-color: #4285f4 !important;
    }
    .alert-success {
        background-color: #2c5234 !important;
        border-color: #4caf50 !important;
    }
    .alert-danger {
        background-color: #5c2c31 !important;
        border-color: #f44336 !important;
    }
    .alert-warning {
        background-color: #6a5300 !important;
        border-color: #ffc107 !important;
    }

    /* Stiluri specifice pentru tabelul de conturi bancare */
    .table {
        color: #e0e0e0 !important;
        background-color: #2a3042 !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
    .table th, .table td {
        border-color: rgba(255, 255, 255, 0.1) !important;
        vertical-align: middle;
    }
    .table thead th {
        background-color: #3b435a !important;
        color: #ffffff !important;
        font-weight: bold;
    }
    .table tbody tr:hover {
        background-color: #3b435a !important;
    }
    /* Responsive adjustments for table */
    @media (max-width: 767.98px) {
        .table-responsive {
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
        }
        .table {
            width: 100%;
            margin-bottom: 0;
        }
        .table thead {
            display: none;
        }
        .table tbody tr {
            display: block;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            padding: 0.75rem;
        }
        .table tbody td {
            display: block;
            text-align: right;
            padding-left: 50% !important;
            position: relative;
            border: none;
        }
        .table tbody td::before {
            content: attr(data-label);
            position: absolute;
            left: 0;
            width: 50%;
            padding-left: 1rem;
            font-weight: bold;
            text-align: left;
            color: #b0b0b0;
        }
        .table tbody td:last-child {
            border-bottom: none;
        }
        .table .btn {
            width: 100%;
            margin-top: 0.5rem;
        }
    }
</style>

<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Setări</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Configurare TVA și Monede</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Configurări Fiscale Generale</h4>
                        <hr>

                        <?php if ($success_message): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $success_message; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>

                        <form id="fiscalConfigForm" action="process_configurari_fiscale.php" method="POST">
                            <input type="hidden" name="action" value="save">
                            <input type="hidden" name="id" value="1"> <!-- ID-ul este întotdeauna 1 pentru acest tabel -->
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="cotaTVA" class="form-label">Cotă TVA Implicită (%):</label>
                                    <input type="number" step="0.01" class="form-control" id="cotaTVA" name="cota_tva" value="<?php echo htmlspecialchars($configurari_fiscale['cota_tva']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="monedaImplicita" class="form-label">Monedă Implicită:</label>
                                    <select class="form-select" id="monedaImplicita" name="moneda_implicita" required>
                                        <?php foreach ($monede_disponibile as $moneda): ?>
                                            <option value="<?php echo htmlspecialchars($moneda); ?>" <?php if ($configurari_fiscale['moneda_implicita'] == $moneda) echo 'selected'; ?>><?php echo htmlspecialchars($moneda); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <button type="submit" class="btn btn-primary">Salvează Configurări Fiscale</button>
                            </div>
                        </form>

                        <h4 class="card-title mt-5">Conturi Bancare</h4>
                        <hr>
                        <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addEditBankAccountModal" id="addBankAccountBtn">
                            <i class="bx bx-plus"></i> Adaugă Cont Bancar Nou
                        </button>

                        <?php if (empty($conturi_bancare_list)): ?>
                            <div class="alert alert-info">Nu există conturi bancare înregistrate.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nume Bancă</th>
                                            <th>IBAN</th>
                                            <th>SWIFT/BIC</th>
                                            <th>Monedă</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bankAccountsTableBody">
                                        <?php foreach ($conturi_bancare_list as $cont): ?>
                                            <tr 
                                                data-id="<?php echo $cont['id']; ?>"
                                                data-nume-banca="<?php echo htmlspecialchars($cont['nume_banca']); ?>"
                                                data-iban="<?php echo htmlspecialchars($cont['iban']); ?>"
                                                data-swift="<?php echo htmlspecialchars($cont['swift']); ?>"
                                                data-adresa-banca="<?php echo htmlspecialchars($cont['adresa_banca']); ?>"
                                                data-moneda="<?php echo htmlspecialchars($cont['moneda']); ?>"
                                            >
                                                <td data-label="Nume Bancă:"><?php echo htmlspecialchars($cont['nume_banca']); ?></td>
                                                <td data-label="IBAN:"><?php echo htmlspecialchars($cont['iban']); ?></td>
                                                <td data-label="SWIFT/BIC:"><?php echo htmlspecialchars($cont['swift'] ?? 'N/A'); ?></td>
                                                <td data-label="Monedă:"><?php echo htmlspecialchars($cont['moneda']); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-bank-account-btn" data-bs-toggle="modal" data-bs-target="#addEditBankAccountModal">Editează</button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-bank-account-btn" data-id="<?php echo $cont['id']; ?>">Șterge</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Modal Adaugă/Editează Cont Bancar -->
<div class="modal fade" id="addEditBankAccountModal" tabindex="-1" aria-labelledby="addEditBankAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEditBankAccountModalLabel">Adaugă Cont Bancar Nou</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="bankAccountForm" action="process_conturi_bancare.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" id="bankAccountAction" name="action" value="add">
                    <input type="hidden" id="bankAccountId" name="id">
                    <input type="hidden" name="id_companie" value="1"> <!-- Se leagă la compania cu ID 1 -->
                    
                    <div class="mb-3">
                        <label for="modalNumeBanca" class="form-label">Nume Bancă:</label>
                        <input type="text" class="form-control" id="modalNumeBanca" name="nume_banca" required>
                    </div>
                    <div class="mb-3">
                        <label for="modalIBAN" class="form-label">IBAN:</label>
                        <input type="text" class="form-control" id="modalIBAN" name="iban" required>
                    </div>
                    <div class="mb-3">
                        <label for="modalSWIFT" class="form-label">SWIFT/BIC:</label>
                        <input type="text" class="form-control" id="modalSWIFT" name="swift">
                    </div>
                    <div class="mb-3">
                        <label for="modalAdresaBanca" class="form-label">Adresă Bancă:</label>
                        <textarea class="form-control" id="modalAdresaBanca" name="adresa_banca" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="modalMonedaCont" class="form-label">Monedă Cont:</label>
                        <select class="form-select" id="modalMonedaCont" name="moneda" required>
                            <?php foreach ($monede_disponibile as $moneda): ?>
                                <option value="<?php echo htmlspecialchars($moneda); ?>"><?php echo htmlspecialchars($moneda); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Salvează Cont Bancar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmare Ștergere Cont Bancar -->
<div class="modal fade" id="deleteBankAccountModal" tabindex="-1" aria-labelledby="deleteBankAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteBankAccountModalLabel">Confirmă Ștergerea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi acest cont bancar? Această acțiune nu poate fi anulată.
                <input type="hidden" id="deleteBankAccountId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBankAccountBtn">Șterge</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addEditBankAccountModal = document.getElementById('addEditBankAccountModal');
    const bankAccountForm = document.getElementById('bankAccountForm');
    const addBankAccountBtn = document.getElementById('addBankAccountBtn');
    const deleteBankAccountModal = document.getElementById('deleteBankAccountModal');
    const confirmDeleteBankAccountBtn = document.getElementById('confirmDeleteBankAccountBtn');
    const bankAccountsTableBody = document.getElementById('bankAccountsTableBody');

    // Deschide modalul pentru adăugare cont bancar
    addBankAccountBtn.addEventListener('click', function() {
        bankAccountForm.reset();
        document.getElementById('bankAccountAction').value = 'add';
        document.getElementById('bankAccountId').value = '';
        document.getElementById('addEditBankAccountModalLabel').textContent = 'Adaugă Cont Bancar Nou';
        document.getElementById('modalMonedaCont').value = 'RON'; // Setează moneda implicită
    });

    // Deschide modalul pentru editare cont bancar
    bankAccountsTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-bank-account-btn')) {
            const row = e.target.closest('tr');
            document.getElementById('bankAccountAction').value = 'edit';
            document.getElementById('bankAccountId').value = row.getAttribute('data-id');
            document.getElementById('addEditBankAccountModalLabel').textContent = 'Editează Cont Bancar';

            document.getElementById('modalNumeBanca').value = row.getAttribute('data-nume-banca');
            document.getElementById('modalIBAN').value = row.getAttribute('data-iban');
            document.getElementById('modalSWIFT').value = row.getAttribute('data-swift');
            document.getElementById('modalAdresaBanca').value = row.getAttribute('data-adresa-banca');
            document.getElementById('modalMonedaCont').value = row.getAttribute('data-moneda');
        }
    });

    // Trimiterea formularului (Adaugă/Editează Cont Bancar)
    bankAccountForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(bankAccountForm);

        fetch('process_conturi_bancare.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data); // Pentru depanare
            const modalInstance = bootstrap.Modal.getInstance(addEditBankAccountModal);
            if (modalInstance) {
                modalInstance.hide();
            }
            location.reload(); // Reîncarcă pagina pentru a vedea modificările
        })
        .catch(error => {
            console.error('Eroare la salvarea contului bancar:', error);
            alert('A apărut o eroare la salvarea contului bancar.');
        });
    });

    // Ștergerea contului bancar
    bankAccountsTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-bank-account-btn')) {
            const bankAccountIdToDelete = e.target.getAttribute('data-id');
            document.getElementById('deleteBankAccountId').value = bankAccountIdToDelete;
            const deleteModalInstance = new bootstrap.Modal(deleteBankAccountModal);
            deleteModalInstance.show();
        }
    });

    confirmDeleteBankAccountBtn.addEventListener('click', function() {
        const bankAccountIdToDelete = document.getElementById('deleteBankAccountId').value;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', bankAccountIdToDelete);

        fetch('process_conturi_bancare.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data);
            const modalInstance = bootstrap.Modal.getInstance(deleteBankAccountModal);
            if (modalInstance) {
                modalInstance.hide();
            }
            location.reload();
        })
        .catch(error => {
            console.error('Eroare la ștergerea contului bancar:', error);
            alert('A apărut o eroare la ștergerea contului bancar.');
        });
    });
});
</script>
