<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$ldapConfig = [
    'ldap_host' => 'ad.uonbi.ac.ke',
    'ldap_port' => 389,
    'ldap_dn' => 'dc=AD,dc=UONBI,dc=AC,dc=KE',
    'ldap_user' => 'cn=pwdappuser,cn=Users,dc=AD,dc=UONBI,dc=AC,dc=KE',
    'ldap_password' => 'Kenya@2030',
];

header('Content-Type: application/json');

// Check if LDAP extension is loaded
if (!extension_loaded('ldap')) {
    echo json_encode(['error' => 'PHP LDAP extension is not enabled. Please enable it in your php.ini.']);
    exit;
}

$ds = ldap_connect($ldapConfig['ldap_host'], $ldapConfig['ldap_port']);

if (!$ds) {
    echo json_encode(['error' => 'Could not connect to LDAP server: ' . ldap_error($ds) . ' (' . ldap_errno($ds) . ')']);
    exit;
}

ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ds, LDAP_OPT_REFERRALS, 0); // Often needed for AD

$bind = @ldap_bind($ds, $ldapConfig['ldap_user'], $ldapConfig['ldap_password']);

if (!$bind) {
    echo json_encode(['error' => 'Could not bind to LDAP server: ' . ldap_error($ds) . ' (' . ldap_errno($ds) . ')']);
    exit;
}

// Use $_REQUEST to get parameters from both GET and POST
$dn = isset($_REQUEST['dn']) ? $_REQUEST['dn'] : null;
$get_attributes = isset($_REQUEST['get_attributes']) ? true : false;
$search_cn = isset($_REQUEST['search_cn']) ? $_REQUEST['search_cn'] : null;

if ($search_cn) {
    // Search for users by CN
    $filter = "(cn=*$search_cn*)";
    $search_result = @ldap_search($ds, $ldapConfig['ldap_dn'], $filter);

    if (!$search_result) {
        echo json_encode(['error' => 'Error in LDAP search: ' . ldap_error($ds) . ' (' . ldap_errno($ds) . ')']);
        exit;
    }

    $entries = ldap_get_entries($ds, $search_result);
    $results = [];
    for ($i = 0; $i < $entries['count']; $i++) {
        $results[] = [
            'dn' => $entries[$i]['dn'],
            'cn' => isset($entries[$i]['cn'][0]) ? $entries[$i]['cn'][0] : '',
            'mail' => isset($entries[$i]['mail'][0]) ? $entries[$i]['mail'][0] : '',
            'displayName' => isset($entries[$i]['displayname'][0]) ? $entries[$i]['displayname'][0] : ''
        ];
    }
    echo json_encode(['results' => $results]);

} else if ($get_attributes && $dn) {
    // Fetch attributes for a specific DN
    $result = @ldap_read($ds, $dn, 'objectClass=*');
    if (!$result) {
        echo json_encode(['error' => 'Error reading attributes: ' . ldap_error($ds) . ' (' . ldap_errno($ds) . ')']);
        exit;
    }
    $entries = ldap_get_entries($ds, $result);
    $attributes = [];
    if ($entries['count'] > 0) {
        foreach ($entries[0] as $key => $value) {
            if (is_numeric($key) || $key === 'count') {
                continue;
            }
            if (is_array($value)) {
                unset($value['count']);
                // Handle potential binary data or non-UTF8 characters
                foreach ($value as $k => $v) {
                    if (!mb_check_encoding($v, 'UTF-8')) {
                        $value[$k] = base64_encode($v) . ' (base64 encoded)'; // Encode binary/non-UTF8
                    }
                }
                $attributes[$key] = $value;
            } else {
                if (!mb_check_encoding($value, 'UTF-8')) {
                    $value = base64_encode($value) . ' (base64 encoded)'; // Encode binary/non-UTF8
                }
                $attributes[$key] = $value;
            }
        }
    }
    echo json_encode(['attributes' => $attributes]);

} else if ($dn) {
    // Fetch children for a specific DN
    $result = @ldap_list($ds, $dn, 'objectClass=*', ['dn']);

    if (!$result) {
        echo json_encode(['error' => 'Error in LDAP search: ' . ldap_error($ds) . ' (' . ldap_errno($ds) . ')']);
        exit;
    }

    $entries = ldap_get_entries($ds, $result);

    $response = [];
    for ($i = 0; $i < $entries['count']; $i++) {
        $response[] = ['dn' => $entries[$i]['dn']];
    }

    ldap_close($ds);

    $final_response = [];
    foreach ($response as $entry) {
        if ($entry['dn'] !== $dn) {
            $final_response[] = $entry;
        }
    }

    echo json_encode(['children' => array_values($final_response)]);

} else {
    echo json_encode(['error' => 'No DN provided.']);
}

?>