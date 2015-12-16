<?php

namespace ActiveCollab\Etcd\Tests\Etcd;

use ActiveCollab\Etcd\Client;

/**
 * @package ActiveCollab\Etcd\Tests\Etcd
 */
class SettingsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test default server value
     */
    public function testDefaultServer()
    {
        $this->assertEquals('http://127.0.0.1:4001', (new Client())->getServer());
    }

    /**
     * Test set server
     */
    public function testSetServer()
    {
        $this->assertEquals('http://localhost:4001', (new Client())->setServer('http://localhost:4001/')->getServer());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetInvalidServerUrlException()
    {
        new Client('invalid server url');
    }

    /**
     * Test automatic detection of HTTPS
     */
    public function testHttpsDetection()
    {
        $reflection = new \ReflectionClass(Client::class);
        $is_https_property = $reflection->getProperty('is_https');
        $is_https_property->setAccessible(true);

        $this->assertFalse($is_https_property->getValue(new Client('http://127.0.0.1:4001')));
        $this->assertTrue($is_https_property->getValue(new Client('https://127.0.0.1:4001')));
    }

    /**
     * Test verify SSL peer can be
     */
    public function testVerifySslPeerCanBeSet()
    {
        $this->assertTrue((new Client())->getVerifySslPeer());
        $this->assertFalse((new Client())->verifySslPeer(false)->getVerifySslPeer());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionOnMissingCustomCaFile()
    {
        (new Client())->verifySslPeer(true, 'not a file');
    }

    /**
     * Test custom CA file can be set
     */
    public function testCustomCaFileCanBeSet()
    {
        $this->assertEquals(__FILE__, (new Client())->verifySslPeer(true, __FILE__)->getCustomCaFile());
    }

    /**
     * @expectedException \LogicException
     */
    public function testExceptionOnCustomCaFileWhenPeerIsNotVeified()
    {
        (new Client())->verifySslPeer(false, __FILE__)->getCustomCaFile();
    }

    /**
     * Test default root value
     */
    public function testDefaultRoot()
    {
        $this->assertEquals('/', (new Client())->getRoot());
    }

    /**
     * Test if root path is properly set
     */
    public function testSetRoot()
    {
        $this->assertEquals('/root', (new Client())->setRoot('root')->getRoot());
        $this->assertEquals('/root', (new Client())->setRoot('root/')->getRoot());
        $this->assertEquals('/root', (new Client())->setRoot('/root/')->getRoot());
        $this->assertEquals('/a/bit/deeper/path', (new Client())->setRoot('/a/bit/deeper/path/')->getRoot());
    }

    /**
     * Test default API version
     */
    public function testDefaultApiVersion()
    {
        $this->assertEquals('v2', (new Client())->getApiVersion());
    }

    /**
     * Test set API version
     */
    public function testSetApiVersion()
    {
        $this->assertEquals('v7', (new Client())->setApiVersion('v7')->getApiVersion());
    }

    /**
     * Test if API version is used in key path
     */
    public function testApiVersionIsUsedInKeyPath()
    {
        $this->assertEquals('/v2/keys/path/to/key', (new Client())->getKeyPath('path/to/key'));
        $this->assertEquals('/v7/keys/path/to/key', (new Client())->setApiVersion('v7')->getKeyPath('path/to/key'));
    }

    /**
     * Test if root is used in key path
     */
    public function testRootIsUsedInKeyPath()
    {
        $this->assertEquals('/v2/keys/path/to/key', (new Client())->getKeyPath('path/to/key'));
        $this->assertEquals('/v2/keys/root/is/cool/path/to/key', (new Client())->setRoot('root/is/cool')->getKeyPath('path/to/key'));
    }

    /**
     * Test how things fit together
     */
    public function testGetKeyUrl()
    {
        $clinet = (new Client('http://localhost:4001', 'v7'))->setRoot('awesome/root');
        $this->assertInstanceOf(Client::class, $clinet);

        $this->assertEquals('http://localhost:4001/v7/keys/awesome/root/path/to/key', $clinet->getKeyUrl('path/to/key'));
    }
}
