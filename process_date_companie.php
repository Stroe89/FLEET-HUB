<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_company_data') {
    $current_user_id = $_SESSION['user_id'];
    $existing_logo_path = $_POST['existing_logo_path'] ?? '';
    
    // Definim array-ul $data cu toate câmpurile posibile
    $data = [
        'nume_companie' => trim($_POST['nume_companie'] ?? ''),
        'cui' => trim($_POST['cui'] ?? ''),
        'nr_reg_com' => trim($_POST['nr_reg_com'] ?? ''),
        'adresa' => trim($_POST['adresa'] ?? ''),
        'oras' => trim($_POST['oras'] ?? ''),
        'judet' => trim($_POST['judet'] ?? ''),
        'cod_postal' => trim($_POST['cod_postal'] ?? ''),
        'telefon' => trim($_POST['telefon'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'website' => trim($_POST['website'] ?? ''),
        'logo_path' => $existing_logo_path,
        'bank_name' => trim($_POST['bank_name'] ?? ''),
        'bank_iban' => trim($_POST['bank_iban'] ?? ''),
        'bank_swift' => trim($_POST['bank_swift'] ?? ''),
        'reprezentant_legal' => trim($_POST['reprezentant_legal'] ?? ''),
        'functie_reprezentant' => trim($_POST['functie_reprezentant'] ?? ''),
        'cod_fiscal' => trim($_POST['cod_fiscal'] ?? ''),
        'activitate_principala' => trim($_POST['activitate_principala'] ?? ''),
        'numar_angajati' => trim($_POST['numar_angajati'] ?? ''),
        'capital_social' => trim($_POST['capital_social'] ?? ''),
        'telefon_secundar' => trim($_POST['telefon_secundar'] ?? ''),
        'email_secundar' => trim($_POST['email_secundar'] ?? ''),
        'tara' => trim($_POST['tara'] ?? ''),
        'regiune' => trim($_POST['regiune'] ?? ''),
        'slogan' => trim($_POST['slogan'] ?? ''),
        'antet' => trim($_POST['antet'] ?? ''),
        'domeniu_principal' => trim($_POST['domeniu_principal'] ?? '')
    ];
    
    // Verificăm dacă există un logo nou
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/logos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = uniqid() . '_' . basename($_FILES['logo']['name']);
        $target_file = $upload_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($file_type, $allowed_types)) {
            if ($_FILES['logo']['size'] <= 5 * 1024 * 1024) {
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
                    if (!empty($existing_logo_path) && file_exists($existing_logo_path)) {
                        unlink($existing_logo_path);
                    }
                    $data['logo_path'] = $target_file;
                }
            }
        }
    }
    
    // Verificăm dacă trebuie să ștergem logo-ul
    if (isset($_POST['delete_logo']) && $_POST['delete_logo'] == '1') {
        if (!empty($existing_logo_path) && file_exists($existing_logo_path)) {
            unlink($existing_logo_path);
        }
        $data['logo_path'] = '';
    }
    
    // Verificăm dacă există deja o înregistrare pentru acest user
    $sql_check = "SELECT user_id FROM date_companie WHERE user_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $current_user_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    try {
        if ($result_check->num_rows > 0) {
            // UPDATE
            $sql = "UPDATE date_companie SET 
                nume_companie=?, cui=?, nr_reg_com=?, adresa=?, oras=?, judet=?, 
                cod_postal=?, telefon=?, email=?, website=?, logo_path=?, bank_name=?,
                bank_iban=?, bank_swift=?, reprezentant_legal=?, functie_reprezentant=?, 
                cod_fiscal=?, activitate_principala=?, numar_angajati=?, capital_social=?, 
                telefon_secundar=?, email_secundar=?, tara=?, regiune=?, slogan=?, 
                antet=?, domeniu_principal=? WHERE user_id=?";
            
            $params = [
                $data['nume_companie'], $data['cui'], $data['nr_reg_com'], 
                $data['adresa'], $data['oras'], $data['judet'], 
                $data['cod_postal'], $data['telefon'], $data['email'], 
                $data['website'], $data['logo_path'], $data['bank_name'],
                $data['bank_iban'], $data['bank_swift'], $data['reprezentant_legal'],
                $data['functie_reprezentant'], $data['cod_fiscal'], 
                $data['activitate_principala'], $data['numar_angajati'],
                $data['capital_social'], $data['telefon_secundar'],
                $data['email_secundar'], $data['tara'], $data['regiune'],
                $data['slogan'], $data['antet'], $data['domeniu_principal'],
                $current_user_id
            ];
            
            $stmt = $conn->prepare($sql);
            $types = str_repeat('s', 27) . 'i';
            $stmt->bind_param($types, ...$params);
            
        } else {
            // INSERT
            $sql = "INSERT INTO date_companie (user_id, nume_companie, cui, nr_reg_com, 
                adresa, oras, judet, cod_postal, telefon, email, website, logo_path, 
                bank_name, bank_iban, bank_swift, reprezentant_legal, functie_reprezentant, 
                cod_fiscal, activitate_principala, numar_angajati, capital_social, 
                telefon_secundar, email_secundar, tara, regiune, slogan, antet, 
                domeniu_principal) VALUES (?" . str_repeat(',?', 27) . ")";
            
            $params = [
                $current_user_id, $data['nume_companie'], $data['cui'], 
                $data['nr_reg_com'], $data['adresa'], $data['oras'], 
                $data['judet'], $data['cod_postal'], $data['telefon'], 
                $data['email'], $data['website'], $data['logo_path'],
                $data['bank_name'], $data['bank_iban'], $data['bank_swift'],
                $data['reprezentant_legal'], $data['functie_reprezentant'],
                $data['cod_fiscal'], $data['activitate_principala'],
                $data['numar_angajati'], $data['capital_social'],
                $data['telefon_secundar'], $data['email_secundar'],
                $data['tara'], $data['regiune'], $data['slogan'],
                $data['antet'], $data['domeniu_principal']
            ];
            
            $stmt = $conn->prepare($sql);
            $types = 'i' . str_repeat('s', 27);
            $stmt->bind_param($types, ...$params);
        }
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Datele companiei au fost salvate cu succes!";
        } else {
            throw new Exception($stmt->error);
        }
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Eroare la salvarea datelor: " . $e->getMessage();
    }
    
    if (isset($stmt)) $stmt->close();
    if (isset($stmt_check)) $stmt_check->close();
    $conn->close();
    
    header("Location: date-companie.php");
    exit();
}

header("Location: date-companie.php");
exit();