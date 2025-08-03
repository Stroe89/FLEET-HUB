<?php
ob_start(); // Începe output buffering
session_start();
require_once 'db_connect.php';

// Activează afișarea erorilor pentru depanare (dezactivează în producție)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Configuration Placeholder (for multinational environment) ---
// For a multinational, these should ideally come from a central config file (e.g., config/app.php)
// or environment variables, not hardcoded here.
// Example: require_once '../config/app.php';
$upload_dir = 'uploads/documents/'; //
$allowed_file_types = ['application/pdf', 'image/jpeg', 'image/png']; //
$max_file_size = 5 * 1024 * 1024; // 5MB

// Get current user ID for audit trails
$current_user_id = $_SESSION['user_id'] ?? null;
if (empty($current_user_id)) {
    // For a multinational, strict authentication is key.
    // Redirect or throw an error if user_id is not set, meaning unauthenticated access.
    $_SESSION['error_message'] = "Sesiune invalidă sau utilizator neautentificat. Vă rugăm să vă autentificați din nou.";
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    $id_vehicul_form = $_POST['id_vehicul'] ?? null; // ID-ul vehiculului selectat din formular

    error_log("PROCESS_DOCUMENT.PHP: Cerere POST primită. Acțiune: " . $action . ", ID Vehicul: " . $id_vehicul_form);

    if (empty($id_vehicul_form) || !is_numeric($id_vehicul_form)) {
        $_SESSION['error_message'] = "Eroare: Vehiculul selectat nu este valid. Te rog alege un vehicul."; // More user-friendly
        error_log("PROCESS_DOCUMENT.PHP: Eroare - ID vehicul invalid/nespecificat. Redirecționare.");
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'documente-vehicule.php'));
        exit();
    }

    $conn->begin_transaction();

    try {
        switch ($action) {
            case 'add': // Acțiune pentru un singur document (din modalul documente-vehicule.php)
                $nume_document_user = trim($_POST['nume_document_user'] ?? ''); // Noul câmp nume document
                $tip_document = $_POST['tip_document'] ?? '';
                $data_expirare = $_POST['data_expirare'] ?? '';
                $important = isset($_POST['important']) ? 1 : 0;
                $cale_fisier = null;
                $nume_original_fisier = null;
                // Added for complexity
                $observatii = trim($_POST['observatii'] ?? '');
                $numar_referinta = trim($_POST['numar_referinta'] ?? '');


                if (empty($nume_document_user) || empty($tip_document) || empty($data_expirare)) {
                    throw new Exception("Toate câmpurile (Nume, Tip, Data Expirării) sunt obligatorii."); // More user-friendly
                }
                if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $data_expirare) || !strtotime($data_expirare)) {
                    throw new Exception("Data de expirare nu este validă. Formatul așteptat este YYYY-MM-DD."); // More user-friendly
                }

                if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] == UPLOAD_ERR_OK) {
                    // Ensure upload directory exists
                    if (!is_dir($upload_dir)) {
                        error_log("PROCESS_DOCUMENT.PHP: Directorul de upload nu există. Încercare creare: " . $upload_dir);
                        if (!mkdir($upload_dir, 0777, true)) {
                            throw new Exception("Eroare la crearea directorului de încărcare. Vă rugăm contactați administratorul."); // More user-friendly
                        }
                    }

                    $file_extension = pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION);
                    $new_file_name = uniqid('doc_') . '.' . $file_extension;
                    $cale_fisier = $upload_dir . $new_file_name;
                    $nume_original_fisier = basename($_FILES['document_file']['name']);

                    // Validation using predefined constants
                    if (!in_array($_FILES['document_file']['type'], $allowed_file_types)) {
                        throw new Exception("Tipul fișierului nu este permis. Se acceptă doar PDF, JPG, PNG."); //
                    }
                    if ($_FILES['document_file']['size'] > $max_file_size) {
                        throw new Exception("Dimensiunea fișierului depășește limita de 5MB."); //
                    }
                    if (!move_uploaded_file($_FILES['document_file']['tmp_name'], $cale_fisier)) {
                        throw new Exception("Eroare la încărcarea fișierului. Verificați permisiunile directorului de încărcare."); // More user-friendly
                    }
                    error_log("PROCESS_DOCUMENT.PHP: Fișier încărcat cu succes: " . $cale_fisier);

                    // --- Multinational Consideration: Cloud Storage Upload ---
                    // For a multinational, instead of move_uploaded_file, you would upload to S3/Azure Blob/GCS here.
                    // This involves using the respective SDKs (e.g., AWS SDK for PHP via Composer).
                    // Example (conceptual using AWS S3 SDK):
                    /*
                    require 'vendor/autoload.php'; // If using Composer for SDKs
                    use Aws\S3\S3Client;
                    $s3Client = new S3Client([
                        'version' => 'latest',
                        'region'  => 'your-aws-region', // e.g., 'eu-central-1'
                        'credentials' => [
                            'key'    => 'YOUR_AWS_ACCESS_KEY_ID',
                            'secret' => 'YOUR_AWS_SECRET_ACCESS_KEY',
                        ],
                    ]);
                    $bucketName = 'your-company-document-bucket';
                    $s3Key = 'documents/' . $new_file_name; // Path within the S3 bucket
                    try {
                        $result = $s3Client->putObject([
                            'Bucket'     => $bucketName,
                            'Key'        => $s3Key,
                            'SourceFile' => $_FILES['document_file']['tmp_name'],
                            'ACL'        => 'private', // Or 'public-read' if accessible via URL
                            'Metadata'   => [
                                'original_name' => urlencode($nume_original_fisier), // Store original name in metadata
                                'uploaded_by'   => $current_user_id,
                            ]
                        ]);
                        $cale_fisier = $result['ObjectURL']; // Store the URL of the file in S3
                        error_log("PROCESS_DOCUMENT.PHP: Fișier încărcat cu succes pe S3: " . $cale_fisier);
                    } catch (Aws\S3\Exception\S3Exception $e) {
                        throw new Exception("Eroare la încărcarea fișierului în cloud: " . $e->getMessage());
                    }
                    */
                } else if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] != UPLOAD_ERR_NO_FILE) {
                     throw new Exception("Eroare la încărcarea fișierului: Cod eroare " . $_FILES['document_file']['error'] . ". Contactați suportul tehnic pentru asistență."); // More user-friendly
                }

                // Add created_at and created_by for audit trail
                // Added new fields to INSERT statement
                $stmt = $conn->prepare("INSERT INTO documente (id_vehicul, nume_document_user, tip_document, data_expirare, cale_fisier, nume_original_fisier, important, observatii, numar_referinta, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt === false) throw new Exception("Eroare la pregătirea operațiunii de adăugare a documentului: " . $conn->error); // More user-friendly
                // Updated bind_param signature
                $stmt->bind_param("isssssisii", $id_vehicul_form, $nume_document_user, $tip_document, $data_expirare, $cale_fisier, $nume_original_fisier, $important, $observatii, $numar_referinta, $current_user_id);
                if (!$stmt->execute()) throw new Exception("Eroare la adăugarea documentului în baza de date: " . $stmt->error);
                $stmt->close();
                $_SESSION['success_message'] = "Documentul a fost adăugat cu succes!";
                error_log("PROCESS_DOCUMENT.PHP: Document adăugat cu succes (single).");
                break;

            case 'add_multiple': // Acțiune pentru adăugarea mai multor documente (din adauga-document.php)
                $documents_data = $_POST['documents'] ?? [];
                
                if (empty($documents_data)) {
                    throw new Exception("Niciun document nu a fost trimis pentru adăugare.");
                }

                // Ensure upload directory exists
                if (!is_dir($upload_dir)) {
                    error_log("PROCESS_DOCUMENT.PHP: Directorul de upload nu există. Încercare creare: " . $upload_dir);
                    if (!mkdir($upload_dir, 0777, true)) {
                        throw new Exception("Eroare la crearea directorului de încărcare. Vă rugăm contactați administratorul."); // More user-friendly
                    }
                }

                // Prepare statement outside the loop for efficiency
                // Added new fields to INSERT statement
                $stmt = $conn->prepare("INSERT INTO documente (id_vehicul, nume_document_user, tip_document, data_expirare, cale_fisier, nume_original_fisier, important, observatii, numar_referinta, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt === false) throw new Exception("Eroare la pregătirea operațiunii de adăugare multiplă: " . $conn->error); // More user-friendly

                foreach ($documents_data as $key => $doc_data) {
                    $nume_document_user = trim($doc_data['nume_document_user'] ?? '');
                    $tip_document = $doc_data['tip_document'] ?? '';
                    $data_expirare = $doc_data['data_expirare'] ?? '';
                    $important = isset($doc_data['important']) ? 1 : 0;
                    $cale_fisier = null;
                    $nume_original_fisier = null;
                    // Added for complexity
                    $observatii = trim($doc_data['observatii'] ?? '');
                    $numar_referinta = trim($doc_data['numar_referinta'] ?? '');

                    error_log("PROCESS_DOCUMENT.PHP: Procesare document multiplu Key: " . $key . ", Nume: " . $nume_document_user);

                    if (empty($nume_document_user) || empty($tip_document) || empty($data_expirare)) {
                        throw new Exception("Toate câmpurile (Nume, Tip, Data Expirării) sunt obligatorii pentru documentul " . ($key + 1) . "."); // More user-friendly
                    }
                    if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $data_expirare) || !strtotime($data_expirare)) {
                        throw new Exception("Data de expirare nu este validă pentru documentul " . ($key + 1) . ". Formatul așteptat este YYYY-MM-DD."); // More user-friendly
                    }

                    // Logica pentru upload fisier individual (din $_FILES['documents'])
                    // Verificăm dacă există un fișier pentru acest index și dacă nu are erori
                    if (isset($_FILES['documents']['name'][$key]['document_file']) && $_FILES['documents']['error'][$key]['document_file'] == UPLOAD_ERR_OK) {
                        $file_tmp_name = $_FILES['documents']['tmp_name'][$key]['document_file'];
                        $file_name_original = $_FILES['documents']['name'][$key]['document_file'];
                        $file_type = $_FILES['documents']['type'][$key]['document_file'];
                        $file_size = $_FILES['documents']['size'][$key]['document_file'];

                        $file_extension = pathinfo($file_name_original, PATHINFO_EXTENSION);
                        $new_file_name = uniqid('doc_') . '.' . $file_extension;
                        $cale_fisier = $upload_dir . $new_file_name;
                        $nume_original_fisier = basename($file_name_original);

                        // Validation using predefined constants
                        if (!in_array($file_type, $allowed_file_types)) {
                            throw new Exception("Tipul fișierului pentru documentul " . ($key + 1) . " nu este permis. Se acceptă doar PDF, JPG, PNG."); //
                        }
                        if ($file_size > $max_file_size) {
                            throw new Exception("Dimensiunea fișierului pentru documentul " . ($key + 1) . " depășește limita de 5MB."); //
                        }
                        if (!move_uploaded_file($file_tmp_name, $cale_fisier)) {
                            throw new Exception("Eroare la încărcarea fișierului pentru documentul " . ($key + 1) . ". Verificați permisiunile directorului de încărcare."); // More user-friendly
                        }
                        error_log("PROCESS_DOCUMENT.PHP: Fișier multiplu încărcat cu succes: " . $cale_fisier);

                        // --- Multinational Consideration: Cloud Storage Upload ---
                        // Similar to 'add' action, upload to cloud storage here instead of local file system.
                    } else if (isset($_FILES['documents']['error'][$key]['document_file']) && $_FILES['documents']['error'][$key]['document_file'] != UPLOAD_ERR_NO_FILE) {
                        // Eroare la încărcarea fișierului, dar nu UPLOAD_ERR_NO_FILE
                        throw new Exception("Eroare la încărcarea fișierului pentru documentul " . ($key + 1) . ": Cod eroare " . $_FILES['documents']['error'][$key]['document_file'] . ". Contactați suportul tehnic pentru asistență."); // More user-friendly
                    }
                    // Dacă nu s-a selectat fișier (UPLOAD_ERR_NO_FILE), cale_fisier și nume_original_fisier rămân null, ceea ce este OK.

                    // Bind parameters and execute for each document
                    // Updated bind_param signature
                    $stmt->bind_param("isssssisii", $id_vehicul_form, $nume_document_user, $tip_document, $data_expirare, $cale_fisier, $nume_original_fisier, $important, $observatii, $numar_referinta, $current_user_id);
                    if (!$stmt->execute()) {
                        throw new Exception("Eroare la adăugarea documentului " . ($key + 1) . " în baza de date: " . $stmt->error);
                    }
                }
                $stmt->close();
                $_SESSION['success_message'] = "Documentele au fost adăugate cu succes!";
                error_log("PROCESS_DOCUMENT.PHP: Documente multiple adăugate cu succes.");
                break;

            case 'edit':
                $document_id = $_POST['document_id'] ?? null;
                $nume_document_user = trim($_POST['nume_document_user'] ?? '');
                $data_expirare = $_POST['data_expirare'] ?? '';
                $important = isset($_POST['important']) ? 1 : 0;
                // Added for complexity
                $observatii = trim($_POST['observatii'] ?? '');
                $numar_referinta = trim($_POST['numar_referinta'] ?? '');


                if (empty($document_id) || empty($nume_document_user) || empty($data_expirare) || !is_numeric($document_id)) {
                    throw new Exception("ID document, nume document sau data expirării invalidă pentru editare. Vă rugăm verificați datele introduse."); // More user-friendly
                }
                if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $data_expirare) || !strtotime($data_expirare)) {
                    throw new Exception("Data de expirare nu este validă. Formatul așteptat este YYYY-MM-DD."); // More user-friendly
                }

                // Include updated_at and updated_by for audit trail
                // Added new fields to UPDATE statement
                $stmt = $conn->prepare("UPDATE documente SET nume_document_user = ?, data_expirare = ?, important = ?, observatii = ?, numar_referinta = ?, updated_at = CURRENT_TIMESTAMP, updated_by = ? WHERE id = ? AND id_vehicul = ?");
                if ($stmt === false) throw new Exception("Eroare la pregătirea operațiunii de actualizare a documentului: " . $conn->error); // More user-friendly
                // Updated bind_param signature
                $stmt->bind_param("ssiisiii", $nume_document_user, $data_expirare, $important, $observatii, $numar_referinta, $current_user_id, $document_id, $id_vehicul_form);
                if (!$stmt->execute()) throw new Exception("Eroare la actualizarea documentului în baza de date: " . $stmt->error);
                $stmt->close();
                $_SESSION['success_message'] = "Documentul a fost actualizat cu succes!"; // More general success message
                error_log("PROCESS_DOCUMENT.PHP: Document editat cu succes.");
                break;

            case 'delete':
                $document_id = $_POST['document_id'] ?? null;
                if (empty($document_id) || !is_numeric($document_id)) {
                    throw new Exception("ID document invalid pentru ștergere. Vă rugăm reîncărcați pagina."); // More user-friendly
                }

                // Preluam calea fisierului inainte de stergere
                $stmt_file = $conn->prepare("SELECT cale_fisier FROM documente WHERE id = ? AND id_vehicul = ?");
                if ($stmt_file === false) throw new Exception("Eroare la pregătirea interogării pentru fișier: " . $conn->error); // More user-friendly
                $stmt_file->bind_param("ii", $document_id, $id_vehicul_form);
                $stmt_file->execute();
                $result_file = $stmt_file->get_result();
                $file_data = $result_file->fetch_assoc();
                $stmt_file->close();

                // --- Multinational Consideration: Soft Delete & Cloud Storage ---
                // For a multinational, instead of physically deleting, implement "soft deletion"
                // by setting `is_deleted = TRUE` in the database.
                // For files in cloud storage, you might move them to an "archive" folder or
                // set a lifecycle policy to delete them after a certain period, instead of immediate deletion.
                /*
                if ($file_data && !empty($file_data['cale_fisier'])) {
                    // If using local storage:
                    if (file_exists($file_data['cale_fisier'])) {
                        unlink($file_data['cale_fisier']); // Delete file from server
                        error_log("PROCESS_DOCUMENT.PHP: Fișier șters de pe server: " . $file_data['cale_fisier']);
                    }
                    // If using S3/Cloud Storage:
                    // $s3Client->deleteObject(['Bucket' => $bucketName, 'Key' => 'documents/' . basename($file_data['cale_fisier'])]);
                    // OR move to archive:
                    // $s3Client->copyObject([
                    //     'Bucket'     => $bucketName,
                    //     'CopySource' => urlencode($bucketName . '/' . 'documents/' . basename($file_data['cale_fisier'])),
                    //     'Key'        => 'archives/' . basename($file_data['cale_fisier']),
                    // ]);
                    // $s3Client->deleteObject(['Bucket' => $bucketName, 'Key' => 'documents/' . basename($file_data['cale_fisier'])]);
                }
                */

                // Implement soft delete instead of hard delete for better auditability
                // And ensure the `is_deleted` column is handled in all SELECT queries elsewhere.
                $stmt = $conn->prepare("UPDATE documente SET is_deleted = TRUE, updated_at = CURRENT_TIMESTAMP, updated_by = ? WHERE id = ? AND id_vehicul = ?");
                if ($stmt === false) throw new Exception("Eroare la pregătirea operațiunii de ștergere logică: " . $conn->error); // More user-friendly
                $stmt->bind_param("iii", $current_user_id, $document_id, $id_vehicul_form);
                if (!$stmt->execute()) throw new Exception("Eroare la ștergerea documentului (marcare ca inactiv) în baza de date: " . $stmt->error);
                $stmt->close();
                $_SESSION['success_message'] = "Documentul a fost șters (marcat ca inactiv) cu succes!"; // Reflects soft delete
                error_log("PROCESS_DOCUMENT.PHP: Document șters (logic) cu succes din baza de date.");
                break;

            default:
                throw new Exception("Acțiune invalidă. Vă rugăm să reîncărcați pagina sau să contactați suportul tehnic."); // More user-friendly
        }
        $conn->commit();
        error_log("PROCESS_DOCUMENT.PHP: Tranzacție finalizată cu succes.");
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Eroare la procesarea documentului: " . $e->getMessage(); // More user-friendly prefix
        error_log("PROCESS_DOCUMENT.PHP: Eroare în tranzacție: " . $e->getMessage());
    } finally {
        if(isset($conn)) { $conn->close(); }
    }
    // Redirect to the page that initiated the request or a default
    $redirect_url = $_SERVER['HTTP_REFERER'] ?? 'documente-vehicule.php';
    if (!empty($id_vehicul_form) && !str_contains($redirect_url, 'id=')) { // Ensure ID is passed for contextual redirection
         $redirect_url = 'documente-vehicule.php?id=' . $id_vehicul_form;
    }
    header("Location: " . $redirect_url);
    exit();

} else {
    error_log("PROCESS_DOCUMENT.PHP: Cerere non-POST sau acțiune fetch directă.");
    // Optionally redirect to a safe page if direct access
    header("Location: index.php");
    exit();
}
ob_end_flush();
?>