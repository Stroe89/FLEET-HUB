<?php
require_once 'config/database.php';

echo "<h2>Test Conexiune Baza de Date - NTS TOUR</h2>";

// Test conexiune
try {
    $pdo = getDBConnection();
    echo "<div style='color: green;'>✓ Conexiunea la baza de date este OK!</div>";
    
    // Verifică dacă baza de date există
    $stmt = $pdo->query("SELECT DATABASE() as current_db");
    $result = $stmt->fetch();
    echo "<p>Baza de date curentă: <strong>" . $result['current_db'] . "</strong></p>";
    
    // Verifică dacă tabelul users există
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<div style='color: green;'>✓ Tabelul 'users' există în baza de date</div>";
        
        // Afișează structura tabelului
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll();
        echo "<h3>Structura tabelului 'users':</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Coloană</th><th>Tip</th><th>Null</th><th>Cheie</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . $column['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Contorizează utilizatorii
        $stmt = $pdo->query("SELECT COUNT(*) as user_count FROM users");
        $count = $stmt->fetch();
        echo "<p>Numărul de utilizatori în baza de date: <strong>" . $count['user_count'] . "</strong></p>";
        
        // Afișează primii 5 utilizatori (fără parole)
        $stmt = $pdo->query("SELECT id, employee_code, username, full_name, role, status, created_at FROM users LIMIT 5");
        $users = $stmt->fetchAll();
        if (count($users) > 0) {
            echo "<h3>Primii utilizatori din baza de date:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Cod Angajat</th><th>Username</th><th>Nume Complet</th><th>Rol</th><th>Status</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . $user['id'] . "</td>";
                echo "<td>" . $user['employee_code'] . "</td>";
                echo "<td>" . $user['username'] . "</td>";
                echo "<td>" . $user['full_name'] . "</td>";
                echo "<td>" . $user['role'] . "</td>";
                echo "<td>" . $user['status'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "<div style='color: red;'>✗ Tabelul 'users' NU există în baza de date</div>";
        echo "<p><a href='create_tables.php'>Creează tabelele necesare</a></p>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>✗ Eroare la conexiunea cu baza de date:</div>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    
    echo "<h3>Verificări necesare:</h3>";
    echo "<ul>";
    echo "<li>Verifică dacă MySQL este pornit</li>";
    echo "<li>Verifică dacă baza de date 'nts_tour' există</li>";
    echo "<li>Verifică username și parola în config/database.php</li>";
    echo "</ul>";
}
?>
