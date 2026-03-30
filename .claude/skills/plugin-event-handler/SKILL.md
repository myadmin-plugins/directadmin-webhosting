---
name: plugin-event-handler
description: Adds a new lifecycle event handler to src/Plugin.php following the GenericEvent pattern. Use when user says 'add event handler', 'handle new event', 'add hook', or wants to respond to a new webhosting.* event. Covers getHooks() registration, type check, service data retrieval, myadmin_log + request_log pairs, and event success/stopPropagation pattern. Do NOT use for modifying existing handlers or non-webhosting.* events.
---
# Plugin Event Handler

## Critical

- **Always** guard the entire handler body with `if (in_array($event['type'], [get_service_define('WEB_DIRECTADMIN'), get_service_define('WEB_STORAGE')]))` — handlers fire for all webhosting types; without this check the handler runs on wrong service types.
- **Always** call `$event['success'] = true; $event->stopPropagation();` at the end of a successful path. Missing `stopPropagation()` lets other plugins continue processing.
- **Always** call `$event['success'] = false; $event->stopPropagation(); return;` on any error path before returning — never silently return.
- Every DirectAdmin API call must be followed by **both** `request_log(...)` and `myadmin_log(...)` — never one without the other.
- Never use PDO. Use `$db = get_module_db(self::$module)` and `$db->real_escape()` for any user data written to the DB.
- The method signature is always `public static function getHandlerName(GenericEvent $event)` — no other signature is valid.

## Instructions

### Step 1 — Add the hook registration to `getHooks()`

Open `src/Plugin.php` and find the `getHooks()` return array. Add a new entry using `self::$module` as the key prefix:

```php
public static function getHooks()
{
    return [
        self::$module.'.settings'   => [__CLASS__, 'getSettings'],
        self::$module.'.activate'   => [__CLASS__, 'getActivate'],
        self::$module.'.reactivate' => [__CLASS__, 'getReactivate'],
        self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
        self::$module.'.terminate'  => [__CLASS__, 'getTerminate'],
        self::$module.'.mynewevent' => [__CLASS__, 'getMyNewEvent'],  // ← add here
        'api.register'              => [__CLASS__, 'apiRegister'],
        'function.requirements'     => [__CLASS__, 'getRequirements'],
        'ui.menu'                   => [__CLASS__, 'getMenu'],
    ];
}
```

The event name key must match exactly what `run_event()` dispatches in the caller.

Verify: grep the MyAdmin core for the event name string to confirm it exists before proceeding.

### Step 2 — Add the PHPDoc block and method signature

Add the new method after the last lifecycle handler (before `getChangeIp` or `getMenu`). Always include the `@throws \Exception` tag when the handler makes HTTPSocket calls:

```php
/**
 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
 * @throws \Exception
 */
public static function getMyNewEvent(GenericEvent $event)
{
```

### Step 3 — Add the service-type guard and subject retrieval

This is the first thing inside the method body. Use `$event->getSubject()` to get the service object:

```php
if (in_array($event['type'], [get_service_define('WEB_DIRECTADMIN'), get_service_define('WEB_STORAGE')])) {
    $serviceClass = $event->getSubject();
    myadmin_log('myadmin', 'info', 'DirectAdmin MyNewEvent', __LINE__, __FILE__, self::$module, $serviceClass->getId());
```

Verify: `$serviceClass->getId()` and `$serviceClass->getCustid()` are available on the subject before using them.

### Step 4 — Retrieve settings and server credentials

Always load settings and server data before constructing the HTTPSocket:

```php
    $settings   = get_module_settings(self::$module);
    $serverdata = get_service_master($serviceClass->getServer(), self::$module);
    $hash       = $serverdata[$settings['PREFIX'].'_key'];
    $ip         = $serverdata[$settings['PREFIX'].'_ip'];
```

The `$settings['PREFIX']` string is the column prefix for this module's DB table (e.g. `website_`). Use it to build all DB column names.

### Step 5 — Construct and connect the HTTPSocket

Always hardcode port `2222`. Prefix the host with `ssl://` when SSL is enabled:

```php
    $server_ssl = 'Y';
    $sock = new HTTPSocket();
    $sock->connect(($server_ssl == 'Y' ? 'ssl://' : '').$ip, 2222);
    $sock->set_login('admin', $hash);
    // For POST requests only:
    // $sock->set_method('POST');
```

For impersonating a user instead of admin: `$sock->set_login('admin|'.$username, $hash)`.

### Step 6 — Build options array and issue the query

```php
    $apiCmd     = '/CMD_API_YOUR_ENDPOINT';
    $apiOptions = [
        'action'  => 'desired_action',
        'select0' => $serviceClass->getUsername(),
        // add additional fields as needed
    ];
    $sock->query($apiCmd, $apiOptions);
    $rawResult = $sock->fetch_body();
    $result    = $sock->fetch_parsed_body();
```

`fetch_body()` must be called before `fetch_parsed_body()` — both are needed: `$rawResult` goes to `request_log`, `$result` is used for error checking.

### Step 7 — Log the request (both loggers, always paired)

```php
    request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'directadmin', $apiCmd, $apiOptions, $rawResult, $serviceClass->getId());
    myadmin_log('myadmin', 'info', 'DirectAdmin '.$apiCmd.' Response: '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
```

Argument order for `request_log`: `($module, $custid, $function, $provider, $cmd, $options, $rawResult, $serviceId)`.

### Step 8 — Handle API errors and set event outcome

On error: set `$event['success'] = false`, call `stopPropagation()`, and return immediately.
On success: set `$event['success'] = true`, call `stopPropagation()`, return `true`.

```php
    if ($result['error'] != "0") {
        $event['success'] = false;
        myadmin_log('directadmin', 'error', 'Error in MyNewEvent Text:'.$result['text'].' Details:'.$result['details'], __LINE__, __FILE__, self::$module, $serviceClass->getId());
        $event->stopPropagation();
        return;
    }
    $event['success'] = true;
    $event->stopPropagation();
    return true;
}
```

Close the `if (in_array(...))` block before the closing `}` of the method — handlers that do not match the type guard must fall through without setting `$event['success']`.

## Examples

**User says:** "Add a handler for `webhosting.changepackage` that calls `/CMD_API_MODIFY_USER` on DirectAdmin."

**Actions taken:**
1. Add `self::$module.'.changepackage' => [__CLASS__, 'getChangePackage']` to `getHooks()`.
2. Add method:

```php
/**
 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
 * @throws \Exception
 */
public static function getChangePackage(GenericEvent $event)
{
    if (in_array($event['type'], [get_service_define('WEB_DIRECTADMIN'), get_service_define('WEB_STORAGE')])) {
        $serviceClass = $event->getSubject();
        myadmin_log('myadmin', 'info', 'DirectAdmin ChangePackage', __LINE__, __FILE__, self::$module, $serviceClass->getId());
        $settings   = get_module_settings(self::$module);
        $serverdata = get_service_master($serviceClass->getServer(), self::$module);
        $hash       = $serverdata[$settings['PREFIX'].'_key'];
        $ip         = $serverdata[$settings['PREFIX'].'_ip'];
        $server_ssl = 'Y';
        $sock = new HTTPSocket();
        $sock->connect(($server_ssl == 'Y' ? 'ssl://' : '').$ip, 2222);
        $sock->set_login('admin', $hash);
        $apiCmd     = '/CMD_API_MODIFY_USER';
        $apiOptions = [
            'action'   => 'package',
            'user'     => $serviceClass->getUsername(),
            'package'  => $event['package'],
        ];
        $sock->query($apiCmd, $apiOptions);
        $rawResult = $sock->fetch_body();
        $result    = $sock->fetch_parsed_body();
        request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'directadmin', $apiCmd, $apiOptions, $rawResult, $serviceClass->getId());
        myadmin_log('myadmin', 'info', 'DirectAdmin '.$apiCmd.' Response: '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
        if ($result['error'] != "0") {
            $event['success'] = false;
            myadmin_log('directadmin', 'error', 'Error ChangePackage Text:'.$result['text'].' Details:'.$result['details'], __LINE__, __FILE__, self::$module, $serviceClass->getId());
            $event->stopPropagation();
            return;
        }
        $event['success'] = true;
        $event->stopPropagation();
        return true;
    }
}
```

**Result:** `webhosting.changepackage` is dispatched → DirectAdmin `/CMD_API_MODIFY_USER` is called → both loggers record the transaction → event propagation stops.

## Common Issues

**`$result['error']` is never `"0"` even on success** — DirectAdmin returns the string `"0"`, not integer `0`. Always compare with `!= "0"` (loose string comparison), not `!== 0`.

**Handler fires but does nothing** — The `if (in_array($event['type'], ...))` guard is missing or uses the wrong `get_service_define()` constant. Run `grep -r 'get_service_define' src/` to confirm the correct constant names.

**`fetch_parsed_body()` returns empty array** — You called `fetch_parsed_body()` without first calling `fetch_body()`. Both must be called in sequence after `query()`.

**`$serverdata[$settings['PREFIX'].'_key']` is undefined** — `$serviceClass->getServer()` returned `0` (no server assigned). Add a guard: `if ($serviceClass->getServer() > 0)` before retrieving server data (see `getDeactivate()` for the pattern).

**SSL connection refused** — Confirm the server is accessible on port `2222` with SSL. If `$server_ssl` comes from `$serverdata`, check the column name matches `$settings['PREFIX'].'_ssl'`; do not hardcode `'Y'` in production handlers.

**Hook not firing** — Confirm the event key in `getHooks()` exactly matches the string passed to `run_event()` in the caller — e.g. `'webhosting.changepackage'` vs `'webHosting.changePackage'` will not match.