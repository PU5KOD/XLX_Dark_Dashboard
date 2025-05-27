<?php

// Path to CSV and SQLite database
$csvFile = '/xlxd/users_db/user.csv';
$dbFile = '/xlxd/users_db/users.db';

// Check if CSV file exists
if (!file_exists($csvFile)) {
    die("Error: user.csv not found at $csvFile\n");
}

// Create or open SQLite database
try {
    $db = new SQLite3($dbFile);
} catch (Exception $e) {
    die("Error: Could not open SQLite database: " . $e->getMessage() . "\n");
}

// Create table (drop if exists to ensure fresh data)
$db->exec('DROP TABLE IF EXISTS users');
$db->exec('CREATE TABLE users (
    callsign TEXT PRIMARY KEY,
    name TEXT,
    city_state TEXT
)');

// Mapping of Brazilian states for acronyms
$estadosBrasil = [
    'Acre' => 'AC',
    'Alagoas' => 'AL',
    'Amapa' => 'AP',
    'Amazonas' => 'AM',
    'Bahia' => 'BA',
    'Ceara' => 'CE',
    'Distrito Federal' => 'DF',
    'Espirito Santo' => 'ES',
    'Goias' => 'GO',
    'Maranhao' => 'MA',
    'Mato Grosso' => 'MT',
    'Mato Grosso do Sul' => 'MS',
    'Minas Gerais' => 'MG',
    'Para' => 'PA',
    'Paraiba' => 'PB',
    'Parana' => 'PR',
    'Pernambuco' => 'PE',
    'Piaui' => 'PI',
    'Rio de Janeiro' => 'RJ',
    'Rio Grande do Norte' => 'RN',
    'Rio Grande do Sul' => 'RS',
    'Rondonia' => 'RO',
    'Roraima' => 'RR',
    'Santa Catarina' => 'SC',
    'Sao Paulo' => 'SP',
    'Sergipe' => 'SE',
    'Tocantins' => 'TO'
];

// Begin a transaction to speed up inserts
$db->exec('BEGIN TRANSACTION');

// Read CSV and insert into database
$handle = fopen($csvFile, 'r');
if ($handle === false) {
    die("Error: Could not open user.csv\n");
}

// Skip header row
fgetcsv($handle);

// Prepare insert statement
$stmt = $db->prepare('INSERT OR REPLACE INTO users (callsign, name, city_state) VALUES (:callsign, :name, :city_state)');

$counter = 0;
$startTime = microtime(true);

// Process each row
while (($row = fgetcsv($handle)) !== false) {
    if (count($row) < 7) continue; // Skip malformed rows
    $callsign = strtoupper(trim($row[1])); // Callsign (index 1)
    $fullName = trim($row[2] . ' ' . $row[3]); // fname + surname
    $cidade = trim($row[4]); // city (index 4)
    $estado = trim($row[5]); // state (index 5)

    // Check if the state is Brazilian and abbreviate it
    $estadoAbreviado = $estado;
    if (array_key_exists($estado, $estadosBrasil)) {
        $estadoAbreviado = $estadosBrasil[$estado];
    }

    // Combine city and state with ", " as separator
    $cityState = $cidade . ', ' . $estadoAbreviado;

    if (empty($callsign)) continue; // Skip empty callsigns

    $stmt->bindValue(':callsign', $callsign, SQLITE3_TEXT);
    $stmt->bindValue(':name', $fullName, SQLITE3_TEXT);
    $stmt->bindValue(':city_state', $cityState, SQLITE3_TEXT);
    $stmt->execute();

    // Print progress every 1000 rows
    $counter++;
    if ($counter % 1000 === 0) {
        $elapsed = microtime(true) - $startTime;
        echo "Processed $counter rows in " . number_format($elapsed, 2) . " seconds\n";
    }
}

fclose($handle);

// Commit the transaction
$db->exec('COMMIT');

$db->close();

$elapsed = microtime(true) - $startTime;
echo "Database updated successfully. Processed $counter rows in " . number_format($elapsed, 2) . " seconds\n";
?>
