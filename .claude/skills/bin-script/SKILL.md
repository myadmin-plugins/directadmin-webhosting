---
name: bin-script
description: Creates a new CLI script in bin/ for DirectAdmin API operations. Use when user says 'add bin script', 'create CLI tool', 'new admin script', or needs a standalone operational script like bin/add_user.php or bin/suspend_user.php. Covers autoload require, HTTPSocket setup, SSL connect, arg parsing, and result printing. Do NOT use for src/ class code or Plugin.php event handlers.
---
# bin-script

## Critical

- All scripts live in `bin/` — never `src/`.
- Always use `use Detain\MyAdminDirectAdminWeb\HTTPSocket;` with the autoloader — never include the HTTPSocket source file directly (that is legacy, see `bin/add_domain.php`).
- Port is always `2222` — never 80, 443, or 8080.
- SSL: use the ssl scheme prefix when `$server_ssl == 'Y'`; otherwise connect bare host. Never use the http scheme prefix in new scripts.
- Use `fetch_parsed_body()` for structured results; use `fetch_body()` only when you need raw string output.
- Check `$result['error'] != "0"` for API errors; error detail is in `$result['text']` and `$result['details']`.
- Never interpolate raw `$_GET`/`$_POST` into queries — these are CLI scripts; use `$_SERVER['argv']` for input.

## Instructions

### 1. Choose the bootstrap style

Two patterns exist. Pick one based on whether the script needs live DB server credentials:

**A — Hardcoded / dev credentials** (like `bin/add_user.php`, `bin/suspend_user.php`):
```php
require_once('../vendor/autoload.php');
```
Used for quick standalone scripts where credentials are set directly in the file.

**B — MyAdmin DB lookup** (like `bin/show_all_users.php`, `bin/server_information.php`):
```php
include_once __DIR__.'/../../../../include/functions.inc.php';
```
Used when the script must look up a real server from `website_masters`. Requires running inside a MyAdmin checkout.

Verify your chosen autoload path resolves before writing the rest of the script.

### 2. Write the file header

```php
<?php

use Detain\MyAdminDirectAdminWeb\HTTPSocket;

// Step 1 bootstrap here (require_once or include_once)
```

### 3. Set server connection variables

For pattern A:
```php
$server_login = 'admin';
$server_pass  = 'admin_password';
$server_host  = 'da1.is.cc';   // where the API connects to
$server_ssl   = 'Y';
$server_port  = 2222;
```

For pattern B (DB lookup):
```php
$db = get_module_db('webhosting');
if (count($_SERVER['argv']) < 2) {
    die("Call like {$_SERVER['argv'][0]} <hostname>\nwhere <hostname> is a webhosting server such as webhosting2004.interserver.net");
}
$db->query("select * from website_masters where website_name='".$db->real_escape($_SERVER['argv'][1])."'", __LINE__, __FILE__);
if ($db->num_rows() == 0) {
    die("Invalid Server {$_SERVER['argv'][1]} passed, did not match any webhosting server name");
}
$db->next_record(MYSQL_ASSOC);
$server_name = $db->Record['website_ip'];
$server_port = 2222;
$password    = $db->Record['website_key'];
```

### 4. Connect HTTPSocket

For pattern A:
```php
$sock = new HTTPSocket();
if ($server_ssl == 'Y') {
    $sock->connect('ssl://'.$server_host, $server_port);
} else {
    $sock->connect($server_host, $server_port);
}
$sock->set_login($server_login, $server_pass);
```

For pattern B:
```php
$sock = new HTTPSocket();
$sock->connect('ssl://'.$server_name, $server_port);
$sock->set_login('admin', $password);
```

For POST requests, add `$sock->set_method('POST');` immediately after `set_login()`.

For admin impersonation: `$sock->set_login('admin|'.$username, $adminPass);`

Verify `HTTPSocket` is importable: run `vendor/bin/phpunit tests/HTTPSocketTest.php`.

### 5. Issue the DirectAdmin API query

GET (no payload — pass params as array):
```php
$sock->query('/CMD_API_ENDPOINT', ['param' => $value]);
```

POST (with payload array):
```php
$sock->query(
    '/CMD_API_ENDPOINT',
    [
        'action' => 'create',
        'key'    => $value,
    ]
);
```

Common endpoints and their required fields:

| Action | Endpoint | Key fields |
|---|---|---|
| Create user | `/CMD_API_ACCOUNT_USER` | `action=create`, `username`, `email`, `passwd`, `passwd2`, `domain`, `package`, `ip` |
| Suspend user | `/CMD_API_SELECT_USERS` | `location=CMD_SELECT_USERS`, `suspend=Suspend`, `select0=$username` |
| Unsuspend user | `/CMD_API_SELECT_USERS` | `location=CMD_SELECT_USERS`, `suspend=Unsuspend`, `select0=$username` |
| Delete user | `/CMD_API_SELECT_USERS` | `confirmed=Confirm`, `delete=yes`, `select0=$username` |
| Show user config | `/CMD_API_SHOW_USER_CONFIG` | `user=$username` |
| All users | `/CMD_API_SHOW_ALL_USERS` | — |
| Server stats | `/CMD_API_ADMIN_STATS` | — |

### 6. Fetch and print the result

```php
$result = $sock->fetch_parsed_body();   // array via parse_str
print_r($result);

if ($result['error'] != '0') {
    echo "<b>Error: <br>\n";
    echo $result['text']."<br>\n";
    echo $result['details']."<br></b>\n";
} else {
    echo "Success<br>\n";
}

exit(0);
```

Omit the error-check block for read-only/info queries that don't return an `error` key (e.g. `CMD_API_SHOW_ALL_USERS` returns a list).

## Examples

**User says:** "Add a bin script to add a subdomain for a user"

**Actions taken:**
1. Pattern A chosen (dev/standalone, no DB needed).
2. File created at `bin/add_subdomain.php`.
3. Credentials set as variables; SSL connect to port 2222.
4. `set_method('POST')` added because this is a write operation.
5. Query to `/CMD_API_SUBDOMAINS` with `action=create`.
6. `fetch_parsed_body()` called, error checked.

**Result:**
```php
<?php

use Detain\MyAdminDirectAdminWeb\HTTPSocket;

require_once('../vendor/autoload.php');

$server_login = 'admin';
$server_pass  = 'admin_password';
$server_host  = 'da1.is.cc';   // where the API connects to
$server_ssl   = 'Y';
$server_port  = 2222;

$username   = 'targetuser';
$subdomain  = 'sub';
$domain     = 'example.com';

$sock = new HTTPSocket();
if ($server_ssl == 'Y') {
    $sock->connect('ssl://'.$server_host, $server_port);
} else {
    $sock->connect($server_host, $server_port);
}
$sock->set_login('admin|'.$username, $server_pass);
$sock->set_method('POST');

$sock->query(
    '/CMD_API_SUBDOMAINS',
    [
        'action' => 'create',
        'domain' => $domain,
        'subdomain' => $subdomain,
    ]
);

$result = $sock->fetch_parsed_body();
print_r($result);

if ($result['error'] != '0') {
    echo "<b>Error creating subdomain {$subdomain}.{$domain}:<br>\n";
    echo $result['text']."<br>\n";
    echo $result['details']."<br></b>\n";
} else {
    echo "Subdomain {$subdomain}.{$domain} created<br>\n";
}

exit(0);
```

## Common Issues

**`Class 'Detain\MyAdminDirectAdminWeb\HTTPSocket' not found`**
- You used a direct include of the HTTPSocket source file instead of the autoloader.
- Fix: use `require_once('../vendor/autoload.php');` and add `use Detain\MyAdminDirectAdminWeb\HTTPSocket;` at top.
- Verify: `vendor/bin/phpunit tests/HTTPSocketTest.php` must pass.

**`$result['error']` is undefined / `print_r` shows empty array**
- You called `fetch_parsed_body()` on an endpoint that returns raw text (e.g. `CMD_API_SHOW_ALL_USERS` returns a list).
- Fix: switch to `fetch_body()` and parse manually, or check DirectAdmin docs for that endpoint's response format.

**`Connection refused` or SSL handshake failure**
- Port 2222 must be open on the target server.
- The ssl scheme prefix is required when `$server_ssl == 'Y'` — omitting it causes a plaintext-on-TLS mismatch.
- Fix: confirm with `openssl s_client -connect da1.is.cc:2222`.

**Pattern B: `Invalid Server ... passed, did not match any webhosting server name`**
- The `$_SERVER['argv'][1]` value doesn't match a `website_name` in `website_masters`.
- Fix: check valid hostnames with `SELECT website_name FROM website_masters LIMIT 10;`.

**Script works locally but `include_once __DIR__.'/../../../..'` fails in another checkout depth**
- Pattern B hard-codes four levels up to the MyAdmin root.
- Fix: use pattern A with hardcoded credentials for portable standalone scripts, or adjust the relative path to match your checkout structure.
