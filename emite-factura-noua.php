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

// Preluăm lista de clienți pentru dropdown
$clienti_list = [];
$stmt_clienti = $conn->prepare("SELECT id, nume_companie, persoana_contact, termen_plata FROM clienti ORDER BY nume_companie ASC");
if ($stmt_clienti) {
    $stmt_clienti->execute();
    $result_clienti = $stmt_clienti->get_result();
    while ($row = $result_clienti->fetch_assoc()) {
        $clienti_list[] = $row;
    }
    $stmt_clienti->close();
}
$conn->close();

// Statusuri pentru factură
$statusuri_factura = ['Emisa', 'Platita', 'Restanta', 'Anulata'];
?>

<title>NTS TOUR | Emite Factură Nouă</title>

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
    /* Stiluri specifice formularului de factură */
    .invoice-item-card {
        background-color: #3b435a;
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    .invoice-item-card .btn-close {
        filter: invert(1);
    }
    .total-section {
        font-size: 1.25rem;
        font-weight: bold;
        color: #ffffff;
    }
</style>

<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Contabilitate</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item"><a href="facturi-emise.php">Facturi Emise</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Emite Factură Nouă</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Emite Factură Nouă</h4>
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

                        <form id="emiteFacturaForm" action="process_facturi.php" method="POST">
                            <input type="hidden" name="action" value="add">
                            
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="selectClient" class="form-label">Client:</label>
                                    <select class="form-select" id="selectClient" name="id_client" required>
                                        <option value="">Alege un client</option>
                                        <?php foreach ($clienti_list as $client): ?>
                                            <option value="<?php echo $client['id']; ?>" data-termen-plata="<?php echo htmlspecialchars($client['termen_plata']); ?>"><?php echo htmlspecialchars($client['nume_companie'] . ' (' . $client['persoana_contact'] . ')'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="numarFactura" class="form-label">Număr Factură:</label>
                                    <input type="text" class="form-control" id="numarFactura" name="numar_factura" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="dataEmiterii" class="form-label">Dată Emitere:</label>
                                    <input type="date" class="form-control" id="dataEmiterii" name="data_emiterii" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="dataScadenta" class="form-label">Dată Scadență:</label>
                                    <input type="date" class="form-control" id="dataScadenta" name="data_scadenta" required>
                                </div>
                                <div class="col-12">
                                    <label for="observatii" class="form-label">Observații Generale:</label>
                                    <textarea class="form-control" id="observatii" name="observatii" rows="3"></textarea>
                                </div>
                            </div>

                            <h5 class="card-title mt-4">Articole Factură</h5>
                            <hr>
                            <div id="invoiceItemsContainer">
                                <!-- Articolele facturii vor fi adăugate aici dinamic -->
                                <div class="invoice-item-card" id="itemGroup_0">
                                    <h6 class="d-flex justify-content-between align-items-center">
                                        Articol 1
                                        <button type="button" class="btn-close text-white" aria-label="Șterge" onclick="removeInvoiceItem('itemGroup_0')"></button>
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="itemDescription_0" class="form-label">Descriere:</label>
                                            <input type="text" class="form-control" id="itemDescription_0" name="items[0][description]" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="itemQuantity_0" class="form-label">Cantitate:</label>
                                            <input type="number" step="1" class="form-control item-quantity" id="itemQuantity_0" name="items[0][quantity]" value="1" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="itemUnitPrice_0" class="form-label">Preț Unitar:</label>
                                            <input type="number" step="0.01" class="form-control item-unit-price" id="itemUnitPrice_0" name="items[0][unit_price]" value="0.00" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="itemVAT_0" class="form-label">TVA (%):</label>
                                            <input type="number" step="0.01" class="form-control item-vat" id="itemVAT_0" name="items[0][vat]" value="19" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Total Articol (cu TVA):</label>
                                            <p class="form-control-plaintext text-white item-total" id="itemTotal_0">0.00 RON</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn btn-info mt-3 mb-4" id="addInvoiceItemBtn">
                                <i class="bx bx-plus"></i> Adaugă Articol
                            </button>

                            <div class="d-flex justify-content-end align-items-center mb-4">
                                <span class="total-section me-3">Total Factură (cu TVA):</span>
                                <strong class="total-section" id="grandTotal">0.00 RON</strong>
                                <input type="hidden" name="valoare_totala" id="hiddenGrandTotal">
                                <input type="hidden" name="moneda" value="RON"> <!-- Moneda fixă pentru factură -->
                                <input type="hidden" name="status" value="Emisa"> <!-- Status implicit factură -->
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2">
                                <a href="facturi-emise.php" class="btn btn-secondary">Anulează</a>
                                <button type="submit" class="btn btn-primary" id="saveInvoiceBtn">Salvează Factura</button>
                                <button type="button" class="btn btn-success" id="generateInvoicePdfBtn"><i class="bx bxs-file-pdf"></i> Salvează & Generează PDF</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let itemCounter = 1;

    const invoiceItemsContainer = document.getElementById('invoiceItemsContainer');
    const addInvoiceItemBtn = document.getElementById('addInvoiceItemBtn');
    const emiteFacturaForm = document.getElementById('emiteFacturaForm');
    const grandTotalElement = document.getElementById('grandTotal');
    const hiddenGrandTotalInput = document.getElementById('hiddenGrandTotal');
    const saveInvoiceBtn = document.getElementById('saveInvoiceBtn');
    const generateInvoicePdfBtn = document.getElementById('generateInvoicePdfBtn');
    const selectClient = document.getElementById('selectClient');
    const dataEmiterii = document.getElementById('dataEmiterii');
    const dataScadenta = document.getElementById('dataScadenta');

    // Funcție pentru calcularea totalului unui articol
    function calculateItemTotal(group) {
        const quantity = parseFloat(group.querySelector('.item-quantity').value) || 0;
        const unitPrice = parseFloat(group.querySelector('.item-unit-price').value) || 0;
        const vat = parseFloat(group.querySelector('.item-vat').value) || 0;

        const subtotal = quantity * unitPrice;
        const totalWithVat = subtotal * (1 + vat / 100);
        group.querySelector('.item-total').textContent = totalWithVat.toFixed(2) + ' RON';
        return totalWithVat;
    }

    // Funcție pentru calcularea totalului general al facturii
    function calculateGrandTotal() {
        let grandTotal = 0;
        document.querySelectorAll('.invoice-item-card').forEach(group => {
            grandTotal += calculateItemTotal(group);
        });
        grandTotalElement.textContent = grandTotal.toFixed(2) + ' RON';
        hiddenGrandTotalInput.value = grandTotal.toFixed(2);
    }

    // Adaugă event listeners pentru calcul la modificarea câmpurilor articolului
    invoiceItemsContainer.addEventListener('input', function(e) {
        if (e.target.classList.contains('item-quantity') || e.target.classList.contains('item-unit-price') || e.target.classList.contains('item-vat')) {
            const group = e.target.closest('.invoice-item-card');
            calculateItemTotal(group);
            calculateGrandTotal();
        }
    });

    // Adaugă primul articol la încărcarea paginii
    calculateGrandTotal();

    // Adaugă mai multe articole
    addInvoiceItemBtn.addEventListener('click', function() {
        const newItemGroup = document.createElement('div');
        newItemGroup.classList.add('invoice-item-card');
        newItemGroup.id = `itemGroup_${itemCounter}`;
        newItemGroup.innerHTML = `
            <h6 class="d-flex justify-content-between align-items-center">
                Articol ${itemCounter + 1}
                <button type="button" class="btn-close text-white" aria-label="Șterge" onclick="removeInvoiceItem('itemGroup_${itemCounter}')"></button>
            </h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="itemDescription_${itemCounter}" class="form-label">Descriere:</label>
                    <input type="text" class="form-control" id="itemDescription_${itemCounter}" name="items[${itemCounter}][description]" required>
                </div>
                <div class="col-md-3">
                    <label for="itemQuantity_${itemCounter}" class="form-label">Cantitate:</label>
                    <input type="number" step="1" class="form-control item-quantity" id="itemQuantity_${itemCounter}" name="items[${itemCounter}][quantity]" value="1" required>
                </div>
                <div class="col-md-3">
                    <label for="itemUnitPrice_${itemCounter}" class="form-label">Preț Unitar:</label>
                    <input type="number" step="0.01" class="form-control item-unit-price" id="itemUnitPrice_${itemCounter}" name="items[${itemCounter}][unit_price]" value="0.00" required>
                </div>
                <div class="col-md-6">
                    <label for="itemVAT_${itemCounter}" class="form-label">TVA (%):</label>
                    <input type="number" step="0.01" class="form-control item-vat" id="itemVAT_${itemCounter}" name="items[${itemCounter}][vat]" value="19" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Total Articol (cu TVA):</label>
                    <p class="form-control-plaintext text-white item-total" id="itemTotal_${itemCounter}">0.00 RON</p>
                </div>
            </div>
        `;
        invoiceItemsContainer.appendChild(newItemGroup);
        itemCounter++;
        calculateGrandTotal(); // Recalculează totalul general după adăugarea unui nou articol
    });

    // Funcție globală pentru ștergerea articolelor de factură
    window.removeInvoiceItem = function(groupId) {
        const groupToRemove = document.getElementById(groupId);
        if (groupToRemove) {
            groupToRemove.remove();
            calculateGrandTotal(); // Recalculează totalul general după ștergere
        }
    };

    // Setează data emiterii la data curentă implicit
    const now = new Date();
    const formattedDate = now.toISOString().substring(0, 10);
    dataEmiterii.value = formattedDate;

    // Calculează data scadenței pe baza termenului de plată al clientului
    selectClient.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const termenPlata = parseInt(selectedOption.getAttribute('data-termen-plata')) || 30; // Implicit 30 zile
        
        const emitereDate = new Date(dataEmiterii.value);
        if (!isNaN(emitereDate)) {
            emitereDate.setDate(emitereDate.getDate() + termenPlata);
            dataScadenta.value = emitereDate.toISOString().substring(0, 10);
        }
    });
    // Trigger la încărcare pentru clientul selectat inițial (dacă există)
    selectClient.dispatchEvent(new Event('change'));


    // Gestionarea butonului "Salvează & Generează PDF"
    generateInvoicePdfBtn.addEventListener('click', function() {
        document.getElementById('emiteFacturaForm').action = 'process_facturi.php?generate_pdf=true';
        document.getElementById('emiteFacturaForm').submit();
    });

    // Asigură că acțiunea corectă este folosită pentru salvarea normală
    saveInvoiceBtn.addEventListener('click', function() {
        document.getElementById('emiteFacturaForm').action = 'process_facturi.php';
    });
});
</script>
