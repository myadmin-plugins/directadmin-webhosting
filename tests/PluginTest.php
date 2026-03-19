<?php

namespace Detain\MyAdminDirectAdminWeb\Tests;

use Detain\MyAdminDirectAdminWeb\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Tests for the Plugin class.
 */
class PluginTest extends TestCase
{
    /**
     * @var ReflectionClass
     */
    private $reflection;

    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(Plugin::class);
    }

    /**
     * Test that Plugin class exists and is instantiable.
     */
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(Plugin::class));
    }

    /**
     * Test that Plugin can be instantiated.
     */
    public function testCanBeInstantiated(): void
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    /**
     * Test that the class resides in the correct namespace.
     */
    public function testNamespace(): void
    {
        $this->assertSame('Detain\MyAdminDirectAdminWeb', $this->reflection->getNamespaceName());
    }

    /**
     * Test that $name static property has expected value.
     */
    public function testNameProperty(): void
    {
        $this->assertSame('DirectAdmin Webhosting', Plugin::$name);
    }

    /**
     * Test that $description static property is a non-empty string.
     */
    public function testDescriptionProperty(): void
    {
        $this->assertIsString(Plugin::$description);
        $this->assertNotEmpty(Plugin::$description);
        $this->assertStringContainsString('DirectAdmin', Plugin::$description);
    }

    /**
     * Test that $help static property exists and is a string.
     */
    public function testHelpProperty(): void
    {
        $this->assertIsString(Plugin::$help);
    }

    /**
     * Test that $module static property is 'webhosting'.
     */
    public function testModuleProperty(): void
    {
        $this->assertSame('webhosting', Plugin::$module);
    }

    /**
     * Test that $type static property is 'service'.
     */
    public function testTypeProperty(): void
    {
        $this->assertSame('service', Plugin::$type);
    }

    /**
     * Test that all expected static properties exist.
     */
    public function testStaticPropertiesExist(): void
    {
        $expectedProperties = ['name', 'description', 'help', 'module', 'type'];
        foreach ($expectedProperties as $prop) {
            $this->assertTrue(
                $this->reflection->hasProperty($prop),
                "Expected static property \${$prop} to exist"
            );
            $refProp = $this->reflection->getProperty($prop);
            $this->assertTrue($refProp->isStatic(), "\${$prop} should be static");
            $this->assertTrue($refProp->isPublic(), "\${$prop} should be public");
        }
    }

    /**
     * Test that getHooks returns an array.
     */
    public function testGetHooksReturnsArray(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertIsArray($hooks);
    }

    /**
     * Test that getHooks contains expected event keys.
     */
    public function testGetHooksContainsExpectedKeys(): void
    {
        $hooks = Plugin::getHooks();
        $expectedKeys = [
            'webhosting.settings',
            'webhosting.activate',
            'webhosting.reactivate',
            'webhosting.deactivate',
            'webhosting.terminate',
            'api.register',
            'function.requirements',
            'ui.menu',
        ];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $hooks, "Hook key '{$key}' should exist");
        }
    }

    /**
     * Test that getHooks values are valid callable arrays.
     */
    public function testGetHooksValuesAreCallableArrays(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $eventName => $handler) {
            $this->assertIsArray($handler, "Handler for '{$eventName}' should be an array");
            $this->assertCount(2, $handler, "Handler for '{$eventName}' should have 2 elements");
            $this->assertSame(Plugin::class, $handler[0], "Handler class should be Plugin for '{$eventName}'");
            $this->assertIsString($handler[1], "Handler method should be a string for '{$eventName}'");
        }
    }

    /**
     * Test that all handler methods referenced in getHooks exist on the class.
     */
    public function testGetHooksMethodsExist(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $eventName => $handler) {
            $methodName = $handler[1];
            $this->assertTrue(
                $this->reflection->hasMethod($methodName),
                "Method '{$methodName}' referenced in hook '{$eventName}' should exist"
            );
        }
    }

    /**
     * Test that all handler methods are public and static.
     */
    public function testGetHooksMethodsArePublicStatic(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $eventName => $handler) {
            $method = $this->reflection->getMethod($handler[1]);
            $this->assertTrue(
                $method->isPublic(),
                "Method '{$handler[1]}' should be public"
            );
            $this->assertTrue(
                $method->isStatic(),
                "Method '{$handler[1]}' should be static"
            );
        }
    }

    /**
     * Test that event handler methods accept GenericEvent as first parameter.
     */
    public function testEventHandlerSignatures(): void
    {
        $eventHandlerMethods = [
            'apiRegister',
            'getActivate',
            'getReactivate',
            'getDeactivate',
            'getTerminate',
            'getChangeIp',
            'getMenu',
            'getRequirements',
            'getSettings',
        ];

        foreach ($eventHandlerMethods as $methodName) {
            $method = $this->reflection->getMethod($methodName);
            $params = $method->getParameters();
            $this->assertGreaterThanOrEqual(
                1,
                count($params),
                "Method '{$methodName}' should accept at least one parameter"
            );
            $paramType = $params[0]->getType();
            $this->assertNotNull($paramType, "First parameter of '{$methodName}' should be type-hinted");
            $typeName = $paramType instanceof \ReflectionNamedType ? $paramType->getName() : (string)$paramType;
            $this->assertSame(
                'Symfony\Component\EventDispatcher\GenericEvent',
                $typeName,
                "First parameter of '{$methodName}' should be GenericEvent"
            );
        }
    }

    /**
     * Test that the number of hooks is exactly 8.
     */
    public function testGetHooksCount(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertCount(8, $hooks);
    }

    /**
     * Test that hook keys are prefixed with the module name or known system hooks.
     */
    public function testHookKeyPrefixes(): void
    {
        $hooks = Plugin::getHooks();
        $validPrefixes = ['webhosting.', 'api.', 'function.', 'ui.'];
        foreach (array_keys($hooks) as $key) {
            $matchesPrefix = false;
            foreach ($validPrefixes as $prefix) {
                if (strpos($key, $prefix) === 0) {
                    $matchesPrefix = true;
                    break;
                }
            }
            $this->assertTrue($matchesPrefix, "Hook key '{$key}' should start with a valid prefix");
        }
    }

    /**
     * Test that constructor has no required parameters.
     */
    public function testConstructorHasNoRequiredParams(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = $constructor->getParameters();
        foreach ($params as $param) {
            $this->assertTrue(
                $param->isOptional(),
                "Constructor parameter '{$param->getName()}' should be optional"
            );
        }
    }

    /**
     * Test that the class is not abstract.
     */
    public function testClassIsNotAbstract(): void
    {
        $this->assertFalse($this->reflection->isAbstract());
    }

    /**
     * Test that the class is not final.
     */
    public function testClassIsNotFinal(): void
    {
        $this->assertFalse($this->reflection->isFinal());
    }

    /**
     * Test the getMenu method exists and accepts GenericEvent.
     */
    public function testGetMenuMethodExists(): void
    {
        $method = $this->reflection->getMethod('getMenu');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $this->assertCount(1, $method->getParameters());
    }

    /**
     * Test the getRequirements method exists with correct signature.
     */
    public function testGetRequirementsMethodExists(): void
    {
        $method = $this->reflection->getMethod('getRequirements');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $this->assertCount(1, $method->getParameters());
    }

    /**
     * Test the getSettings method exists with correct signature.
     */
    public function testGetSettingsMethodExists(): void
    {
        $method = $this->reflection->getMethod('getSettings');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $this->assertCount(1, $method->getParameters());
    }
}
