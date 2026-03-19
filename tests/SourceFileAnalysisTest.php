<?php

namespace Detain\MyAdminDirectAdminWeb\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Static analysis tests that verify source file contents without executing DB-heavy code.
 */
class SourceFileAnalysisTest extends TestCase
{
    /**
     * @var string
     */
    private $srcDir;

    protected function setUp(): void
    {
        $this->srcDir = dirname(__DIR__) . '/src';
    }

    /**
     * Test that Plugin.php file exists.
     */
    public function testPluginFileExists(): void
    {
        $this->assertFileExists($this->srcDir . '/Plugin.php');
    }

    /**
     * Test that HTTPSocket.php file exists.
     */
    public function testHttpSocketFileExists(): void
    {
        $this->assertFileExists($this->srcDir . '/HTTPSocket.php');
    }

    /**
     * Test that api_auto_directadmin_login.php file exists.
     */
    public function testApiAutoLoginFileExists(): void
    {
        $this->assertFileExists($this->srcDir . '/api_auto_directadmin_login.php');
    }

    /**
     * Test that unhtmlentities.php file exists.
     */
    public function testUnhtmlentitiesFileExists(): void
    {
        $this->assertFileExists($this->srcDir . '/unhtmlentities.php');
    }

    /**
     * Test that Plugin.php declares the correct namespace.
     */
    public function testPluginNamespace(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        $this->assertStringContainsString('namespace Detain\\MyAdminDirectAdminWeb;', $content);
    }

    /**
     * Test that HTTPSocket.php declares the correct namespace.
     */
    public function testHttpSocketNamespace(): void
    {
        $content = file_get_contents($this->srcDir . '/HTTPSocket.php');
        $this->assertStringContainsString('namespace Detain\\MyAdminDirectAdminWeb;', $content);
    }

    /**
     * Test that Plugin.php uses GenericEvent.
     */
    public function testPluginUsesGenericEvent(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        $this->assertStringContainsString('use Symfony\\Component\\EventDispatcher\\GenericEvent;', $content);
    }

    /**
     * Test that Plugin.php uses HTTPSocket.
     */
    public function testPluginUsesHttpSocket(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        $this->assertStringContainsString('use Detain\\MyAdminDirectAdminWeb\\HTTPSocket;', $content);
    }

    /**
     * Test that Plugin.php declares class Plugin.
     */
    public function testPluginDeclaresClass(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        $this->assertMatchesRegularExpression('/class\s+Plugin/', $content);
    }

    /**
     * Test that HTTPSocket.php declares class HTTPSocket.
     */
    public function testHttpSocketDeclaresClass(): void
    {
        $content = file_get_contents($this->srcDir . '/HTTPSocket.php');
        $this->assertMatchesRegularExpression('/class\s+HTTPSocket/', $content);
    }

    /**
     * Test that Plugin.php contains getHooks method.
     */
    public function testPluginContainsGetHooksMethod(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        $this->assertStringContainsString('function getHooks()', $content);
    }

    /**
     * Test that Plugin.php contains getActivate method.
     */
    public function testPluginContainsGetActivateMethod(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        $this->assertStringContainsString('function getActivate(', $content);
    }

    /**
     * Test that Plugin.php contains getDeactivate method.
     */
    public function testPluginContainsGetDeactivateMethod(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        $this->assertStringContainsString('function getDeactivate(', $content);
    }

    /**
     * Test that Plugin.php contains getTerminate method.
     */
    public function testPluginContainsGetTerminateMethod(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        $this->assertStringContainsString('function getTerminate(', $content);
    }

    /**
     * Test that Plugin.php contains getReactivate method.
     */
    public function testPluginContainsGetReactivateMethod(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        $this->assertStringContainsString('function getReactivate(', $content);
    }

    /**
     * Test that Plugin.php contains getSettings method.
     */
    public function testPluginContainsGetSettingsMethod(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        $this->assertStringContainsString('function getSettings(', $content);
    }

    /**
     * Test that Plugin.php references DirectAdmin API commands.
     */
    public function testPluginReferencesApiCommands(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        $this->assertStringContainsString('/CMD_API_ACCOUNT_USER', $content);
        $this->assertStringContainsString('/CMD_API_ACCOUNT_RESELLER', $content);
        $this->assertStringContainsString('/CMD_API_SELECT_USERS', $content);
        $this->assertStringContainsString('/CMD_API_DOMAIN', $content);
    }

    /**
     * Test that HTTPSocket.php contains curl-related code.
     */
    public function testHttpSocketUsesCurl(): void
    {
        $content = file_get_contents($this->srcDir . '/HTTPSocket.php');
        $this->assertStringContainsString('curl_init', $content);
        $this->assertStringContainsString('curl_setopt', $content);
        $this->assertStringContainsString('curl_exec', $content);
        $this->assertStringContainsString('curl_close', $content);
    }

    /**
     * Test that HTTPSocket.php version is 3.0.3.
     */
    public function testHttpSocketVersionInSource(): void
    {
        $content = file_get_contents($this->srcDir . '/HTTPSocket.php');
        $this->assertStringContainsString("'3.0.3'", $content);
    }

    /**
     * Test that api_auto_directadmin_login.php defines the function.
     */
    public function testApiAutoLoginDefinesFunction(): void
    {
        $content = file_get_contents($this->srcDir . '/api_auto_directadmin_login.php');
        $this->assertStringContainsString('function api_auto_directadmin_login(', $content);
    }

    /**
     * Test that api_auto_directadmin_login.php uses HTTPSocket.
     */
    public function testApiAutoLoginUsesHttpSocket(): void
    {
        $content = file_get_contents($this->srcDir . '/api_auto_directadmin_login.php');
        $this->assertStringContainsString('use Detain\\MyAdminDirectAdminWeb\\HTTPSocket;', $content);
    }

    /**
     * Test that api_auto_directadmin_login.php references the login keys API.
     */
    public function testApiAutoLoginReferencesLoginKeysApi(): void
    {
        $content = file_get_contents($this->srcDir . '/api_auto_directadmin_login.php');
        $this->assertStringContainsString('/CMD_API_LOGIN_KEYS', $content);
    }

    /**
     * Test that unhtmlentities.php defines the function.
     */
    public function testUnhtmlentitiesDefinesFunction(): void
    {
        $content = file_get_contents($this->srcDir . '/unhtmlentities.php');
        $this->assertStringContainsString('function unhtmlentities(', $content);
    }

    /**
     * Test that unhtmlentities.php uses preg_replace_callback.
     */
    public function testUnhtmlentitiesUsesRegex(): void
    {
        $content = file_get_contents($this->srcDir . '/unhtmlentities.php');
        $this->assertStringContainsString('preg_replace_callback', $content);
    }

    /**
     * Test that all PHP source files have opening PHP tags.
     */
    public function testAllSourceFilesHavePhpTag(): void
    {
        $files = glob($this->srcDir . '/*.php');
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $this->assertStringStartsWith('<?php', $content, basename($file) . ' should start with <?php');
        }
    }

    /**
     * Test that Plugin.php contains getChangeIp method.
     */
    public function testPluginContainsGetChangeIpMethod(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        $this->assertStringContainsString('function getChangeIp(', $content);
    }

    /**
     * Test that Plugin.php contains apiRegister method.
     */
    public function testPluginContainsApiRegisterMethod(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        $this->assertStringContainsString('function apiRegister(', $content);
    }

    /**
     * Test that Plugin.php contains getMenu method.
     */
    public function testPluginContainsGetMenuMethod(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        $this->assertStringContainsString('function getMenu(', $content);
    }

    /**
     * Test that Plugin.php contains getRequirements method.
     */
    public function testPluginContainsGetRequirementsMethod(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        $this->assertStringContainsString('function getRequirements(', $content);
    }

    /**
     * Test that HTTPSocket.php supports SSL connections.
     */
    public function testHttpSocketSupportsSSL(): void
    {
        $content = file_get_contents($this->srcDir . '/HTTPSocket.php');
        $this->assertStringContainsString('CURLOPT_SSL_VERIFYPEER', $content);
        $this->assertStringContainsString('CURLOPT_SSL_VERIFYHOST', $content);
    }

    /**
     * Test that HTTPSocket.php supports both GET and POST methods.
     */
    public function testHttpSocketSupportsGetAndPost(): void
    {
        $content = file_get_contents($this->srcDir . '/HTTPSocket.php');
        $this->assertStringContainsString("== 'GET'", $content);
        $this->assertStringContainsString("== 'POST'", $content);
    }

    /**
     * Test that Plugin activate uses port 2222 for DirectAdmin.
     */
    public function testPluginUsesPort2222(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        $this->assertStringContainsString('2222', $content);
    }

    /**
     * Test that HTTPSocket has user agent string.
     */
    public function testHttpSocketHasUserAgent(): void
    {
        $content = file_get_contents($this->srcDir . '/HTTPSocket.php');
        $this->assertStringContainsString('CURLOPT_USERAGENT', $content);
        $this->assertStringContainsString('HTTPSocket/', $content);
    }
}
