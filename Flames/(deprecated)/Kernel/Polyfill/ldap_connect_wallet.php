<?php
declare(strict_types=1);


function ldap_connect_wallet(?string $uri, string $wallet, #[\SensitiveParameter] string $password, int $auth_mode = \GSLC_SSL_NO_AUTH): \LDAP\Connection|false { return ldap_connect($uri, $wallet, $password, $auth_mode); }
