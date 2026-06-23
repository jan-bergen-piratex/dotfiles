<?php
/**
 * Export Sendy contacts directly from the local Sendy database.
 *
 * Copy this file to the Sendy install root and run:
 *   php export_sendy_contacts_db.php
 *
 * It reads includes/config.php, queries all subscriber rows, derives status,
 * deduplicates by email, and writes:
 *   sendy_contacts_db.csv
 *   sendy_contacts_db_omclub.csv
 */

error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

$all_output = $argv[1] ?? 'sendy_contacts_db.csv';
$omclub_output = $argv[2] ?? preg_replace('/\.csv$/i', '_omclub.csv', $all_output);
if ($omclub_output === $all_output) {
    $omclub_output = $all_output . '_omclub.csv';
}

$config = getenv('SENDY_CONFIG') ?: __DIR__ . '/includes/config.php';
if (!is_file($config)) {
    fwrite(STDERR, "Could not find Sendy config at $config\n");
    exit(2);
}
require $config;

$host = $dbHost ?? $db_host ?? $dbhost ?? 'localhost';
$user = $dbUser ?? $db_user ?? $dbuser ?? '';
$pass = $dbPass ?? $db_pass ?? $dbpass ?? '';
$name = $dbName ?? $db_name ?? $dbname ?? '';
if ($user === '' || $name === '') {
    fwrite(STDERR, "Could not determine DB credentials from Sendy config\n");
    exit(2);
}

$mysqli = new mysqli($host, $user, $pass, $name);
if ($mysqli->connect_error) {
    fwrite(STDERR, "DB connection failed: " . $mysqli->connect_error . "\n");
    exit(2);
}
$mysqli->set_charset('utf8mb4');

function qid($identifier) {
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function columns($mysqli, $table) {
    $cols = [];
    $res = $mysqli->query('SHOW COLUMNS FROM ' . qid($table));
    if (!$res) return $cols;
    while ($row = $res->fetch_assoc()) $cols[] = $row['Field'];
    return $cols;
}

function pick($columns, $candidates) {
    foreach ($candidates as $candidate) {
        if (in_array($candidate, $columns, true)) return $candidate;
    }
    return '';
}

function first_nonempty($row, $fields) {
    foreach ($fields as $field) {
        $value = trim((string)($row[$field] ?? ''));
        if ($value !== '') return $value;
    }
    return '';
}

function clean_text($value) {
    $value = preg_replace('/\s+/', ' ', trim((string)$value));
    if (in_array(strtolower($value), ['-', 'n/a', 'na', 'none', 'null'], true)) return '';
    return $value;
}

function split_name($full_name) {
    $full_name = clean_text($full_name);
    if ($full_name === '') return ['', ''];
    $parts = preg_split('/\s+/', $full_name);
    if (count($parts) === 1) return [$parts[0], ''];
    return [$parts[0], implode(' ', array_slice($parts, 1))];
}

function person_name($row) {
    $first_name = clean_text(first_nonempty($row, ['first_name', 'firstname']));
    $last_name = clean_text(first_nonempty($row, ['last_name', 'lastname', 'surname', 'nachname']));
    if ($first_name !== '' || $last_name !== '') {
        if ($first_name === '') {
            [$fallback_first, $_] = split_name($row['name'] ?? '');
            $first_name = $fallback_first;
        }
        return [$first_name, $last_name];
    }
    return split_name($row['name'] ?? '');
}

function company_name($row) {
    return clean_text(first_nonempty($row, ['company', 'company_name', 'companyname']));
}

function language_from_list_name($name) {
    if (strpos($name, 'GER') !== false || strpos($name, 'DE') !== false) return 'de';
    if (strpos($name, 'ENG') !== false || strpos($name, 'EN') !== false) return 'en';
    return 'unknown';
}

function normalized_languages($value) {
    $parts = array_filter(array_map('trim', explode(';', strtolower((string)$value))));
    $has_de = in_array('de', $parts, true);
    $has_en = in_array('en', $parts, true);
    $languages = [];
    if ($has_de) $languages[] = 'DE';
    if ($has_en) $languages[] = 'EN';
    return $languages ? implode(';', $languages) : 'unknown';
}

function derive_status($row) {
    $bounced = isset($row['bounced']) && (string)$row['bounced'] !== '0' && (string)$row['bounced'] !== '';
    $unsubscribed = isset($row['unsubscribed']) && (string)$row['unsubscribed'] !== '0' && (string)$row['unsubscribed'] !== '';
    $complaint = isset($row['complaint']) && (string)$row['complaint'] !== '0' && (string)$row['complaint'] !== '';
    $confirmed = !isset($row['confirmed']) || (string)$row['confirmed'] === '1' || (string)$row['confirmed'] === '';
    if ($bounced) return 'bounced';
    if ($unsubscribed) return 'unsubscribed';
    if ($complaint) return 'complained';
    if (!$confirmed) return 'unconfirmed';
    return 'active';
}

function merge_values($existing, $incoming) {
    $values = array_filter(array_map('trim', explode(';', (string)$existing)));
    foreach (array_filter(array_map('trim', explode(';', (string)$incoming))) as $part) {
        if (!in_array($part, $values, true)) $values[] = $part;
    }
    return implode(';', $values);
}

function merge_row(&$merged, $row) {
    $email = strtolower(trim((string)($row['email'] ?? '')));
    if ($email === '') return;
    if (!isset($merged[$email])) {
        $row['email'] = $email;
        $merged[$email] = $row;
        return;
    }
    foreach (['languages', 'source_brand_name', 'source_list_names', 'status'] as $field) {
        $merged[$email][$field] = merge_values($merged[$email][$field] ?? '', $row[$field] ?? '');
    }
    foreach ($row as $key => $value) {
        if (!isset($merged[$email][$key]) || $merged[$email][$key] === '') {
            $merged[$email][$key] = $value;
        }
    }
}

function curated_row($row) {
    [$first_name, $last_name] = person_name($row);
    $full_name = trim(implode(' ', array_filter([$first_name, $last_name])));
    return [
        'full_name' => $full_name,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'company' => company_name($row),
        'email' => trim((string)($row['email'] ?? '')),
        'languages' => normalized_languages($row['languages'] ?? ''),
        'source_brand_name' => trim((string)($row['source_brand_name'] ?? '')),
        'source_list_names' => trim((string)($row['source_list_names'] ?? '')),
        'status' => trim((string)($row['status'] ?? '')),
        'joined' => trim((string)($row['joined'] ?? $row['timestamp'] ?? '')),
    ];
}

function write_csv($path, $rows) {
    $columns = ['full_name', 'first_name', 'last_name', 'company', 'email', 'languages', 'source_brand_name', 'source_list_names', 'status', 'joined'];
    $out = fopen($path, 'w');
    if (!$out) {
        fwrite(STDERR, "Could not write $path\n");
        exit(2);
    }
    fputcsv($out, $columns);
    foreach ($rows as $row) {
        fputcsv($out, array_map(fn($column) => $row[$column] ?? '', $columns));
    }
    fclose($out);
}

$scols = columns($mysqli, 'subscribers');
$lcols = columns($mysqli, 'lists');
$acols = columns($mysqli, 'apps');
if (!$scols || !$lcols) {
    fwrite(STDERR, "Expected Sendy tables 'subscribers' and 'lists' were not found\n");
    exit(2);
}

$subscriber_list_col = pick($scols, ['list', 'list_id']);
$list_id_col = pick($lcols, ['id']);
$list_name_col = pick($lcols, ['name', 'list_name']);
$list_app_col = pick($lcols, ['app', 'app_id', 'brand', 'brand_id', 'userID']);
$app_id_col = pick($acols, ['id']);
$app_name_col = pick($acols, ['app_name', 'name', 'from_name']);
if ($subscriber_list_col === '' || $list_id_col === '' || $list_name_col === '') {
    fwrite(STDERR, "Could not identify Sendy subscriber/list relationship columns\n");
    exit(2);
}

$selects = [];
foreach ($scols as $col) $selects[] = 's.' . qid($col) . ' AS ' . qid($col);
$selects[] = 'l.' . qid($list_name_col) . ' AS source_list_names';
if ($app_name_col !== '') $selects[] = 'a.' . qid($app_name_col) . ' AS source_brand_name';

$sql = 'SELECT ' . implode(', ', $selects) .
       ' FROM ' . qid('subscribers') . ' s ' .
       'LEFT JOIN ' . qid('lists') . ' l ON s.' . qid($subscriber_list_col) . ' = l.' . qid($list_id_col);
if ($list_app_col !== '' && $app_id_col !== '') {
    $sql .= ' LEFT JOIN ' . qid('apps') . ' a ON l.' . qid($list_app_col) . ' = a.' . qid($app_id_col);
}
$sql .= ' ORDER BY source_brand_name, source_list_names, email';

$res = $mysqli->query($sql, MYSQLI_USE_RESULT);
if (!$res) {
    fwrite(STDERR, "DB query failed: " . $mysqli->error . "\n");
    exit(2);
}

$merged = [];
while ($row = $res->fetch_assoc()) {
    $row['status'] = derive_status($row);
    $row['languages'] = language_from_list_name($row['source_list_names'] ?? '');
    merge_row($merged, $row);
}

$all_rows = [];
$omclub_rows = [];
foreach ($merged as $row) {
    $curated = curated_row($row);
    $all_rows[] = $curated;
    $brands = array_map('strtolower', array_map('trim', explode(';', $curated['source_brand_name'])));
    if (in_array('omclub', $brands, true) || in_array('omclub sponsors', $brands, true)) {
        $omclub_rows[] = $curated;
    }
}

write_csv($all_output, $all_rows);
write_csv($omclub_output, $omclub_rows);

fwrite(STDERR, "Wrote " . count($all_rows) . " contact row(s) to $all_output\n");
fwrite(STDERR, "Wrote " . count($omclub_rows) . " OMClub contact row(s) to $omclub_output\n");
?>
