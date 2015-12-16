<?php

namespace ActiveCollab\Etcd\Tests\Etcd;

use ActiveCollab\Etcd\Client;

/**
 * @package ActiveCollab\Etcd\Tests\Etcd
 */
class VersionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers LinkORB\Component\Etcd\Client::doRequest
     */
    public function testGetVersion()
    {
        $version = (new Client())->geVersion();

        $this->assertInternalType('array', $version);
        $this->assertArrayHasKey('etcdserver', $version);
        $this->assertArrayHasKey('etcdcluster', $version);
    }
}