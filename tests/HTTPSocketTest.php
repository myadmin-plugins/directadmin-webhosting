<?php

namespace Detain\MyAdminDirectAdminWeb\Tests;

use Detain\MyAdminDirectAdminWeb\HTTPSocket;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Tests for the HTTPSocket class.
 */
class HTTPSocketTest extends TestCase
{
    /**
     * @var ReflectionClass
     */
    private $reflection;

    /**
     * @var HTTPSocket
     */
    private $socket;

    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(HTTPSocket::class);
        $this->socket = new HTTPSocket();
    }

    /**
     * Test that HTTPSocket class exists.
     */
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(HTTPSocket::class));
    }

    /**
     * Test that HTTPSocket can be instantiated without arguments.
     */
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(HTTPSocket::class, $this->socket);
    }

    /**
     * Test that the class is in the correct namespace.
     */
    public function testNamespace(): void
    {
        $this->assertSame('Detain\MyAdminDirectAdminWeb', $this->reflection->getNamespaceName());
    }

    /**
     * Test that the version property is set.
     */
    public function testVersionProperty(): void
    {
        $this->assertSame('3.0.3', $this->socket->version);
    }

    /**
     * Test default method is GET.
     */
    public function testDefaultMethodIsGet(): void
    {
        $this->assertSame('GET', $this->socket->method);
    }

    /**
     * Test default error array is empty.
     */
    public function testDefaultErrorIsEmptyArray(): void
    {
        $this->assertIsArray($this->socket->error);
        $this->assertEmpty($this->socket->error);
    }

    /**
     * Test default warn array is empty.
     */
    public function testDefaultWarnIsEmptyArray(): void
    {
        $this->assertIsArray($this->socket->warn);
        $this->assertEmpty($this->socket->warn);
    }

    /**
     * Test default query_cache is empty array.
     */
    public function testDefaultQueryCacheIsEmptyArray(): void
    {
        $this->assertIsArray($this->socket->query_cache);
        $this->assertEmpty($this->socket->query_cache);
    }

    /**
     * Test default doFollowLocationHeader is true.
     */
    public function testDefaultDoFollowLocationHeader(): void
    {
        $this->assertTrue($this->socket->doFollowLocationHeader);
    }

    /**
     * Test default max_redirects is 5.
     */
    public function testDefaultMaxRedirects(): void
    {
        $this->assertSame(5, $this->socket->max_redirects);
    }

    /**
     * Test default extra_headers is empty array.
     */
    public function testDefaultExtraHeaders(): void
    {
        $this->assertIsArray($this->socket->extra_headers);
        $this->assertEmpty($this->socket->extra_headers);
    }

    /**
     * Test that ssl_setting_message has a default value.
     */
    public function testDefaultSslSettingMessage(): void
    {
        $this->assertIsString($this->socket->ssl_setting_message);
        $this->assertNotEmpty($this->socket->ssl_setting_message);
    }

    /**
     * Test connect sets host and port.
     */
    public function testConnect(): void
    {
        $this->socket->connect('example.com', 8080);
        $this->assertSame('example.com', $this->socket->remote_host);
        $this->assertSame(8080, $this->socket->remote_port);
    }

    /**
     * Test connect with non-numeric port defaults to 80.
     */
    public function testConnectNonNumericPortDefaultsTo80(): void
    {
        $this->socket->connect('example.com', 'abc');
        $this->assertSame('example.com', $this->socket->remote_host);
        $this->assertSame(80, $this->socket->remote_port);
    }

    /**
     * Test connect with empty port defaults to 80.
     */
    public function testConnectEmptyPortDefaultsTo80(): void
    {
        $this->socket->connect('example.com', '');
        $this->assertSame(80, $this->socket->remote_port);
    }

    /**
     * Test set_method changes the method.
     */
    public function testSetMethod(): void
    {
        $this->socket->set_method('POST');
        $this->assertSame('POST', $this->socket->method);
    }

    /**
     * Test set_method converts to uppercase.
     */
    public function testSetMethodUppercases(): void
    {
        $this->socket->set_method('post');
        $this->assertSame('POST', $this->socket->method);
    }

    /**
     * Test set_method defaults to GET.
     */
    public function testSetMethodDefault(): void
    {
        $this->socket->set_method();
        $this->assertSame('GET', $this->socket->method);
    }

    /**
     * Test set_method with HEAD.
     */
    public function testSetMethodHead(): void
    {
        $this->socket->set_method('head');
        $this->assertSame('HEAD', $this->socket->method);
    }

    /**
     * Test set_login sets username and password.
     */
    public function testSetLogin(): void
    {
        $this->socket->set_login('admin', 'secret');
        $this->assertSame('admin', $this->socket->remote_uname);
        $this->assertSame('secret', $this->socket->remote_passwd);
    }

    /**
     * Test set_login with empty username does not set it.
     */
    public function testSetLoginEmptyUsername(): void
    {
        $this->socket->set_login('', 'secret');
        $this->assertNull($this->socket->remote_uname);
        $this->assertSame('secret', $this->socket->remote_passwd);
    }

    /**
     * Test set_login with empty password does not set it.
     */
    public function testSetLoginEmptyPassword(): void
    {
        $this->socket->set_login('admin', '');
        $this->assertSame('admin', $this->socket->remote_uname);
        $this->assertNull($this->socket->remote_passwd);
    }

    /**
     * Test add_header adds to extra_headers.
     */
    public function testAddHeader(): void
    {
        $this->socket->add_header('X-Custom', 'value');
        $this->assertArrayHasKey('X-Custom', $this->socket->extra_headers);
        $this->assertSame('value', $this->socket->extra_headers['X-Custom']);
    }

    /**
     * Test add_header overwrites existing header.
     */
    public function testAddHeaderOverwrites(): void
    {
        $this->socket->add_header('X-Custom', 'value1');
        $this->socket->add_header('X-Custom', 'value2');
        $this->assertSame('value2', $this->socket->extra_headers['X-Custom']);
    }

    /**
     * Test clear_headers empties extra_headers.
     */
    public function testClearHeaders(): void
    {
        $this->socket->add_header('X-Custom', 'value');
        $this->socket->clear_headers();
        $this->assertEmpty($this->socket->extra_headers);
    }

    /**
     * Test fetch_result returns result property.
     */
    public function testFetchResult(): void
    {
        $this->socket->result = 'test result';
        $this->assertSame('test result', $this->socket->fetch_result());
    }

    /**
     * Test fetch_body returns result_body property.
     */
    public function testFetchBody(): void
    {
        $this->socket->result_body = 'test body';
        $this->assertSame('test body', $this->socket->fetch_body());
    }

    /**
     * Test fetch_parsed_body parses URL-encoded body.
     */
    public function testFetchParsedBody(): void
    {
        $this->socket->result_body = 'key1=value1&key2=value2';
        $result = $this->socket->fetch_parsed_body();
        $this->assertIsArray($result);
        $this->assertSame('value1', $result['key1']);
        $this->assertSame('value2', $result['key2']);
    }

    /**
     * Test fetch_parsed_body with empty body.
     */
    public function testFetchParsedBodyEmpty(): void
    {
        $this->socket->result_body = '';
        $result = $this->socket->fetch_parsed_body();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test fetch_header parses header string.
     */
    public function testFetchHeader(): void
    {
        $this->socket->result_header = "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nX-Test: foo\r\n\r\n";
        $headers = $this->socket->fetch_header();
        $this->assertIsArray($headers);
        $this->assertSame('HTTP/1.1 200 OK', $headers[0]);
        $this->assertSame('text/html', $headers['content-type']);
        $this->assertSame('foo', $headers['x-test']);
    }

    /**
     * Test fetch_header with specific header name.
     */
    public function testFetchHeaderSpecific(): void
    {
        $this->socket->result_header = "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\n\r\n";
        $value = $this->socket->fetch_header('Content-Type');
        $this->assertSame('text/html', $value);
    }

    /**
     * Test get_status_code returns the status code.
     */
    public function testGetStatusCode(): void
    {
        $this->socket->result_status_code = 200;
        $this->assertSame(200, $this->socket->get_status_code());
    }

    /**
     * Test get_status_code returns null by default.
     */
    public function testGetStatusCodeDefaultNull(): void
    {
        $this->assertNull($this->socket->get_status_code());
    }

    /**
     * Test getTransferSpeed returns lastTransferSpeed.
     */
    public function testGetTransferSpeed(): void
    {
        $this->socket->lastTransferSpeed = 1024.5;
        $this->assertSame(1024.5, $this->socket->getTransferSpeed());
    }

    /**
     * Test set_ssl_setting_message changes the message.
     */
    public function testSetSslSettingMessage(): void
    {
        $msg = 'Custom SSL message';
        $this->socket->set_ssl_setting_message($msg);
        $this->assertSame($msg, $this->socket->ssl_setting_message);
    }

    /**
     * Test that all expected public properties exist.
     */
    public function testExpectedPropertiesExist(): void
    {
        $expectedProperties = [
            'version', 'method', 'remote_host', 'remote_port',
            'remote_uname', 'remote_passwd', 'result', 'result_header',
            'result_body', 'result_status_code', 'lastTransferSpeed',
            'bind_host', 'error', 'warn', 'query_cache',
            'doFollowLocationHeader', 'redirectURL', 'max_redirects',
            'ssl_setting_message', 'extra_headers',
        ];
        foreach ($expectedProperties as $prop) {
            $this->assertTrue(
                $this->reflection->hasProperty($prop),
                "Property '{$prop}' should exist"
            );
        }
    }

    /**
     * Test that all expected public methods exist.
     */
    public function testExpectedMethodsExist(): void
    {
        $expectedMethods = [
            'unhtmlentities', 'connect', 'bind', 'set_method',
            'set_login', 'query', 'getTransferSpeed', 'get',
            'get_status_code', 'add_header', 'clear_headers',
            'fetch_result', 'fetch_header', 'fetch_body',
            'fetch_parsed_body', 'set_ssl_setting_message',
        ];
        foreach ($expectedMethods as $method) {
            $this->assertTrue(
                $this->reflection->hasMethod($method),
                "Method '{$method}' should exist"
            );
        }
    }

    /**
     * Test unhtmlentities is a public static method.
     */
    public function testUnhtmlentitiesIsPublicStatic(): void
    {
        $method = $this->reflection->getMethod('unhtmlentities');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
    }

    /**
     * Test connect method signature.
     */
    public function testConnectMethodSignature(): void
    {
        $method = $this->reflection->getMethod('connect');
        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertSame('host', $params[0]->getName());
        $this->assertSame('port', $params[1]->getName());
        $this->assertTrue($params[1]->isOptional());
    }

    /**
     * Test query method signature.
     */
    public function testQueryMethodSignature(): void
    {
        $method = $this->reflection->getMethod('query');
        $params = $method->getParameters();
        $this->assertCount(3, $params);
        $this->assertSame('request', $params[0]->getName());
        $this->assertSame('content', $params[1]->getName());
        $this->assertSame('doSpeedCheck', $params[2]->getName());
        $this->assertTrue($params[1]->isOptional());
        $this->assertTrue($params[2]->isOptional());
    }

    /**
     * Test bind method stores ip.
     */
    public function testBindWithExplicitIp(): void
    {
        $this->socket->bind('192.168.1.1');
        $this->assertSame('192.168.1.1', $this->socket->bind_host);
    }

    /**
     * Test that multiple headers can be added.
     */
    public function testMultipleHeaders(): void
    {
        $this->socket->add_header('X-First', 'one');
        $this->socket->add_header('X-Second', 'two');
        $this->assertCount(2, $this->socket->extra_headers);
        $this->assertSame('one', $this->socket->extra_headers['X-First']);
        $this->assertSame('two', $this->socket->extra_headers['X-Second']);
    }

    /**
     * Test fetch_parsed_body with URL-encoded special characters.
     */
    public function testFetchParsedBodyWithEncodedValues(): void
    {
        $this->socket->result_body = 'name=hello+world&value=foo%26bar';
        $result = $this->socket->fetch_parsed_body();
        $this->assertSame('hello world', $result['name']);
        $this->assertSame('foo&bar', $result['value']);
    }

    /**
     * Test that the class is not abstract or final.
     */
    public function testClassIsConcreteAndNotFinal(): void
    {
        $this->assertFalse($this->reflection->isAbstract());
        $this->assertFalse($this->reflection->isFinal());
    }

    /**
     * Test that set_method with mixed case produces uppercase.
     */
    public function testSetMethodMixedCase(): void
    {
        $this->socket->set_method('PaTcH');
        $this->assertSame('PATCH', $this->socket->method);
    }

    /**
     * Test connect with SSL prefix host.
     */
    public function testConnectWithSslHost(): void
    {
        $this->socket->connect('ssl://example.com', 2222);
        $this->assertSame('ssl://example.com', $this->socket->remote_host);
        $this->assertSame(2222, $this->socket->remote_port);
    }

    /**
     * Test that get method exists with correct signature.
     */
    public function testGetMethodSignature(): void
    {
        $method = $this->reflection->getMethod('get');
        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertSame('location', $params[0]->getName());
        $this->assertSame('asArray', $params[1]->getName());
        $this->assertTrue($params[1]->isOptional());
    }
}
