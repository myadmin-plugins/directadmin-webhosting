# MyAdmin DirectAdmin Webhosting Plugin

Composer plugin providing automated provisioning, suspension, reactivation, termination, and SSO login for DirectAdmin-based webhosting accounts.

## Commands

```bash
composer install                          # install deps
vendor/bin/phpunit                        # run all tests
vendor/bin/phpunit tests/PluginTest.php   # plugin tests only
vendor/bin/phpunit tests/HTTPSocketTest.php
vendor/bin/phpunit tests/SourceFileAnalysisTest.php
```

## Architecture

- **Namespace**: `Detain\MyAdminDirectAdminWeb\` → `src/`
- **Test namespace**: `Detain\MyAdminDirectAdminWeb\Tests\` → `tests/`
- **Autoload**: PSR-4 via `composer.json`
- **Entry**: `src/Plugin.php` — registers all event hooks via `getHooks()`
- **HTTP client**: `src/HTTPSocket.php` — curl-based client for DirectAdmin API on port `2222`
- **SSO**: `src/api_auto_directadmin_login.php` — `api_auto_directadmin_login($id)` function
- **Utility**: `src/unhtmlentities.php` — `unhtmlentities()` helper
- **CLI scripts**: `bin/` — standalone operational scripts (add_user, suspend_user, delete_user, etc.)
- **Tests**: `tests/PluginTest.php` · `tests/HTTPSocketTest.php` · `tests/SourceFileAnalysisTest.php`
- **Config**: `phpunit.xml.dist` · `.scrutinizer.yml` · `.codeclimate.yml`
- **CI/CD**: `.github/` — GitHub Actions workflows for automated testing and deployment

## Plugin Event Hooks

`src/Plugin.php::getHooks()` registers these events:

| Event | Method | DirectAdmin CMD |
|---|---|---|
| `webhosting.activate` | `getActivate()` | `/CMD_API_ACCOUNT_USER` or `/CMD_API_ACCOUNT_RESELLER` |
| `webhosting.reactivate` | `getReactivate()` | `/CMD_API_SELECT_USERS` (dounsuspend) |
| `webhosting.deactivate` | `getDeactivate()` | `/CMD_API_SELECT_USERS` (suspend) |
| `webhosting.terminate` | `getTerminate()` | `/CMD_API_SELECT_USERS` (delete) |
| `webhosting.settings` | `getSettings()` | — |
| `api.register` | `apiRegister()` | — |
| `function.requirements` | `getRequirements()` | — |
| `ui.menu` | `getMenu()` | — |

## HTTPSocket Pattern

All DirectAdmin API calls use `src/HTTPSocket.php`:

```php
use Detain\MyAdminDirectAdminWeb\HTTPSocket;

$sock = new HTTPSocket();
$sock->connect(($server_ssl == 'Y' ? 'ssl://' : '') . $host, 2222);
$sock->set_login('admin', $hash);          // or 'admin|username' for impersonation
$sock->set_method('POST');                  // only needed for POST
$sock->query('/CMD_API_ENDPOINT', [
    'action' => 'create',
    'key'    => 'value',
]);
$rawResult = $sock->fetch_body();           // raw string
$result    = $sock->fetch_parsed_body();    // parse_str decoded array
```

- Always connect to port `2222`
- SSL: prefix host with the ssl scheme when `$server_ssl == 'Y'`
- Admin impersonation: `set_login('admin|username', $adminPass)`
- Check `$result['error'] != "0"` for API errors; error details in `$result['text']` and `$result['details']`

## Event Handler Pattern

All lifecycle handlers in `src/Plugin.php` follow this structure:

```php
public static function getActivate(GenericEvent $event)
{
    if (in_array($event['type'], [get_service_define('WEB_DIRECTADMIN'), get_service_define('WEB_STORAGE')])) {
        $serviceClass = $event->getSubject();
        $settings = get_module_settings(self::$module);      // returns PREFIX, TABLE, etc.
        $serverdata = get_service_master($serviceClass->getServer(), self::$module);
        $hash = $serverdata[$settings['PREFIX'].'_key'];
        $ip   = $serverdata[$settings['PREFIX'].'_ip'];
        // ... HTTPSocket call ...
        request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'directadmin', $apiCmd, $apiOptions, $rawResult, $serviceClass->getId());
        myadmin_log('myadmin', 'info', 'DirectAdmin '.$apiCmd.' '.json_encode($apiOptions).' : '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
        $event['success'] = true;
        $event->stopPropagation();
        return true;
    }
}
```

## Coding Conventions

- Namespace: `Detain\MyAdminDirectAdminWeb` in all `src/` classes
- Indentation: tabs (enforced by `.scrutinizer.yml`)
- Log every API call: `myadmin_log()` + `request_log()` pair after each `$sock->query()`
- Error path: set `$event['success'] = false` + `chatNotify()` + `$event->stopPropagation()` then return
- Success path: set `$event['success'] = true` + `$event->stopPropagation()` + `return true`
- `self::$module` is always `'webhosting'`
- SSO login key: use `/CMD_API_LOGIN_KEYS` with `type=one_time_url` and `max_uses=2`

## Testing Conventions

- Tests use `ReflectionClass` to validate method signatures (see `tests/PluginTest.php`)
- `tests/SourceFileAnalysisTest.php` validates file existence and content patterns
- All test classes extend `PHPUnit\Framework\TestCase`
- Test namespace: `Detain\MyAdminDirectAdminWeb\Tests`
- Bootstrap: `vendor/autoload.php` (set in `phpunit.xml.dist`)

## Common DirectAdmin API Commands

- `/CMD_API_ACCOUNT_USER` — create hosting user
- `/CMD_API_ACCOUNT_RESELLER` — create reseller account
- `/CMD_API_SELECT_USERS` — suspend/unsuspend/delete users (use `select0` for username)
- `/CMD_API_DOMAIN` — manage domains
- `/CMD_API_LOGIN_KEYS` — create SSO login keys
- `/CMD_API_SHOW_USER_CONFIG` — get user config (returns `ip`, etc.)
- `/CMD_API_DATABASES` — manage databases
- `/CMD_API_SUBDOMAINS` — manage subdomains
- `/CMD_API_DNS_ADMIN` — manage DNS records

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md .agents/ .opencode/ 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->

<!-- caliber:managed:sync -->
## Context Sync

This project uses [Caliber](https://github.com/caliber-ai-org/ai-setup) to keep AI agent configs in sync across Claude Code, Cursor, Copilot, and Codex.
Configs update automatically before each commit via `caliber refresh`.
If the pre-commit hook is not set up, run `/setup-caliber` to configure everything automatically.
<!-- /caliber:managed:sync -->
