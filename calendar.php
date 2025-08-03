<?php
session_start(); // ASIGURĂ-TE CĂ ACEASTA ESTE PRIMA LINIE DE COD PHP!
require_once 'db_connect.php'; // A doua linie
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

// Preluăm vehiculele și angajații pentru dropdown-uri în modal
$vehicule_list = [];
$stmt_vehicule = $conn->prepare("SELECT id, model, numar_inmatriculare, tip FROM vehicule ORDER BY model ASC");
if ($stmt_vehicule) {
    $stmt_vehicule->execute();
    $result_vehicule = $stmt_vehicule->get_result();
    while ($row = $result_vehicule->fetch_assoc()) {
        $vehicule_list[] = $row;
    }
    $stmt_vehicule->close();
}

$angajati_list = [];
$stmt_angajati = $conn->prepare("SELECT id, nume, prenume, functie FROM angajati ORDER BY nume ASC");
if ($stmt_angajati) {
    $stmt_angajati->execute();
    $result_angajati = $stmt_angajati->get_result();
    while ($row = $result_angajati->fetch_assoc()) {
        $angajati_list[] = $row;
    }
    $stmt_angajati->close();
}

$conn->close(); // Închidem conexiunea după preluarea datelor inițiale

// Tipuri de evenimente pentru dropdown-ul din modal
$event_types_available = ['Intalnire', 'Revizie', 'Concediu', 'Cursă', 'Rută', 'Document Expirat', 'Problema', 'Altele'];
?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | Calendar</title>

<!-- FullCalendar CSS -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />

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

    /* Stiluri specifice FullCalendar */
    .fc {
        font-family: '<?php echo $current_font_family; ?>', sans-serif !important;
    }
    .fc .fc-toolbar-title {
        color: #ffffff;
    }
    .fc .fc-button {
        background-color: #3b435a;
        color: #ffffff;
        border-color: rgba(255, 255, 255, 0.1);
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }
    .fc .fc-button:hover {
        background-color: #4a546e;
        border-color: rgba(255, 255, 255, 0.2);
    }
    .fc .fc-button-primary:not(:disabled).fc-button-active {
        background-color: var(--primary-accent-color);
        border-color: var(--primary-accent-color);
        color: #fff;
    }
    .fc .fc-daygrid-day-number {
        color: #e0e0e0;
    }
    .fc .fc-col-header-cell-cushion {
        color: #ffffff;
    }
    .fc .fc-daygrid-day.fc-day-today {
        background-color: rgba(13, 110, 253, 0.15); /* Light blue for today */
    }
    .fc-theme-standard .fc-scrollgrid {
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .fc-theme-standard td, .fc-theme-standard th {
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .fc-event {
        background-color: #0d6efd; /* Default event color */
        border-color: #0d6efd;
        color: #fff;
        border-radius: 0.3rem;
        font-size: 0.85em;
        padding: 2px 5px;
        margin-bottom: 2px;
        cursor: pointer;
    }
    /* Culori specifice pentru tipuri de evenimente */
    .fc-event-Intalnire { background-color: #28a745; border-color: #28a745; } /* Green */
    .fc-event-Revizie { background-color: #ffc107; border-color: #ffc107; color: #343a40; } /* Yellow */
    .fc-event-Concediu { background-color: #17a2b8; border-color: #17a2b8; } /* Cyan */
    .fc-event-Cursă { background-color: #6f42c1; border-color: #6f42c1; } /* Purple */
    .fc-event-Rută { background-color: #fd7e14; border-color: #fd7e14; } /* Orange */
    .fc-event-Document_Expirat { background-color: #dc3545; border-color: #dc3545; } /* Red */
    .fc-event-Problema { background-color: #e83e8c; border-color: #e83e8c; } /* Pink */
    .fc-event-Altele { background-color: #6c757d; border-color: #6c757d; } /* Grey */

    /* Stil pentru iconița mare pe dată */
    .fc-daygrid-day-events {
        position: relative;
    }
    .event-icon {
        font-size: 2.5em; /* Dimensiune mare */
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        opacity: 0.2; /* Transparent, pentru a nu acoperi textul */
        pointer-events: none; /* Nu interferează cu click-urile pe evenimente */
        color: var(--primary-accent-color); /* Culoare de accent */
    }
    .fc-daygrid-event-dot {
        display: none; /* Ascunde punctele mici de eveniment dacă folosim iconițe mari */
    }
    .fc-event-main-frame {
        padding: 2px 0; /* Ajustează padding-ul textului evenimentului */
    }
    .fc-event-title-wrap {
        white-space: normal; /* Permite titlurilor evenimentelor să se întindă pe mai multe rânduri */
    }
</style>

<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Calendar</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Calendar</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Calendar Evenimente</h4>
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

                        <div id='calendar'></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Modal Adaugă/Editează Eveniment -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalLabel">Adaugă Eveniment Nou</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="eventForm" action="process_calendar_event.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" id="eventAction" name="action" value="add">
                    <input type="hidden" id="eventId" name="id">
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="eventTitle" class="form-label">Titlu Eveniment:</label>
                            <input type="text" class="form-control" id="eventTitle" name="title" required>
                        </div>
                        <div class="col-12">
                            <label for="eventDescription" class="form-label">Descriere:</label>
                            <textarea class="form-control" id="eventDescription" name="description" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="eventStartDate" class="form-label">Dată/Ora Început:</label>
                            <input type="datetime-local" class="form-control" id="eventStartDate" name="start_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="eventEndDate" class="form-label">Dată/Ora Sfârșit (opțional):</label>
                            <input type="datetime-local" class="form-control" id="eventEndDate" name="end_date">
                        </div>
                        <div class="col-md-6">
                            <label for="eventType" class="form-label">Tip Eveniment:</label>
                            <select class="form-select" id="eventType" name="event_type" required>
                                <option value="">Selectează tipul</option>
                                <?php foreach ($event_types_available as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="eventSelectVehicul" class="form-label">Vehicul (opțional):</label>
                            <select class="form-select" id="eventSelectVehicul" name="id_vehicul">
                                <option value="">Fără vehicul</option>
                                <?php foreach ($vehicule_list as $veh): ?>
                                    <option value="<?php echo $veh['id']; ?>"><?php echo htmlspecialchars($veh['model'] . ' (' . $veh['numar_inmatriculare'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="eventSelectAngajat" class="form-label">Angajat (opțional):</label>
                            <select class="form-select" id="eventSelectAngajat" name="id_angajat">
                                <option value="">Fără angajat</option>
                                <?php foreach ($angajati_list as $angajat): ?>
                                    <option value="<?php echo $angajat['id']; ?>"><?php echo htmlspecialchars($angajat['nume'] . ' ' . $angajat['prenume']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="eventIsImportant" name="is_important">
                                <label class="form-check-label" for="eventIsImportant">
                                    Marchează ca Important
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Salvează Eveniment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmare Ștergere Eveniment -->
<div class="modal fade" id="deleteEventModal" tabindex="-1" aria-labelledby="deleteEventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteEventModalLabel">Confirmă Ștergerea Evenimentului</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi evenimentul <strong id="deleteEventTitle"></strong>? Această acțiune nu poate fi anulată.
                <input type="hidden" id="deleteEventId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteEventBtn">Șterge</button>
            </div>
        </div>
    </div>
</div>

<!-- FullCalendar JS -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/ro.js'></script> <!-- Limba română -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const eventModal = document.getElementById('eventModal');
    const eventForm = document.getElementById('eventForm');
    const deleteEventModal = document.getElementById('deleteEventModal');
    const confirmDeleteEventBtn = document.getElementById('confirmDeleteEventBtn');

    // Câmpuri din modal
    const eventActionInput = document.getElementById('eventAction');
    const eventIdInput = document.getElementById('eventId');
    const eventTitleInput = document.getElementById('eventTitle');
    const eventDescriptionInput = document.getElementById('eventDescription');
    const eventStartDateInput = document.getElementById('eventStartDate');
    const eventEndDateInput = document.getElementById('eventEndDate');
    const eventTypeSelect = document.getElementById('eventType');
    const eventSelectVehicul = document.getElementById('eventSelectVehicul');
    const eventSelectAngajat = document.getElementById('eventSelectAngajat');
    const eventIsImportantCheckbox = document.getElementById('eventIsImportant');

    // Mapări pentru acces rapid la datele vehiculelor/angajaților (dacă e nevoie în JS)
    const vehiculeMap = {};
    <?php foreach ($vehicule_list as $veh): ?>
        vehiculeMap[<?php echo $veh['id']; ?>] = { model: '<?php echo addslashes($veh['model']); ?>', numar_inmatriculare: '<?php echo addslashes($veh['numar_inmatriculare']); ?>', tip: '<?php echo addslashes($veh['tip']); ?>' };
    <?php endforeach; ?>

    const angajatiMap = {};
    <?php foreach ($angajati_list as $angajat): ?>
        angajatiMap[<?php echo $angajat['id']; ?>] = { nume: '<?php echo addslashes($angajat['nume']); ?>', prenume: '<?php echo addslashes($angajat['prenume']); ?>', functie: '<?php echo addslashes($angajat['functie']); ?>' };
    <?php endforeach; ?>


    // Inițializare FullCalendar
    const calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'ro', // Setează limba română
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        editable: true, // Permite drag-and-drop și resize
        selectable: true, // Permite selectarea intervalelor de timp
        eventStartEditable: true,
        eventDurationEditable: true,
        navLinks: true, // Permite navigarea la zi/săptămână la click pe numerele zilelor

        // Sursa de evenimente (va apela process_calendar_event.php cu action=fetch)
        events: function(fetchInfo, successCallback, failureCallback) {
            fetch('process_calendar_event.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=fetch&start=' + fetchInfo.startStr + '&end=' + fetchInfo.endStr
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    successCallback(data.events);
                } else {
                    console.error('Eroare la preluarea evenimentelor:', data.message);
                    failureCallback(data.message);
                }
            })
            .catch(error => {
                console.error('Eroare la fetch evenimente:', error);
                failureCallback(error);
            });
        },

        // Deschide modalul pentru a adăuga un eveniment nou la click pe o dată
        dateClick: function(info) {
            eventForm.reset();
            eventActionInput.value = 'add';
            eventIdInput.value = '';
            eventModalLabel.textContent = 'Adaugă Eveniment Nou';
            eventStartDateInput.value = info.dateStr.substring(0, 16); // Pre-populează data și ora
            eventEndDateInput.value = ''; // Golește data de sfârșit
            eventIsImportantCheckbox.checked = false; // Default not important
            eventTypeSelect.value = ''; // Resetează tipul
            eventSelectVehicul.value = ''; // Resetează vehiculul
            eventSelectAngajat.value = ''; // Resetează angajatul
            new bootstrap.Modal(eventModal).show();
        },

        // Deschide modalul pentru a edita un eveniment existent la click pe eveniment
        eventClick: function(info) {
            // Doar evenimentele din evenimente_calendar pot fi editate direct
            if (info.event.extendedProps.source_table === 'evenimente_calendar') {
                eventActionInput.value = 'edit';
                eventIdInput.value = info.event.id;
                eventModalLabel.textContent = 'Editează Eveniment';

                eventTitleInput.value = info.event.title;
                eventDescriptionInput.value = info.event.extendedProps.description || '';
                eventStartDateInput.value = info.event.start ? info.event.start.toISOString().substring(0, 16) : '';
                eventEndDateInput.value = info.event.end ? info.event.end.toISOString().substring(0, 16) : '';
                eventTypeSelect.value = info.event.extendedProps.event_type || '';
                eventSelectVehicul.value = info.event.extendedProps.id_vehicul || '';
                eventSelectAngajat.value = info.event.extendedProps.id_angajat || '';
                eventIsImportantCheckbox.checked = info.event.extendedProps.is_important;

                new bootstrap.Modal(eventModal).show();
            } else {
                // Pentru evenimente din alte tabele, afișăm un mesaj sau redirecționăm
                let redirectUrl = '';
                let message = `Acest eveniment provine din modulul '${info.event.extendedProps.source_table}'.`;
                
                switch (info.event.extendedProps.source_table) {
                    case 'curse_active':
                        redirectUrl = `curse-active.php?id=${info.event.id}`;
                        message += ` Poate fi editat de la pagina Curse Active. Vrei să mergi acolo?`;
                        break;
                    case 'planificare_rute':
                        redirectUrl = `planificare-rute.php?id=${info.event.id}`;
                        message += ` Poate fi editat de la pagina Planificare Rute. Vrei să mergi acolo?`;
                        break;
                    case 'concedii':
                        redirectUrl = `fise-individuale.php?id=${info.event.extendedProps.id_angajat}#contracts-tab`; // Exemplu
                        message += ` Poate fi editat de la fișa individuală a angajatului. Vrei să mergi acolo?`;
                        break;
                    case 'documente':
                        redirectUrl = `documente-vehicule.php?id=${info.event.extendedProps.id_vehicul}`;
                        message += ` Poate fi gestionat de la pagina Documente Vehicule. Vrei să mergi acolo?`;
                        break;
                    case 'probleme_raportate':
                        redirectUrl = `notificari-probleme-raportate.php`; // Sau o pagină specifică problemei
                        message += ` Poate fi gestionat de la pagina Probleme Raportate. Vrei să mergi acolo?`;
                        break;
                    default:
                        alert(message);
                        return;
                }

                if (confirm(message)) {
                    window.location.href = redirectUrl;
                }
            }
        },

        // Tratează drag-and-drop pentru evenimente
        eventDrop: function(info) {
            if (info.event.extendedProps.source_table === 'evenimente_calendar') {
                updateEvent(info.event);
            } else {
                info.revert(); // Anulează mișcarea dacă nu e un eveniment editabil
                alert('Acest tip de eveniment nu poate fi mutat direct din calendar. Editează-l din modulul său specific.');
            }
        },

        // Tratează redimensionarea evenimentelor
        eventResize: function(info) {
            if (info.event.extendedProps.source_table === 'evenimente_calendar') {
                updateEvent(info.event);
            } else {
                info.revert(); // Anulează redimensionarea
                alert('Acest tip de eveniment nu poate fi redimensionat direct din calendar. Editează-l din modulul său specific.');
            }
        },

        // Render custom content for events (for icons)
        eventContent: function(arg) {
            let iconHtml = '';
            let eventColorClass = `fc-event-${arg.event.extendedProps.event_type ? arg.event.extendedProps.event_type.replace(/ /g, '_') : 'Altele'}`;
            
            // Adaugă o clasă specifică pentru evenimentele importante
            if (arg.event.extendedProps.is_important) {
                eventColorClass += ' fc-event-important';
            }

            // Alege iconița în funcție de tipul evenimentului
            switch (arg.event.extendedProps.event_type) {
                case 'Intalnire': iconHtml = '<i class="bx bx-calendar-event"></i> '; break;
                case 'Revizie': iconHtml = '<i class="bx bxs-wrench"></i> '; break;
                case 'Concediu': iconHtml = '<i class="bx bxs-plane-alt"></i> '; break;
                case 'Cursă': iconHtml = '<i class="bx bxs-truck"></i> '; break;
                case 'Rută': iconHtml = '<i class="bx bx-map-alt"></i> '; break;
                case 'Document Expirat': iconHtml = '<i class="bx bxs-file-blank"></i> '; break;
                case 'Problema': iconHtml = '<i class="bx bx-error"></i> '; break;
                default: iconHtml = '<i class="bx bx-info-circle"></i> '; break;
            }

            // Returnează HTML-ul personalizat
            return { html: `<div class="${eventColorClass}">${iconHtml}${arg.event.title}</div>` };
        }
    });

    calendar.render();

    // Trimiterea formularului (Adaugă/Editează)
    eventForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(eventForm);

        // Convertim checkbox-ul is_important
        formData.set('is_important', eventIsImportantCheckbox.checked ? '1' : '0');

        fetch('process_calendar_event.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const modalInstance = bootstrap.Modal.getInstance(eventModal);
                if (modalInstance) { modalInstance.hide(); }
                calendar.refetchEvents(); // Reîncarcă evenimentele din calendar
                alert(data.message);
            } else {
                alert('Eroare: ' + data.message);
                console.error('Eroare la salvarea evenimentului:', data.message);
            }
        })
        .catch(error => {
            console.error('Eroare la salvarea evenimentului:', error);
            alert('A apărut o eroare la salvarea evenimentului.');
        });
    });

    // Deschide modalul de ștergere
    document.getElementById('deleteEventBtn').addEventListener('click', function() {
        const eventId = eventIdInput.value;
        const eventTitle = eventTitleInput.value;
        document.getElementById('deleteEventId').value = eventId;
        document.getElementById('deleteEventTitle').textContent = eventTitle;
        const deleteModalInstance = new bootstrap.Modal(deleteEventModal);
        deleteModalInstance.show();
    });

    // Confirmă ștergerea
    confirmDeleteEventBtn.addEventListener('click', function() {
        const eventIdToDelete = document.getElementById('deleteEventId').value;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', eventIdToDelete);

        fetch('process_calendar_event.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const modalInstance = bootstrap.Modal.getInstance(deleteEventModal);
                if (modalInstance) { modalInstance.hide(); }
                calendar.refetchEvents(); // Reîncarcă evenimentele din calendar
                alert(data.message);
            } else {
                alert('Eroare: ' + data.message);
                console.error('Eroare la ștergerea evenimentului:', data.message);
            }
        })
        .catch(error => {
            console.error('Eroare la ștergerea evenimentului:', error);
            alert('A apărut o eroare la ștergerea evenimentului.');
        });
    });

    // Fix pentru blocarea paginii după închiderea modalurilor (generic)
    const allModals = document.querySelectorAll('.modal');
    allModals.forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function () {
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        });
    });
});
</script>
