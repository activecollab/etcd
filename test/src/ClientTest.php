<?php

namespace ActiveCollab\Etcd\Tests\Etcd;

use ActiveCollab\Etcd\Client;
use ActiveCollab\Etcd\ClientInterface;
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
        $this->client->setEtcdUser('user');
        $this->client->setEtcdPass('pass');
        $this->client->setSandboxPath('/');

        try {
            $this->client->removeDir($this->dirname, true);
        } catch (EtcdException $e) {

        }

        $create_dir = $this->client->createDir($this->dirname);

        $this->assertInternalType('array', $create_dir);
        $this->assertEquals('create', $create_dir['action']);
        $this->assertInternalType('array', $create_dir['node']);
        $this->assertTrue($create_dir['node']['dir']);

        $this->client->setSandboxPath($this->dirname);
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

    public function testExists()
    {
        $this->assertFalse($this->client->exists('/testgetvalue'));
        $this->client->set('/testgetvalue', 'getvalue');
        $this->assertTrue($this->client->exists('/testgetvalue'));
    }

    public function testExistsOnlyChecksForValues()
    {
        $this->assertFalse($this->client->exists('/testgetvalue'));
        $this->client->createDir('/testgetvalue');
        $this->assertFalse($this->client->exists('/testgetvalue'));
    }

    public function testDirExists()
    {
        $this->assertFalse($this->client->dirExists('/testdir'));
        $this->client->createDir('/testdir');
        $this->assertTrue($this->client->dirExists('/testdir'));
    }

    public function testDirExistsChecksForDirs()
    {
        $this->assertFalse($this->client->dirExists('/testdir'));
        $this->client->set('/testdir', 'getvalue');
        $this->assertFalse($this->client->dirExists('/testdir'));
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
    public function testCreate()
    {
        $this->client->create('testmk', 'mkvalue');
        $this->assertEquals('mkvalue', $this->client->get('testmk'));
        $this->client->create('testmk', 'mkvalue');
    }

    /**
     * @expectedException \ActiveCollab\Etcd\Exception\KeyExistsException
     */
    public function testCreateDir()
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

        $dir = $this->client->dirInfo($dirname);
        $this->assertLessThanOrEqual(10, $dir['node']['ttl']);
    }

    /**
     * @expectedException \ActiveCollab\Etcd\Exception\EtcdException
     */
    public function testRemove()
    {
        $this->client->remove('/rmkey');
    }

    /**
     * @expectedException \ActiveCollab\Etcd\Exception\EtcdException
     */
    public function testRemoveDir()
    {
        $this->client->createDir('testrmdir');
        $this->client->removeDir('testrmdir', true);
        $this->client->removeDir('testrmdir');
    }

    public function testListDir()
    {
        $data = $this->client->dirInfo();

        $this->assertEquals($this->dirname, $data['node']['key']);
        $this->assertTrue($data['node']['dir'] == 1);
    }

    public function testListSubdirs()
    {
        $dirs = $this->client->listSubdirs();
        $this->assertTrue(in_array($this->dirname, $dirs));
    }

    public function testGetKeysValueMap()
    {
        $this->client->set('/a/aa', 'a_a');
        $this->client->set('/a/ab', 'a_b');
        $this->client->set('/a/b/ab', 'aa_b');

        $values = $this->client->getKeyValueMap('/', false);
        $this->assertFalse(isset($values[$this->dirname . '/a/aa']));

        $values = $this->client->getKeyValueMap();
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

    /**
     * Test sandboxed call with absolute path
     */
    public function testSandboxedCall()
    {
        $this->assertFalse($this->client->exists('sub/value'));

        $this->client->sandboxed('/phpunit_test/sub', function(ClientInterface &$c) {
            $c->set('value', 123);
        });

        $this->assertTrue($this->client->exists('sub/value'));
        $this->assertEquals('123', $this->client->get('sub/value'));
    }

    /**
     * Test sandboxed call with relative path
     */
    public function testRelativeSandboxedCall()
    {
        $this->assertFalse($this->client->exists('sub/value'));

        $this->client->sandboxed('./sub', function(ClientInterface &$c) {
            $c->set('value', 123);
        });

        $this->assertTrue($this->client->exists('sub/value'));
        $this->assertEquals('123', $this->client->get('sub/value'));
    }
}
