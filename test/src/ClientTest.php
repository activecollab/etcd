<?php

namespace ActiveCollab\Etcd\Tests\Etcd;

use ActiveCollab\Etcd\Client;
use ActiveCollab\Etcd\Exception\EtcdException;

/**
 * @package ActiveCollab\Etcd\Tests\Etcd
 */
class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    private $dirname = '/phpunit_test';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->client = new Client();

        $this->client->setRoot('/');

        try {
            $this->client->removeDir($this->dirname, true);
        } catch (EtcdException $e) {

        }

        $create_dir = $this->client->createDir($this->dirname);

        $this->assertInternalType('array', $create_dir);
        $this->assertEquals('create', $create_dir['action']);
        $this->assertInternalType('array', $create_dir['node']);
        $this->assertTrue($create_dir['node']['dir']);

        $this->client->setRoot($this->dirname);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        try {
            $this->client->removeDir($this->dirname, true);
        } catch (EtcdException $e) {

        }
    }

    public function testGet()
    {
        $this->client->set('/testgetvalue', 'getvalue');
        $value = $this->client->get('/testgetvalue');
        $this->assertEquals('getvalue', $value);
    }

    public function testSet()
    {
        $this->client->set('/testset', 'setvalue');
        $this->assertEquals('setvalue', $this->client->get('/testset'));
    }

    public function testSetWithTtl()
    {
        $ttl = 10;

        $this->client->set('testttl', 'ttlvalue', $ttl);
        $this->assertLessThanOrEqual($ttl, $this->client->getNode('testttl')['ttl']);
    }

    /**
     * @expectedException \ActiveCollab\Etcd\Exception\KeyExistsException
     */
    public function testMk()
    {
        $this->client->create('testmk', 'mkvalue');
        $this->assertEquals('mkvalue', $this->client->get('testmk'));
        $this->client->create('testmk', 'mkvalue');
    }

    /**
     * @expectedException \ActiveCollab\Etcd\Exception\KeyExistsException
     */
    public function testMkdir()
    {
        $this->client->createDir('testmkdir');
        $this->client->createDir('testmkdir');
    }

    /**
     * @expectedException \ActiveCollab\Etcd\Exception\KeyNotFoundException
     */
    public function testUpdate()
    {
        $key = '/testupdate_key';
        $value1 = 'value1';
        $value2 = 'value2';
        $this->client->update($key, $value1);

        $this->client->set($key, $value2);
        $value = $this->client->get($key);
        $this->assertEquals('value2', $value);
    }

    public function testUpdatedir()
    {
        $dirname = '/test_updatedir';

        $this->client->createDir($dirname);
        $this->client->updateDir($dirname, 10);

        $dir = $this->client->listDir($dirname);
        $this->assertLessThanOrEqual(10, $dir['node']['ttl']);
    }

    /**
     * @expectedException \ActiveCollab\Etcd\Exception\EtcdException
     */
    public function testRm()
    {
        $this->client->remove('/rmkey');
    }

    /**
     * @expectedException \ActiveCollab\Etcd\Exception\EtcdException
     */
    public function testRmdir()
    {
        $this->client->createDir('testrmdir');
        $this->client->removeDir('testrmdir', true);
        $this->client->removeDir('testrmdir');
    }

    public function testListDir()
    {
        $data = $this->client->listDir();
        $this->assertEquals($this->dirname, $data['node']['key']);
        $this->assertTrue($data['node']['dir'] == 1);
    }

    public function testLs()
    {
        $dirs = $this->client->listDirs();
        $this->assertTrue(in_array($this->dirname, $dirs));
    }

    public function testGetKeysValue()
    {
        $this->client->set('/a/aa', 'a_a');
        $this->client->set('/a/ab', 'a_b');
        $this->client->set('/a/b/ab', 'aa_b');

        $values = $this->client->getKeysValue('/', false);
        $this->assertFalse(isset($values[$this->dirname . '/a/aa']));

        $values = $this->client->getKeysValue();
        $this->assertTrue(isset($values[$this->dirname . '/a/aa']));
        $this->assertEquals('a_a', $values[$this->dirname . '/a/aa']);
        $this->assertTrue(in_array('aa_b', $values));
    }

    public function testGetNode()
    {
        $key = 'node_key';
        $setdata = $this->client->set($key, 'node_value');
        $node = $this->client->getNode($key);
        $this->assertJsonStringEqualsJsonString(json_encode($node), json_encode($setdata['node']));
    }
}
