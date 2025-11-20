<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Diagnóstico de Conexión MySQL</h2>";

// Prueba 1: Conexión directa con mysqli
echo "<h3>Prueba 1: Conexión directa mysqli</h3>";
$mysqli = new mysqli('127.0.0.1', 'root', 'Ana1076650648', 'soporte_tickets');

if ($mysqli->connect_error) {
    echo "❌ Error de conexión mysqli: " . $mysqli->connect_error . "<br>";
    echo "Error número: " . $mysqli->connect_errno . "<br><br>";
} else {
    echo "✅ Conexión mysqli exitosa<br>";
    echo "Base de datos: soporte_tickets<br><br>";
    
    // Probar query
    $result = $mysqli->query("SELECT COUNT(*) as total FROM users");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Total de usuarios: " . $row['total'] . "<br><br>";
    }
    $mysqli->close();
}

// Prueba 2: Conexión con PDO
echo "<h3>Prueba 2: Conexión PDO</h3>";
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=soporte_tickets', 'root', 'Ana1076650648');
    echo "✅ Conexión PDO exitosa<br><br>";
    
    $stmt = $pdo->query("SELECT id, name, email, role FROM users LIMIT 5");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['role']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} catch (PDOException $e) {
    echo "❌ Error PDO: " . $e->getMessage() . "<br><br>";
}

// Prueba 3: Conexión con Eloquent
echo "<h3>Prueba 3: Conexión Eloquent</h3>";
require __DIR__ . '/../vendor/autoload.php';

try {
    require __DIR__ . '/../app/Config/database.php';
    echo "✅ Configuración cargada<br>";
    
    $users = \App\Models\Users::all();
    echo "✅ Eloquent conectado correctamente<br>";
    echo "Usuarios encontrados: " . $users->count() . "<br>";
    
} catch (Exception $e) {
    echo "❌ Error Eloquent: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
}