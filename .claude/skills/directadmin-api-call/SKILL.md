---
name: directadmin-api-call
description: Creates a DirectAdmin API call using src/HTTPSocket.php. Use when user says 'call DirectAdmin API', 'query DirectAdmin', 'add HTTPSocket call', or adds code to src/ or bin/ that needs to talk to a DirectAdmin server. Covers connect/set_login/set_method/query/fetch_parsed_body pattern with SSL and error checking. Do NOT use for modifying existing API calls or for non-DirectAdmin HTTP requests.
---
# directadmin-api-call

## Critical

- Always connect to port `2222` — never 80, 443, or 8080.
- Always call `fetch_body()` before `fetch_parsed_body()` when you need both; `fetch_parsed_body()` alone is sufficient when you only need the parsed result.
- Always check `$result['error'] != "0"` (string comparison, not `!== 0`) — DirectAdmin returns `"0"` as a string for success.
- Never interpolate unescaped user input into query strings — pass parameters as an array to `query()`.
- SSL: use the ssl scheme prefix in `src/HTTPSocket.php` connect calls only when `$server_ssl == 'Y'`; never hardcode the scheme.
- Always log both the request and response using `request_log()` + `myadmin_log()` inside Plugin.php handlers.

## Instructions

### Step 1 — Import HTTPSocket

In `src/Plugin.php` the class is already in the same namespace, so use it directly. In `bin/` scripts, require it explicitly:

```php
// In bin/ scripts:
require_once __DIR__.'/../vendor/autoload.php';
use Detain\MyAdminDirectAdminWeb\HTTPSocket;

// In src/Plugin.php (already namespaced, no import needed):
$sock = new HTTPSocket();
```

Verify `src/HTTPSocket.php` exists before proceeding.

### Step 2 — Obtain server credentials

Inside Plugin.php handlers, always fetch credentials from the service master record:

```php
$settings   = get_module_settings(self::$module);
$serverdata = get_service_master($serviceClass->getServer(), self::$module);
$hash       = $serverdata[$settings['PREFIX'].'_key'];  // admin API key / password
$ip         = $serverdata[$settings['PREFIX'].'_ip'];   // server IP or hostname
$server_ssl = 'Y';                                      // hardcoded to SSL for DirectAdmin
```

In `bin/` scripts, define credentials directly at the top of the file (no service layer available):

```php
$ip         = '1.2.3.4';
$hash       = 'adminpassword';
$server_ssl = 'Y';
```

### Step 3 — Connect and authenticate

```php
$sock = new HTTPSocket();
$sock->connect(($server_ssl == 'Y' ? 'ssl://' : '').$ip, 2222);
$sock->set_login('admin', $hash);
```

For user-impersonation (acting as a user via admin credentials):

```php
$sock->set_login('admin|'.$username, $hash);
```

Verify the connect line uses exactly port `2222` before proceeding.

### Step 4 — Set method and build the query

GET (default — no `set_method` needed):

```php
$sock->query('/CMD_API_SHOW_USER_CONFIG', ['user' => $username]);
```

POST:

```php
$sock->set_method('POST');
$apiCmd     = '/CMD_API_ACCOUNT_USER';
$apiOptions = [
    'action'   => 'create',
    'add'      => 'Submit',
    'username' => $username,
    'email'    => $email,
    'passwd'   => $pass,
    'passwd2'  => $pass,
    'domain'   => $domain,
    'package'  => $package,
    'ip'       => $server_ip,
    'notify'   => 'yes',
];
$sock->query($apiCmd, $apiOptions);
```

Save `$apiCmd` and `$apiOptions` as variables when inside Plugin.php — they are passed to `request_log()` in Step 6.

### Step 5 — Fetch the response

When you need both raw and parsed (Plugin.php handlers):

```php
$rawResult = $sock->fetch_body();
$result    = $sock->fetch_parsed_body();
```

When you only need parsed (bin/ scripts or read-only calls):

```php
$result = $sock->fetch_parsed_body();
```

### Step 6 — Log request and response (Plugin.php only)

Do this immediately after fetching, before error checking:

```php
request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'directadmin', $apiCmd, $apiOptions, $rawResult, $serviceClass->getId());
myadmin_log('myadmin', 'info', 'DirectAdmin '.$apiCmd.' '.json_encode($apiOptions).' : '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
```

This step uses `$apiCmd`, `$apiOptions`, `$rawResult` from Steps 4–5.

### Step 7 — Check for errors

```php
if ($result['error'] != '0') {
    if ((isset($result['text'])    && trim($result['text'])    != '') ||
        (isset($result['details']) && trim($result['details']) != '')) {
        myadmin_log('directadmin', 'error',
            'Error on '.$apiCmd.' user:'.$username.' Text:'.$result['text'].' Details:'.$result['details'],
            __LINE__, __FILE__, self::$module, $serviceClass->getId());
        chatNotify(
            'Failed [Website '.$serviceClass->getId().'](https://my.interserver.net/admin/view_website?id='.$serviceClass->getId().') '.$apiCmd.' Text:'.$result['text'].' Details:'.$result['details'],
            'int-dev'
        );
        $event['success'] = false;
        $event->stopPropagation();
        return;
    }
}
// Success path
$event['success'] = true;
$event->stopPropagation();
return true;
```

In `bin/` scripts (no event object), print errors directly:

```php
if ($result['error'] != '0') {
    echo 'Error: '.$result['text'].PHP_EOL;
    if (!empty($result['details'])) {
        echo 'Details: '.$result['details'].PHP_EOL;
    }
    exit(1);
}
```

## Examples

**User says:** "Add a DirectAdmin call to suspend a user in the deactivate handler."

**Actions taken:**

1. Open `src/Plugin.php`, locate `getDeactivate()`.
2. After obtaining `$ip`, `$hash`, and `$username` from the service class:

```php
$sock = new HTTPSocket();
$sock->connect(($server_ssl == 'Y' ? 'ssl://' : '').$ip, 2222);
$sock->set_login('admin', $hash);
$sock->set_method('POST');
$apiCmd     = '/CMD_API_SELECT_USERS';
$apiOptions = [
    'location' => 'CMD_SELECT_USERS',
    'suspend'  => 'Suspend',
    'select0'  => $username,
];
$sock->query($apiCmd, $apiOptions);
$rawResult = $sock->fetch_body();
$result    = $sock->fetch_parsed_body();
request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'directadmin', $apiCmd, $apiOptions, $rawResult, $serviceClass->getId());
myadmin_log('myadmin', 'info', 'DirectAdmin '.$apiCmd.' '.json_encode($apiOptions).' : '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
if ($result['error'] != '0') {
    myadmin_log('directadmin', 'error', 'Error suspending '.$username.' Text:'.$result['text'].' Details:'.$result['details'], __LINE__, __FILE__, self::$module, $serviceClass->getId());
    chatNotify('Failed suspension [Website '.$serviceClass->getId().'] Text:'.$result['text'].' Details:'.$result['details'], 'int-dev');
    $event['success'] = false;
    $event->stopPropagation();
    return;
}
$event['success'] = true;
$event->stopPropagation();
return true;
```

3. Run `vendor/bin/phpunit tests/PluginTest.php` to verify no regressions.

**Result:** Suspend API call wired into the deactivate handler with logging and error handling.

## Common Issues

**`Connection refused` or blank `$result`:**
1. Confirm port is `2222`, not 80 or 443.
2. Check `$ip` is not empty: `var_dump($ip);`
3. Check SSL: if the server requires SSL and you omitted the ssl scheme prefix, the connection will fail silently.

**`$result['error']` is missing (array is empty or garbled):**
- You called `fetch_parsed_body()` after already draining the socket with `fetch_body()`. Call `fetch_body()` first, then `fetch_parsed_body()` — never the reverse.

**`Error: Username already exists` in `$result['text']`:**
- DirectAdmin returns `error=1` with this text. Check for it explicitly if idempotent creation is needed:
  ```php
  if ($result['error'] != '0' && strpos($result['text'], 'already exists') === false) { /* real error */ }
  ```

**`$result['error'] == 1` but `$result['text']` and `$result['details']` are both empty:**
- The raw body was not `parse_str`-decodable. Dump `$rawResult` to diagnose the actual DirectAdmin response format.

**`set_login` with impersonation (`admin|username`) returns permission denied:**
- Impersonation requires the admin account to have `allow_login_as_users=yes` in DirectAdmin. Verify with `bin/show_user.php` first.
