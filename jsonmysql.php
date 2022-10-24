<?php
header('Content-Type: text/plain');

/*******************************************/
/*******************************************/
/* SCRIPT PARA MIGRAR JSON A BASE DE DATOS */
/*******************************************/
/*******************************************/



/********************
* SETEAR VARIABLES *
*******************************************/


$host = 'mysql:host=localhost;charset=utf8mb4;port:3306';
$db_user = 'root';
$db_pass = '';
$database = 'beerpoint';
$archivo = '../usuarios.json';
$tabla = 'usuarios';
$agregar = 0;	// SI SE SETEA EN 1 AÑADE DATOS AL FINAL, EN 0 BORRA LOS EXISTENTES

$tiposDatosExcepciones = [
	"integer"	=>	"int(11)"
];
$tiposNombreExcepciones = [
	"descripcion" => "text",
	"pass"	=>	"char(60)"
];
$tiposDefault = "varchar(100)";


/****************************************
* SE TRAEN LOS DATOS DEL ARCHIVO JSON  *
****************************************/
$EOL = PHP_EOL;
$array = explode($EOL, file_get_contents($archivo));

foreach ($array as $key => $value) {
	$arrayTerminado[] = json_decode($value, true);
}

array_pop($arrayTerminado);

/********************
* GENERAR CONEXIÓN *
********************/

try {
	$db = new PDO($host, $db_user, $db_pass);
}
catch( PDOException $Exception ) {
	echo $Exception->getMessage();
}

/***************************************************************
* CREAR BASE DE DATOS SI NO EXISTE CON EL NOMBRE EN $database *
***************************************************************/
$query = $db->query("SHOW DATABASES LIKE '$database'");
$array = $query->fetchAll(PDO::FETCH_ASSOC);
if (empty($array)) {
	$db->query("CREATE DATABASE IF NOT EXISTS $database;
		GRANT ALL ON $database.* TO $db_user@localhost;
		FLUSH PRIVILEGES;")
		or die(print_r($db->errorInfo(), true));
		echo "Base de datos \"$database\" creada.\n";
}else {
	echo "Base de datos \"$tabla\" ya existe, continuando...\n";
}

/**********************************************************
* SE GENERA ARRAY CON COLUMNAS Y ATRIBUTOS DE LAS MISMAS *
**********************************************************/
$detected = 0;	// SI NO SE DETECTO NINGUNA EXCEPCION EN EL TIPO, QUEDA EN 0 Y SE USA EL DEFAULT
if (!empty($arrayTerminado)) {
	foreach ($arrayTerminado as $index => $asd) {
		$actual = $arrayTerminado[$index];
		$tipos = [];
		$columnas = [];

		foreach ($actual as $key => $value) {	// SE CAMBIAN VALORES POR SUS TIPOS (EJ. El nombre se cambia por "string")
			if ($key !== "id") {
				$tipos[$key] = gettype($value);
			}
		}

		foreach ($tipos as $key => $value) {	// SE DETECTAN LOS TIPOS DE DATOS A EXCEPCIONAR
			foreach ($tiposDatosExcepciones as $tipoNombre => $tipoValor) {
				if ($value == $tipoNombre) {
					$value = $tipoValor;
					$detected = 1;
					break;
				}
			}
			foreach ($tiposNombreExcepciones as $tipoNombre => $tipoValor) { // SE DETECTAN LOS DATOS ESPECÍFICOS A EXCEPCIONAR
				if ($key == $tipoNombre) {
					$value = $tipoValor;
					$detected = 1;
					break;
				}
			}
			if ($detected === 0) {	// SI NO SE DETECTÓ NADA, SE USA EL DEFAULT
				$value = $tiposDefault;
			}

			$detected = 0;	//RESETEA LA DETECCIÓN PARA EL PRÓXIMO CHECKEO

			$columnas[] = $key . " ". $value . " NOT NULL";
		}
	}
	$columnas = implode(", ", $columnas);

} else {
	echo "El archivo estaba vacio... Terminado.";exit;
}
/****************************
* CREAR TABLA SI NO EXISTE *
****************************/

$db->query("use $database");
$query = $db->query("SHOW TABLES");
$array = $query->fetchAll(PDO::FETCH_ASSOC);
$encontrado = 0;

foreach ($array as $key => $value) {
	foreach ($array[$key] as $index => $valor) {
		if ($valor === $tabla) {
			$encontrado = 1;
			break;
		}

	}
}

if ($encontrado === 0) {
	$db->query('CREATE TABLE IF NOT EXISTS '.$tabla.' (
		id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT, '.$columnas . ')')
		or die(print_r($db->errorInfo(), true));
		echo "Tabla \"$tabla\" creada.\n";
} else {
	echo "Tabla \"$tabla\" ya existe, continuando...\n";
}
/***********************************************************************
* SI $AGREGAR SE DEFINIÓ EN 0, SE BORRA LA TABLA Y SE EMPIEZA DE NUEVO *
***********************************************************************/

if ($agregar === 0) {
	$db->query('DELETE FROM '.$tabla.';')
	or die(print_r($db->errorInfo(), true));
	$db->query('ALTER TABLE '.$tabla.' AUTO_INCREMENT = 1;');
	echo "Vaciando tabla...\n";
} else {
	echo "Tabla no vaciada, agregando datos a tabla...\n";
}

/**************************************
* SE GENERAN Y SE MANDAN LAS QUERIES *
**************************************/

$db->beginTransaction();
foreach ($arrayTerminado as $index => $asd) {
	$actual = $arrayTerminado[$index];
	$index = [];
	$valor = [];
	foreach ($actual as $key => $value) {	// SE GENERAN LOS NOMBRES DE COLUMNAS Y SUS VALORES
		if ($key !== "id") {
			$index[] = $key;
			$valor[] = '"'.$value.'"';
		}
	}

	$index = implode(", ", $index);
	$valor = implode(", ", $valor);

	$db->exec('INSERT INTO '.$tabla.' ('.$index.') VALUE ('.$valor.');')
	or die(print_r($db->errorInfo(), true));
	echo "Añadiendo en \"$tabla\": $valor\n";
}

$db->commit();
echo "Finalizado.";
?>
