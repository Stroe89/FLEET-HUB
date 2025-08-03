<?php
require_once 'config/database.php';

echo "<h2>Creare Tabele - NTS TOUR</h2>";

try {
    $pdo = getDBConnection();
    
    // SQL pentru crearea tabelului users
    $createUsersTable = "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_code VARCHAR(20) NOT NULL UNIQUE,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        phone VARCHAR(20),
        role ENUM('admin', 'manager', 'employee', 'guide') DEFAULT 'employee',
        status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL,
        INDEX idx_employee_code (employee_code),
        INDEX idx_username (username),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($createUsersTable);
    echo "<div style='color: green;'>✓ Tabelul 'users' a fost creat cu succes!</div>";
    
    // Verifică dacă există deja utilizatori
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    
    if ($userCount == 0) {
        echo "<h3>Creez utilizatori de test...</h3>";
        
        // Inserează utilizatori de test
        $testUsers = [
            [
                'employee_code' => 'EMP001',
                'username' => 'admin',
                'password' => 'admin123',
                'full_name' => 'Administrator Sistem',
                'email' => 'admin@ntstour.ro',
                'role' => 'admin'
            ],
            [
                'employee_code' => 'EMP002',
                'username' => 'manager1',
                'password' => 'manager123',
                'full_name' => 'Manager Turism',
                'email' => 'manager@ntstour.ro',
                'role' => 'manager'
            ],
            [
                'employee_code' => 'EMP003',
                'username' => 'ghid1',
                'password' => 'ghid123',
                'full_name' => 'Ghid Turistic',
                'email' => 'ghid@ntstour.ro',
                'role' => 'guide'
            ],
            [
                'employee_code' => 'EMP004',
                'username' => 'angajat1',
                'password' => 'angajat123',
                'full_name' => 'Angajat NTS',
                'email' => 'angajat@ntstour.ro',
                'role' => 'employee'
            ]
        ];
        
        $insertStmt = $pdo->prepare("
            INSERT INTO users (employee_code, username, password_hash, full_name, email, role) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($testUsers as $user) {
            $passwordHash = password_hash($user['password'], PASSWORD_DEFAULT);
            $insertStmt->execute([
                $user['employee_code'],
                $user['username'],
                $passwordHash,
                $user['full_name'],
                $user['email'],
                $user['role']
            ]);
            echo "<div style='color: blue;'>→ Utilizator creat: " . $user['username'] . " (parola: " . $user['password'] . ")</div>";
        }
        
        echo "<div style='color: green; margin-top: 20px;'>✓ Utilizatori de test creați cu succes!</div>";
    } else {
        echo "<div style='color: orange;'>⚠ Există deja " . $userCount . " utilizatori în baza de date.</div>";
    }
    
    echo "<h3>Utilizatori disponibili pentru login:</h3>";
    $stmt = $pdo->query("SELECT employee_code, username, full_name, role, status FROM users WHERE status = 'active'");
    $users = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin-top: 10px;'>";
    echo "<tr style='background-color: #f0f0f0;'><th>Cod Angajat</th><th>Username</th><th>Nume Complet</th><th>Rol</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . $user['employee_code'] . "</td>";
        echo "<td>" . $user['username'] . "</td>";
        echo "<td>" . $user['full_name'] . "</td>";
        echo "<td>" . $user['role'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div style='margin-top: 20px; padding: 10px; background-color: #e8f5e8; border: 1px solid #4CAF50;'>";
    echo "<h4>Baza de date este gata de utilizare!</h4>";
    echo "<p><a href='login.php'>Mergi la pagina de login</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>✗ Eroare la crearea tabelelor:</div>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>
