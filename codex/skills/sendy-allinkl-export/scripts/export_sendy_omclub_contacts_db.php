<?php
/**
 * Export OMClub and OMClub Sponsors contacts directly from the local Sendy DB.
 *
 * Copy to /tmp on the Sendy server and run with SENDY_CONFIG pointing at the
 * Sendy config:
 *   SENDY_CONFIG=/www/htdocs/w010c8ea/sendy/includes/config.php php /tmp/export_sendy_omclub_contacts_db.php
 *
 * Writes two separate files:
 *   sendy_contacts_db_omclub.csv
 *   sendy_contacts_db_omclub_sponsors.csv
 */

error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

$omclub_output = $argv[1] ?? 'sendy_contacts_db_omclub.csv';
$sponsors_output = $argv[2] ?? 'sendy_contacts_db_omclub_sponsors.csv';

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

function language_values_from_list_name($name) {
    $values = [];
    if (strpos($name, 'GER') !== false || strpos($name, 'DE') !== false) $values[] = 'de';
    if (strpos($name, 'ENG') !== false || strpos($name, 'EN') !== false) $values[] = 'en';
    return $values;
}

function language_values_from_email($email) {
    $domain = strtolower(substr(strrchr((string)$email, '@') ?: '', 1));
    if ($domain === '') return [];
    $values = [];
    if (preg_match('/\.(de|at|ch)$/', $domain)) $values[] = 'de';
    if (preg_match('/\.(uk|co\.uk|us|ie|au|nz|ca)$/', $domain)) $values[] = 'en';
    return $values;
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

function merge_statuses($existing, $incoming) {
    $seen = array_flip(array_filter(array_map('trim', explode(';', (string)$existing))));
    foreach (array_filter(array_map('trim', explode(';', (string)$incoming))) as $part) {
        $seen[$part] = true;
    }
    $order = ['active', 'unconfirmed', 'unsubscribed', 'bounced', 'complained'];
    $values = [];
    foreach ($order as $status) {
        if (isset($seen[$status])) $values[] = $status;
    }
    foreach (array_keys($seen) as $status) {
        if (!in_array($status, $values, true)) $values[] = $status;
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
    $merged[$email]['languages'] = merge_values($merged[$email]['languages'] ?? '', $row['languages'] ?? '');
    $merged[$email]['source_list_names'] = merge_values($merged[$email]['source_list_names'] ?? '', $row['source_list_names'] ?? '');
    $merged[$email]['status'] = merge_statuses($merged[$email]['status'] ?? '', $row['status'] ?? '');
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
        'source_list_names' => trim((string)($row['source_list_names'] ?? '')),
        'status' => trim((string)($row['status'] ?? '')),
        'joined' => trim((string)($row['joined'] ?? $row['timestamp'] ?? '')),
    ];
}

function write_csv($path, $rows) {
    $columns = ['full_name', 'first_name', 'last_name', 'company', 'email', 'languages', 'source_list_names', 'status', 'joined'];
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
if (!$scols || !$lcols || !$acols) {
    fwrite(STDERR, "Expected Sendy tables 'subscribers', 'lists', and 'apps' were not found\n");
    exit(2);
}

$subscriber_list_col = pick($scols, ['list', 'list_id']);
$list_id_col = pick($lcols, ['id']);
$list_name_col = pick($lcols, ['name', 'list_name']);
$list_app_col = pick($lcols, ['app', 'app_id', 'brand', 'brand_id', 'userID']);
$app_id_col = pick($acols, ['id']);
$app_name_col = pick($acols, ['app_name', 'name', 'from_name']);
if ($subscriber_list_col === '' || $list_id_col === '' || $list_name_col === '' || $list_app_col === '' || $app_id_col === '' || $app_name_col === '') {
    fwrite(STDERR, "Could not identify Sendy subscriber/list/app relationship columns\n");
    exit(2);
}

$selects = [];
foreach ($scols as $col) $selects[] = 's.' . qid($col) . ' AS ' . qid($col);
$selects[] = 'l.' . qid($list_name_col) . ' AS source_list_names';
$selects[] = 'a.' . qid($app_name_col) . ' AS source_brand_name';

$brand_filter = "LOWER(a." . qid($app_name_col) . ") IN ('omclub', 'omclub sponsors')";
$sql = 'SELECT ' . implode(', ', $selects) .
       ' FROM ' . qid('subscribers') . ' s ' .
       'INNER JOIN ' . qid('lists') . ' l ON s.' . qid($subscriber_list_col) . ' = l.' . qid($list_id_col) .
       ' INNER JOIN ' . qid('apps') . ' a ON l.' . qid($list_app_col) . ' = a.' . qid($app_id_col) .
       ' WHERE ' . $brand_filter .
       ' ORDER BY source_brand_name, source_list_names, email';

$res = $mysqli->query($sql, MYSQLI_USE_RESULT);
if (!$res) {
    fwrite(STDERR, "DB query failed: " . $mysqli->error . "\n");
    exit(2);
}

$merged = [
    'omclub' => [],
    'omclub sponsors' => [],
];
while ($row = $res->fetch_assoc()) {
    $brand = strtolower(trim((string)($row['source_brand_name'] ?? '')));
    if (!isset($merged[$brand])) continue;

    $language_values = array_merge(
        language_values_from_list_name($row['source_list_names'] ?? ''),
        language_values_from_email($row['email'] ?? '')
    );
    $row['languages'] = implode(';', array_unique($language_values));
    $row['status'] = derive_status($row);
    merge_row($merged[$brand], $row);
}

$omclub_rows = array_map('curated_row', array_values($merged['omclub']));
$sponsors_rows = array_map('curated_row', array_values($merged['omclub sponsors']));

write_csv($omclub_output, $omclub_rows);
write_csv($sponsors_output, $sponsors_rows);

fwrite(STDERR, "Wrote " . count($omclub_rows) . " OMClub contact row(s) to $omclub_output\n");
fwrite(STDERR, "Wrote " . count($sponsors_rows) . " OMClub Sponsors contact row(s) to $sponsors_output\n");
?>
