<?php
header('Content-Type: text/plain');

/*******************************************/
/*******************************************/
/* SCRIPT PARA MIGRAR JSON A BASE DE DATOS */
/*******************************************/
/*******************************************/
$db_user = 'root';
$db_pass = '';
$database = 'beerpoint';
$tabla = 'usuarios';
$archivo = '../usuario.json';
$host = 'mysql:host=localhost;dbname='.$database.';charset=utf8mb4;port:3306';
$agregar = 0; // SI SE SETEA EN 1 AÑADE DATOS AL FINAL, EN 0 BORRA LOS EXISTENTES

try {
    $db = new PDO($host, $db_user, $db_pass);
}
catch( PDOException $Exception ) {
    echo $Exception->getMessage();
}

$results = $db->query("SELECT * FROM $tabla");
$results = $results->fetchAll(PDO::FETCH_ASSOC);

if ($agregar === 0) {
    if (file_exists($archivo)){
        unlink($archivo);
        echo "Borrando archivo y comenzando de 0...\n";
    } else {
        echo "El archivo no existe, creando...\n";
    }
}

foreach ($results as $key => $value) {
    foreach ($results[$key] as $index => $valor) {
        if (is_numeric($valor)) {
            $results[$key][$index] = intval($valor);
        }
    }
    // var_dump($results[$key]);
    $linea = json_encode($results[$key]);
    file_put_contents($archivo, $linea . PHP_EOL, FILE_APPEND);
    echo "Agregado: " . $linea . "\n";
}

echo "Finalizado.";
?>
