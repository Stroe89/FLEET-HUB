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

// Funcție ajutătoare pentru a verifica existența unui tabel
function tableExists($conn, $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($tableName) . "'");
    return $result && $result->num_rows > 0;
}

// --- Preluare Setări Conturi Social Media ---
$social_accounts = [];
$platforms = ['facebook', 'instagram', 'tiktok', 'whatsapp', 'telegram'];

if (tableExists($conn, 'social_accounts_settings')) {
    $sql_settings = "SELECT platform_name, api_key, api_secret, access_token, is_connected, connection_status_message FROM social_accounts_settings";
    $result_settings = $conn->query($sql_settings);
    if ($result_settings) {
        while ($row = $result_settings->fetch_assoc()) {
            $social_accounts[$row['platform_name']] = $row;
        }
    }
}

// Asigură că toate platformele au un entry, chiar dacă nu sunt în DB (pentru a afișa formularul)
foreach ($platforms as $platform) {
    if (!isset($social_accounts[$platform])) {
        $social_accounts[$platform] = [
            'platform_name' => $platform,
            'api_key' => null,
            'api_secret' => null,
            'access_token' => null,
            'is_connected' => false,
            'connection_status_message' => 'Nu este configurat'
        ];
    }
}

$conn->close();
?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | Setări Conturi Social Media</title>

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
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.2);
    }
    .card-header, .modal-header, .modal-footer {
        background-color: #3b435a !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: #ffffff !important;
    }
    .card-title {
        color: #ffffff !important;
    }
    hr {
        border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
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
    .alert {
        color: #ffffff !important;
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.2);
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

    /* Stiluri specifice pentru integrare social media */
    .platform-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .platform-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.3);
    }
    .platform-card .card-body {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    .platform-icon {
        font-size: 3.5rem;
        margin-bottom: 15px;
        color: #fff; /* Culoare implicită, va fi suprascrisă */
    }
    .platform-name {
        font-weight: bold;
        font-size: 1.2rem;
        margin-bottom: 10px;
    }
    .connection-status {
        font-size: 0.9em;
        font-weight: bold;
    }
    .status-connected { color: #28a745; }
    .status-disconnected { color: #dc3545; }
    .status-not-configured { color: #ffc107; }

    /* Iconițe specifice platformelor */
    .icon-facebook { color: #1877F2; }
    .icon-instagram { color: #E4405F; }
    .icon-tiktok { color: #000; filter: invert(1); } /* TikTok logo is often black, invert for dark theme */
    .icon-whatsapp { color: #25D366; }
    .icon-telegram { color: #0088CC; }

    /* Stiluri pentru butoane */
    .btn-primary, .btn-secondary, .btn-danger, .btn-info, .btn-warning, .btn-success, .btn-outline-primary, .btn-outline-danger {
        font-weight: bold !important;
        padding: 0.75rem 1.5rem !important;
        border-radius: 0.5rem !important;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out !important;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.2);
    }
    .btn-primary:hover, .btn-secondary:hover, .btn-danger:hover, .btn-info:hover, .btn-warning:hover, .btn-success:hover, .btn-outline-primary:hover, .btn-outline-danger:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.3);
    }
    .btn-primary { background-color: #007bff !important; border-color: #007bff !important; color: #fff !important; }
    .btn-info { background-color: #17a2b8 !important; border-color: #17a2b8 !important; color: #fff !important; }
    .btn-warning { background-color: #ffc107 !important; border-color: #ffc107 !important; color: #343a40 !important; }
    .btn-success { background-color: #28a745 !important; border-color: #28a745 !important; color: #fff !important; }
    .btn-secondary { background-color: #6c757d !important; border-color: #6c757d !important; color: #fff !important; }

    /* Responsive adjustments */
    @media (max-width: 767.98px) {
        .platform-card .card-body {
            padding: 1rem;
        }
        .platform-icon {
            font-size: 3rem;
        }
        .platform-name {
            font-size: 1rem;
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
                    <li class="breadcrumb-item active" aria-current="page">Setări Conturi Social Media</li>
                </ol>
                </nav>
            </div>
        </div>
        
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

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Integrare Conturi Social Media</h4>
                        <p class="text-muted">Conectează-ți platformele de social media pentru a programa și publica postări direct din NTS TOUR.</p>
                        <hr>

                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                            <?php foreach ($platforms as $platform_name): 
                                $platform_settings = $social_accounts[$platform_name];
                                $icon_class = '';
                                $platform_display_name = ucfirst($platform_name);
                                $instructions_url = '#'; // Placeholder for real instructions

                                switch ($platform_name) {
                                    case 'facebook':
                                        $icon_class = 'bxl-facebook-square icon-facebook';
                                        $platform_display_name = 'Facebook';
                                        $instructions_url = 'https://developers.facebook.com/docs/apps/register/'; // Exemplu
                                        break;
                                    case 'instagram':
                                        $icon_class = 'bxl-instagram icon-instagram';
                                        $platform_display_name = 'Instagram';
                                        $instructions_url = 'https://developers.facebook.com/docs/instagram/'; // Exemplu
                                        break;
                                    case 'tiktok':
                                        $icon_class = 'bxl-tiktok icon-tiktok';
                                        $platform_display_name = 'TikTok';
                                        $instructions_url = 'https://developers.tiktok.com/'; // Exemplu
                                        break;
                                    case 'whatsapp':
                                        $icon_class = 'bxl-whatsapp icon-whatsapp';
                                        $platform_display_name = 'WhatsApp Business API';
                                        $instructions_url = 'https://developers.facebook.com/docs/whatsapp/'; // Exemplu
                                        break;
                                    case 'telegram':
                                        $icon_class = 'bxl-telegram icon-telegram';
                                        $platform_display_name = 'Telegram Bot API';
                                        $instructions_url = 'https://core.telegram.org/bots/api'; // Exemplu
                                        break;
                                }
                            ?>
                            <div class="col">
                                <div class="card platform-card h-100">
                                    <div class="card-body">
                                        <i class="bx <?php echo htmlspecialchars($icon_class); ?> platform-icon"></i>
                                        <h5 class="platform-name"><?php echo htmlspecialchars($platform_display_name); ?></h5>
                                        <p class="connection-status 
                                            <?php echo $platform_settings['is_connected'] ? 'status-connected' : ($platform_settings['connection_status_message'] == 'Nu este configurat' ? 'status-not-configured' : 'status-disconnected'); ?>">
                                            <?php echo htmlspecialchars($platform_settings['is_connected'] ? 'Conectat OK' : $platform_settings['connection_status_message']); ?>
                                        </p>
                                        <small class="text-muted mb-3">
                                            <?php if ($platform_settings['is_connected']): ?>
                                                Ultima conectare: <?php echo $platform_settings['last_connected_at'] ? (new DateTime($platform_settings['last_connected_at']))->format('d.m.Y H:i') : 'N/A'; ?>
                                            <?php else: ?>
                                                Necesită configurare.
                                            <?php endif; ?>
                                        </small>
                                        <button type="button" class="btn btn-primary mt-3 connect-platform-btn" 
                                                data-bs-toggle="modal" data-bs-target="#connectModal" 
                                                data-platform="<?php echo htmlspecialchars($platform_name); ?>"
                                                data-api-key="<?php echo htmlspecialchars($platform_settings['api_key'] ?? ''); ?>"
                                                data-api-secret="<?php echo htmlspecialchars($platform_settings['api_secret'] ?? ''); ?>"
                                                data-access-token="<?php echo htmlspecialchars($platform_settings['access_token'] ?? ''); ?>"
                                                data-is-connected="<?php echo $platform_settings['is_connected'] ? 'true' : 'false'; ?>"
                                                data-instructions-url="<?php echo htmlspecialchars($instructions_url); ?>"
                                                data-platform-display-name="<?php echo htmlspecialchars($platform_display_name); ?>">
                                            <i class="bx bx-plug me-2"></i><?php echo $platform_settings['is_connected'] ? 'Reconectează / Editează' : 'Conectează'; ?>
                                        </button>
                                        <?php if ($platform_settings['is_connected']): ?>
                                            <button type="button" class="btn btn-danger btn-sm mt-2 disconnect-platform-btn" data-platform="<?php echo htmlspecialchars($platform_name); ?>"><i class="bx bx-power-off me-2"></i>Deconectează</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Modal Conectare Platformă -->
<div class="modal fade" id="connectModal" tabindex="-1" aria-labelledby="connectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="connectModalLabel">Conectează <span id="modalPlatformName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="connectPlatformForm" action="process_social_accounts.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="save_connection">
                    <input type="hidden" name="platform_name" id="modalPlatformNameInput">
                    
                    <p class="text-muted">Pentru a conecta contul de <strong id="modalPlatformNameInstructions"></strong>, urmează instrucțiunile de mai jos pentru a obține cheile API/token-urile necesare.</p>
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-2"></i>
                        <span id="platformInstructionsLink">Vizitează <a href="#" target="_blank" class="text-white">documentația oficială</a> pentru instrucțiuni detaliate despre cum să obții aceste credențiale.</span>
                    </div>

                    <div class="mb-3">
                        <label for="modalApiKey" class="form-label">API Key / App ID:</label>
                        <input type="text" class="form-control" id="modalApiKey" name="api_key">
                    </div>
                    <div class="mb-3">
                        <label for="modalApiSecret" class="form-label">API Secret / App Secret:</label>
                        <input type="text" class="form-control" id="modalApiSecret" name="api_secret">
                    </div>
                    <div class="mb-3">
                        <label for="modalAccessToken" class="form-label">Access Token (dacă este necesar):</label>
                        <input type="text" class="form-control" id="modalAccessToken" name="access_token">
                        <small class="form-text text-muted">Acest câmp este necesar doar pentru anumite platforme (ex: Instagram, Telegram Bot Token).</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Salvează & Testează Conexiunea</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmă Deconectare -->
<div class="modal fade" id="disconnectModal" tabindex="-1" aria-labelledby="disconnectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="disconnectModalLabel">Confirmă Deconectarea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să deconectezi contul de <strong id="disconnectPlatformNameDisplay"></strong>? Această acțiune va șterge credențialele API și va dezactiva postările programate pe această platformă.
                <input type="hidden" id="disconnectPlatformNameInputConfirm">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDisconnectBtn">Deconectează</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const connectModal = document.getElementById('connectModal');
    const connectPlatformForm = document.getElementById('connectPlatformForm');
    const modalPlatformName = document.getElementById('modalPlatformName');
    const modalPlatformNameInput = document.getElementById('modalPlatformNameInput');
    const modalPlatformNameInstructions = document.getElementById('modalPlatformNameInstructions');
    const platformInstructionsLink = document.getElementById('platformInstructionsLink').querySelector('a');
    const modalApiKey = document.getElementById('modalApiKey');
    const modalApiSecret = document.getElementById('modalApiSecret');
    const modalAccessToken = document.getElementById('modalAccessToken');

    const disconnectModal = document.getElementById('disconnectModal');
    const disconnectPlatformNameDisplay = document.getElementById('disconnectPlatformNameDisplay');
    const disconnectPlatformNameInputConfirm = document.getElementById('disconnectPlatformNameInputConfirm');
    const confirmDisconnectBtn = document.getElementById('confirmDisconnectBtn');

    let currentPlatformToDisconnect = null;

    // Populează modalul de conectare/editare la click pe buton
    document.querySelectorAll('.connect-platform-btn').forEach(button => {
        button.addEventListener('click', function() {
            const platform = this.dataset.platform;
            const apiKey = this.dataset.apiKey;
            const apiSecret = this.dataset.apiSecret;
            const accessToken = this.dataset.accessToken;
            const isConnected = this.dataset.isConnected === 'true';
            const instructionsUrl = this.dataset.instructionsUrl;
            const platformDisplayName = this.dataset.platformDisplayName;

            modalPlatformName.textContent = platformDisplayName;
            modalPlatformNameInput.value = platform;
            modalPlatformNameInstructions.textContent = platformDisplayName;
            platformInstructionsLink.href = instructionsUrl;
            platformInstructionsLink.textContent = `documentația oficială pentru ${platformDisplayName}`;

            modalApiKey.value = apiKey;
            modalApiSecret.value = apiSecret;
            modalAccessToken.value = accessToken;

            // Ajustează vizibilitatea câmpurilor în funcție de platformă
            // Ex: TikTok poate necesita doar Access Token, Facebook API Key/Secret
            if (platform === 'whatsapp' || platform === 'telegram') {
                modalApiKey.closest('.mb-3').style.display = 'none';
                modalApiSecret.closest('.mb-3').style.display = 'none';
                modalAccessToken.closest('.mb-3').style.display = 'block';
            } else if (platform === 'tiktok') {
                modalApiKey.closest('.mb-3').style.display = 'none';
                modalApiSecret.closest('.mb-3').style.display = 'none';
                modalAccessToken.closest('.mb-3').style.display = 'block';
            }
            else {
                modalApiKey.closest('.mb-3').style.display = 'block';
                modalApiSecret.closest('.mb-3').style.display = 'block';
                modalAccessToken.closest('.mb-3').style.display = 'block';
            }

            // Dacă este deja conectat, poate afișa un mesaj diferit sau bloca anumite câmpuri
            if (isConnected) {
                // Aici poți adăuga logică specifică dacă e deja conectat
            }
        });
    });

    // Deschide modalul de confirmare deconectare
    document.querySelectorAll('.disconnect-platform-btn').forEach(button => {
        button.addEventListener('click', function() {
            currentPlatformToDisconnect = this.dataset.platform;
            const platformDisplayName = this.closest('.platform-card').querySelector('.platform-name').textContent;
            disconnectPlatformNameDisplay.textContent = platformDisplayName;
            disconnectPlatformNameInputConfirm.value = currentPlatformToDisconnect;
            new bootstrap.Modal(disconnectModal).show();
        });
    });

    // Confirmă deconectarea
    confirmDisconnectBtn.addEventListener('click', function() {
        if (currentPlatformToDisconnect) {
            const formData = new FormData();
            formData.append('action', 'disconnect_platform');
            formData.append('platform_name', currentPlatformToDisconnect);

            fetch('process_social_accounts.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message);
                    location.reload(); // Reîncarcă pagina pentru a actualiza statusul
                } else {
                    alert('Eroare: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Eroare la deconectare:', error);
                alert('A apărut o eroare la deconectare.');
            })
            .finally(() => {
                const modalInstance = bootstrap.Modal.getInstance(disconnectModal);
                if (modalInstance) { modalInstance.hide(); }
            });
        }
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
