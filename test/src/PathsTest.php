<?php

namespace ActiveCollab\Etcd\Tests\Etcd;

use ActiveCollab\Etcd\Client;

/**
 * @package ActiveCollab\Etcd\Tests\Etcd
 */
class PathsTest extends \PHPUnit_Framework_TestCase
{
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
}