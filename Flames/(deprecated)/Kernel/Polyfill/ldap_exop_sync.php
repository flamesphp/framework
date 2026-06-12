<?php
declare(strict_types=1);


function ldap_exop_sync(\LDAP\Connection $ldap, string $request_oid, ?string $request_data = null, ?array $controls = null, &$response_data = null, &$response_oid = null): bool { return ldap_exop($ldap, $request_oid, $request_data, $controls, $response_data, $response_oid); }