<?php

namespace ActiveCollab\Etcd;

use ActiveCollab\Etcd\Exception\EtcdException;
use ActiveCollab\Etcd\Exception\KeyExistsException;
use ActiveCollab\Etcd\Exception\KeyNotFoundException;

/**
 * @package ActiveCollab\Etcd
 */
interface ClientInterface
{
    /**
     * @return string
     */
    public function getServer();

    /**
     * @param  string $server
     * @return $this
     */
    public function &setServer($server);

    /**
     * @return bool
     */
    public function getVerifySslPeer();

    /**
     * @return string
     */
    public function getCustomCaFile();

    /**
     * Configure SSL connection parameters
     *
     * @param  bool|true   $verify_ssl_peer
     * @param  string|null $custom_ca_file
     * @return $this
     */
    public function &verifySslPeer($verify_ssl_peer = true, $custom_ca_file = null);

    /**
     * @return string
     */
    public function getApiVersion();

    /**
     * @param  string $version
     * @return $this
     */
    public function &setApiVersion($version);

    /**
     * @return string
     */
    public function getSandboxPath();

    /**
     * Set the default root directory. the default is `/`
     * If the root is others e.g. /linkorb when you set new key,
     * or set dir, all of the key is under the root
     * e.g.
     * <code>
     *    $client->setRoot('/linkorb');
     *    $client->set('key1, 'value1');
     *    // the new key is /linkorb/key1
     * </code>
     *
     * @param string $root
     * @return Client
     */
    public function &setSandboxPath($root);

    /**
     * Build key space operations
     *
     * @param  string $key
     * @return string
     */
    public function getKeyPath($key);

    /**
     * Return full key URI
     *
     * @param  string $key
     * @return string
     */
    public function getKeyUrl($key);

    /**
     * @return array
     */
    public function geVersion();

    /**
     * Set the value of a key
     *
     * @param string $key
     * @param string $value
     * @param int    $ttl
     * @param array  $condition
     * @return array
     */
    public function set($key, $value, $ttl = null, $condition = []);

    /**
     * Retrieve the value of a key
     *
     * @param  string               $key
     * @param  array                $flags
     * @return array
     * @throws KeyNotFoundException
     * @throws EtcdException
     */
    public function getNode($key, array $flags = null);

    /**
     * Retrieve the value of a key
     *
     * @param string $key
     * @param array  $flags the extra query params
     * @return string the value of the key.
     * @throws KeyNotFoundException
     */
    public function get($key, array $flags = null);

    /**
     * Create a new key with a given value
     *
     * @param string $key
     * @param string $value
     * @param int    $ttl
     * @return array $body
     * @throws KeyExistsException
     */
    public function create($key, $value, $ttl = 0);

    /**
     * make a new directory
     *
     * @param string $key
     * @param int    $ttl
     * @return array $body
     * @throws KeyExistsException
     */
    public function createDir($key, $ttl = 0);

    /**
     * Update an existing key with a given value.
     *
     * @param string $key
     * @param string $value
     * @param int    $ttl
     * @param array  $condition The extra condition for updating
     * @return array $body
     * @throws KeyNotFoundException
     */
    public function update($key, $value, $ttl = 0, $condition = []);

    /**
     * Update directory
     *
     * @param  string        $key
     * @param  int           $ttl
     * @return array
     * @throws EtcdException
     */
    public function updateDir($key, $ttl);

    /**
     * remove a key
     *
     * @param string $key
     * @return array
     * @throws EtcdException
     */
    public function remove($key);

    /**
     * Removes the key if it is directory
     *
     * @param string  $key
     * @param boolean $recursive
     * @return mixed
     * @throws EtcdException
     */
    public function removeDir($key, $recursive = false);

    /**
     * Retrieve a directory
     *
     * @param string  $key
     * @param boolean $recursive
     * @return mixed
     * @throws KeyNotFoundException
     */
    public function dirInfo($key = '/', $recursive = false);

    /**
     * Retrieve a directories key
     *
     * @param string  $key
     * @param boolean $recursive
     * @return array
     * @throws EtcdException
     */
    public function listSubdirs($key = '/', $recursive = false);

    /**
     * Get all key-value pair that the key is not directory.
     *
     * @param  string  $root
     * @param  boolean $recursive
     * @param  string  $key
     * @return array
     */
    public function getKeyValueMap($root = '/', $recursive = true, $key = null);
}
