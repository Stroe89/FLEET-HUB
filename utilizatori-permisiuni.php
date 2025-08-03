<?php
session_start();
require_once 'db_connect.php';
require_once 'template/header.php';

// Verifică autentificarea și permisiunile (doar administratorii pot gestiona utilizatori)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Administrator') {
    // Redirecționează sau afișează un mesaj de eroare
    header("Location: index.php"); // Sau o pagină de "acces refuzat"
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

// Preluăm lista de roluri pentru dropdown-uri
$roluri_list = [];
$sql_roluri = "SELECT id, nume_rol FROM roluri_utilizatori ORDER BY nume_rol ASC";
$result_roluri = $conn->query($sql_roluri);
if ($result_roluri) {
    while ($row = $result_roluri->fetch_assoc()) {
        $roluri_list[] = $row;
    }
}

// Preluăm lista de utilizatori
$utilizatori_list = [];
$sql_utilizatori = "
    SELECT u.id, u.username, r.nume_rol
    FROM utilizatori u
    JOIN roluri_utilizatori r ON u.id_rol = r.id
    ORDER BY u.username ASC
";
$result_utilizatori = $conn->query($sql_utilizatori);
if ($result_utilizatori) {
    while ($row = $result_utilizatori->fetch_assoc()) {
        $utilizatori_list[] = $row;
    }
}
$conn->close();
?>

<title>NTS TOUR | Utilizatori & Permisiuni</title>

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

    /* Stiluri specifice pentru tabelul de utilizatori */
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
    .badge-role {
        background-color: #0d6efd !important; /* Culoare implicită pentru rol */
        color: #fff !important;
    }
    /* Culori specifice pentru roluri */
    .badge-role-Administrator { background-color: #dc3545 !important; }
    .badge-role-Manager_Flota { background-color: #ffc107 !important; color: #343a40 !important; }
    .badge-role-Sofer { background-color: #28a745 !important; }

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
                        <li class="breadcrumb-item active" aria-current="page">Utilizatori & Permisiuni</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Gestionare Utilizatori</h4>
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

                        <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addEditUserModal" id="addUserBtn">
                            <i class="bx bx-user-plus"></i> Adaugă Utilizator Nou
                        </button>

                        <!-- Secțiunea de Filtrare -->
                        <div class="row mb-4 filter-section">
                            <div class="col-md-4 mb-3">
                                <label for="filterRole" class="form-label">Filtrează după Rol:</label>
                                <select class="form-select" id="filterRole">
                                    <option value="all">Toate Rolurile</option>
                                    <?php foreach ($roluri_list as $rol): ?>
                                        <option value="<?php echo htmlspecialchars($rol['nume_rol']); ?>"><?php echo htmlspecialchars($rol['nume_rol']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label for="filterSearch" class="form-label">Caută:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                                    <input type="text" class="form-control" id="filterSearch" placeholder="Cauta nume utilizator...">
                                </div>
                            </div>
                        </div>

                        <!-- Lista Utilizatorilor -->
                        <?php if (empty($utilizatori_list)): ?>
                            <div class="alert alert-info">Nu există utilizatori înregistrați.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nume Utilizator</th>
                                            <th>Rol</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="usersTableBody">
                                        <?php foreach ($utilizatori_list as $user): ?>
                                            <tr 
                                                data-id="<?php echo $user['id']; ?>"
                                                data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                data-id-rol="<?php echo htmlspecialchars($user['id_rol']); ?>"
                                                data-nume-rol="<?php echo htmlspecialchars($user['nume_rol']); ?>"
                                                data-search-text="<?php echo strtolower(htmlspecialchars($user['username'] . ' ' . $user['nume_rol'])); ?>"
                                            >
                                                <td data-label="ID:"><?php echo htmlspecialchars($user['id']); ?></td>
                                                <td data-label="Nume Utilizator:"><?php echo htmlspecialchars($user['username']); ?></td>
                                                <td data-label="Rol:"><span class="badge badge-role badge-role-<?php echo str_replace(' ', '_', htmlspecialchars($user['nume_rol'])); ?>"><?php echo htmlspecialchars($user['nume_rol']); ?></span></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-user-btn" data-bs-toggle="modal" data-bs-target="#addEditUserModal">Editează</button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-user-btn" data-id="<?php echo $user['id']; ?>">Șterge</button>
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

<!-- Modal Adaugă/Editează Utilizator -->
<div class="modal fade" id="addEditUserModal" tabindex="-1" aria-labelledby="addEditUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEditUserModalLabel">Adaugă Utilizator Nou</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="userForm" action="process_utilizatori.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" id="userAction" name="action" value="add">
                    <input type="hidden" id="userId" name="id">
                    
                    <div class="mb-3">
                        <label for="modalUsername" class="form-label">Nume Utilizator:</label>
                        <input type="text" class="form-control" id="modalUsername" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="modalPassword" class="form-label">Parolă (lasă gol pentru a nu schimba la editare):</label>
                        <input type="password" class="form-control" id="modalPassword" name="password">
                    </div>
                    <div class="mb-3">
                        <label for="modalRole" class="form-label">Rol:</label>
                        <select class="form-select" id="modalRole" name="id_rol" required>
                            <option value="">Selectează Rolul</option>
                            <?php foreach ($roluri_list as $rol): ?>
                                <option value="<?php echo htmlspecialchars($rol['id']); ?>"><?php echo htmlspecialchars($rol['nume_rol']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Salvează Utilizator</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmare Ștergere Utilizator -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteUserModalLabel">Confirmă Ștergerea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi acest utilizator? Această acțiune nu poate fi anulată.
                <input type="hidden" id="deleteUserId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteUserBtn">Șterge</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addEditUserModal = document.getElementById('addEditUserModal');
    const userForm = document.getElementById('userForm');
    const addUserBtn = document.getElementById('addUserBtn');
    const deleteUserModal = document.getElementById('deleteUserModal');
    const confirmDeleteUserBtn = document.getElementById('confirmDeleteUserBtn');
    const usersTableBody = document.getElementById('usersTableBody');

    // Filtrare
    const filterRole = document.getElementById('filterRole');
    const filterSearch = document.getElementById('filterSearch');

    function filterTable() {
        const selectedRole = filterRole.value;
        const searchText = filterSearch.value.toLowerCase().trim();

        document.querySelectorAll('#usersTableBody tr').forEach(row => {
            const rowRole = row.getAttribute('data-nume-rol');
            const rowSearchText = row.getAttribute('data-search-text');

            const roleMatch = (selectedRole === 'all' || rowRole === selectedRole);
            const searchMatch = (searchText === '' || rowSearchText.includes(searchText));

            if (roleMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    filterRole.addEventListener('change', filterTable);
    filterSearch.addEventListener('input', filterTable);


    // Deschide modalul pentru adăugare
    addUserBtn.addEventListener('click', function() {
        userForm.reset();
        document.getElementById('userAction').value = 'add';
        document.getElementById('userId').value = '';
        document.getElementById('addEditUserModalLabel').textContent = 'Adaugă Utilizator Nou';
        document.getElementById('modalPassword').setAttribute('required', 'required'); // Parola este obligatorie la adăugare
    });

    // Deschide modalul pentru editare
    usersTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-user-btn')) {
            const row = e.target.closest('tr');
            document.getElementById('userAction').value = 'edit';
            document.getElementById('userId').value = row.getAttribute('data-id');
            document.getElementById('addEditUserModalLabel').textContent = 'Editează Utilizator';

            document.getElementById('modalUsername').value = row.getAttribute('data-username');
            document.getElementById('modalRole').value = row.getAttribute('data-id-rol');
            document.getElementById('modalPassword').removeAttribute('required'); // Parola nu este obligatorie la editare
            document.getElementById('modalPassword').value = ''; // Golește câmpul de parolă
        }
    });

    // Trimiterea formularului (Adaugă/Editează)
    userForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(userForm);

        fetch('process_utilizatori.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data); // Pentru depanare
            const modalInstance = bootstrap.Modal.getInstance(addEditUserModal);
            if (modalInstance) {
                modalInstance.hide();
            }
            location.reload(); // Reîncarcă pagina pentru a vedea modificările
        })
        .catch(error => {
            console.error('Eroare la salvarea utilizatorului:', error);
            alert('A apărut o eroare la salvarea utilizatorului.');
        });
    });

    // Ștergerea utilizatorului
    usersTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-user-btn')) {
            const userIdToDelete = e.target.getAttribute('data-id');
            document.getElementById('deleteUserId').value = userIdToDelete;
            const deleteModalInstance = new bootstrap.Modal(deleteUserModal);
            deleteModalInstance.show();
        }
    });

    confirmDeleteUserBtn.addEventListener('click', function() {
        const userIdToDelete = document.getElementById('deleteUserId').value;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', userIdToDelete);

        fetch('process_utilizatori.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data);
            const modalInstance = bootstrap.Modal.getInstance(deleteUserModal);
            if (modalInstance) {
                modalInstance.hide();
            }
            location.reload();
        })
        .catch(error => {
            console.error('Eroare la ștergerea utilizatorului:', error);
            alert('A apărut o eroare la ștergerea utilizatorului.');
        });
    });
});
</script>
