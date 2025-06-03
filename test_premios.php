<?php
// test_premios.php
include 'conexion.php'; // Asegúrate de que la ruta a tu archivo de conexión sea correcta

header('Content-Type: text/plain; charset=utf-8'); // Para ver la salida fácilmente en texto plano

echo "Intentando conectar a la base de datos...\n";
if ($conn->connect_error) {
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}
echo "Conexión exitosa a la base de datos.\n\n";

echo "Intentando llamar al procedimiento almacenado SP_ListarPremiosDisponibles...\n";

// Usar un bloque try-catch para capturar excepciones de mysqli
try {
    $stmt = $conn->prepare("CALL SP_ListarPremiosDisponibles()");

    if ($stmt) {
        echo "Sentencia preparada con éxito.\n";
        if ($stmt->execute()) {
            echo "Procedimiento ejecutado con éxito.\n";
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                echo "Premios encontrados:\n";
                while ($row = $result->fetch_assoc()) {
                    echo "ID: " . $row['id_premio'] . ", Nombre: " . $row['nombre'] . ", Puntos: " . $row['puntos_requeridos'] . "\n";
                }
            } else {
                echo "No se encontraron premios.\n";
            }
            $stmt->close();
        } else {
            // Este es el error si execute() falla
            echo "Error al ejecutar el procedimiento: " . $stmt->error . "\n";
        }
    } else {
        // Este es el error que estás viendo en usu.php
        echo "Error al preparar la sentencia para el procedimiento: " . $conn->error . "\n";
    }
} catch (mysqli_sql_exception $e) {
    echo "Excepción de MySQL capturada: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Excepción general capturada: " . $e->getMessage() . "\n";
} finally {
    if ($conn) $conn->close();
    echo "\nConexión cerrada.\n";
}
?>