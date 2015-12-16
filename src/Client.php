<?php

namespace ActiveCollab\Etcd;

use ActiveCollab\Etcd\Exception\EtcdException;
use ActiveCollab\Etcd\Exception\KeyExistsException;
use ActiveCollab\Etcd\Exception\KeyNotFoundException;
use RecursiveArrayIterator;

/**
 * @package ActiveCollab\Etcd
 */
class Client implements ClientInterface
{
    /**
     * @var string
     */
    private $server;

    /**
     * @var bool
     */
    private $is_https = false;

    /**
     * @var string
     */
    private $api_version;

    /**
     * @var string
     */
    private $root = '/';

    /**
     * @var boolean
     */
    private $verify_ssl_peer = true;

    /**
     * @var string
     */
    private $custom_ca_file;

    /**
     * @param string $server
     * @param string $api_version
     */
    public function __construct($server = 'http://127.0.0.1:4001', $api_version = 'v2')
    {
        $this->setServer($server);
        $this->setApiVersion($api_version);
    }

    /**
     * @return string
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param  string $server
     * @return $this
     */
    public function &setServer($server)
    {
        if (filter_var($server, FILTER_VALIDATE_URL)) {
            $server = rtrim($server, '/');

            if ($server) {
                $this->server = $server;
                $this->is_https = strtolower(parse_url($this->server)['scheme']) == 'https';
            }

            return $this;
        } else {
            throw new \InvalidArgumentException("Value '$server' is not a valid server URL");
        }
    }

    /**
     * @return bool
     */
    public function getVerifySslPeer()
    {
        return $this->verify_ssl_peer;
    }

    /**
     * @return string
     */
    public function getCustomCaFile()
    {
        return $this->custom_ca_file;
    }

    /**
     * Configure SSL connection parameters
     *
     * @param  bool|true   $verify_ssl_peer
     * @param  string|null $custom_ca_file
     * @return $this
     */
    public function &verifySslPeer($verify_ssl_peer = true, $custom_ca_file = null)
    {
        if ($custom_ca_file) {
            if (!is_file($custom_ca_file)) {
                throw new \InvalidArgumentException('Custom CA file does not exist');
            }

            if (!$verify_ssl_peer) {
                throw new \LogicException('Custom CA file shoult not be set if SSL peer is not verified');
            }
        }

        $this->verify_ssl_peer = (boolean) $verify_ssl_peer;
        $this->custom_ca_file = $custom_ca_file;

        return $this;
    }

    /**
     * @return string
     */
    public function getApiVersion()
    {
        return $this->api_version;
    }

    /**
     * @param  string $version
     * @return $this
     */
    public function &setApiVersion($version)
    {
        $this->api_version = $version;

        return $this;
    }

    /**
     * @return string
     */
    public function getRoot()
    {
        return $this->root;
    }

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
    public function &setRoot($root)
    {
        if (substr($root, 0, 1) !== '/') {
            $root = '/' . $root;
        }

        $this->root = rtrim($root, '/');

        return $this;
    }

    /**
     * Build key space operations
     *
     * @param  string $key
     * @return string
     */
    public function getKeyPath($key)
    {
        if (substr($key, 0, 1) !== '/') {
            $key = '/' . $key;
        }

        return rtrim('/' . $this->api_version . '/keys' . $this->root, '/') . $key;
    }

    /**
     * Return full key URI
     *
     * @param  string $key
     * @return string
     */
    public function getKeyUrl($key)
    {
        return $this->server . $this->getKeyPath($key);
    }

    /**
     * @return array
     */
    public function geVersion()
    {
        return $this->httpGet($this->server . '/version');
    }

    /**
     * Make a GET request
     *
     * @param  string $url
     * @param  array  $query_arguments
     * @return array
     */
    private function httpGet($url, $query_arguments = [])
    {
        if (!empty($query_arguments)) {
            $url .= '?' . http_build_query($query_arguments);
        }

        return $this->executeCurlRequest($this->getCurlHandle($url), $url);
    }

    /**
     * Make a POST request
     *
     * @param  string        $url
     * @param  array         $payload
     * @param  array         $query_arguments
     * @return array|mixed
     * @throws EtcdException
     */
    private function httpPost($url, $payload = [], $query_arguments = [])
    {
        if (!empty($query_arguments)) {
            $url .= '?' . http_build_query($query_arguments);
        }

        $curl = $this->getCurlHandle($url);

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($payload));

        return $this->executeCurlRequest($curl, $url);
    }

    /**
     * Make a PUT request
     *
     * @param  string        $url
     * @param  array         $payload
     * @param  array         $query_arguments
     * @return array|mixed
     * @throws EtcdException
     */
    private function httpPut($url, $payload = [], $query_arguments = [])
    {
        if (!empty($query_arguments)) {
            $url .= '?' . http_build_query($query_arguments);
        }

        $curl = $this->getCurlHandle($url);

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($payload));

        return $this->executeCurlRequest($curl, $url);
    }

    /**
     * Make a DELETE request
     *
     * @param  string        $url
     * @param  array         $query_arguments
     * @return array|mixed
     * @throws EtcdException
     */
    private function httpDelete($url, $query_arguments = [])
    {
        if (!empty($query_arguments)) {
            $url .= '?' . http_build_query($query_arguments);
        }

        $curl = $this->getCurlHandle($url);

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');

        return $this->executeCurlRequest($curl, $url);
    }

    /**
     * Initialize curl handle
     *
     * @param  string   $url
     * @return resource
     */
    private function getCurlHandle($url)
    {
        if ($curl = curl_init($url)) {
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 15);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

            if ($this->is_https && $this->verify_ssl_peer) {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

                if ($this->custom_ca_file) {
                    curl_setopt($curl, CURLOPT_CAINFO, $this->custom_ca_file);
                }
            } else {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            }

            return $curl;
        } else {
            throw new \RuntimeException("Can't create curl handle");
        }
    }

    /**
     * @param  resource      $curl
     * @param  string        $url
     * @param  bool|true     $decode_etcd_json
     * @return array|mixed
     * @throws EtcdException
     */
    private function executeCurlRequest($curl, $url, $decode_etcd_json = true)
    {
        $response = curl_exec($curl);

        if ($error_code = curl_errno($curl)) {
            $error = curl_error($curl);

            curl_close($curl);
            throw new \RuntimeException("$url request failed. Reason: $error", $error_code);
        } else {
            curl_close($curl);

            if ($decode_etcd_json) {
                $response = json_decode($response, true);

                if (isset($response['errorCode']) && $response['errorCode']) {
                    $message = $response['message'];

                    if (isset($response['cause']) && $response['cause']) {
                        $message .= '. Cause: ' . $response['cause'];
                    }

                    switch ($response['errorCode']) {
                        case 100:
                            throw new KeyNotFoundException($message);
                        case 105:
                            throw new KeyExistsException($message);
                        default:
                            throw new EtcdException($message);
                    }
                }
            }

            return $response;
        }
    }

    /**
     * Set the value of a key
     *
     * @param string $key
     * @param string $value
     * @param int    $ttl
     * @param array  $condition
     * @return array
     */
    public function set($key, $value, $ttl = null, $condition = [])
    {
        $data = ['value' => $value];

        if ($ttl) {
            $data['ttl'] = $ttl;
        }

        return $this->httpPut($this->getKeyUrl($key), $data, $condition);
    }

    /**
     * Retrieve the value of a key
     *
     * @param string $key
     * @param array  $flags the extra query params
     * @return array
     * @throws KeyNotFoundException
     * @throws EtcdException
     */
    public function getNode($key, array $flags = null)
    {
        $query = [];
        if ($flags) {
            $query = ['query' => $flags];
        }

        $response = $this->httpGet($this->getKeyUrl($key), $query);

        if (empty($response['node'])) {
            throw new EtcdException('Node field expected in respoinse');
        } else {
            return $response['node'];
        }
    }

    /**
     * Retrieve the value of a key
     *
     * @param string $key
     * @param array  $flags the extra query params
     * @return string the value of the key.
     * @throws KeyNotFoundException
     */
    public function get($key, array $flags = null)
    {
        try {
            $node = $this->getNode($key, $flags);

            return $node['value'];
        } catch (KeyNotFoundException $ex) {
            throw $ex;
        }
    }

    /**
     * Create a new key with a given value
     *
     * @param string $key
     * @param string $value
     * @param int    $ttl
     * @return array $body
     * @throws KeyExistsException
     */
    public function create($key, $value, $ttl = 0)
    {
        return $request = $this->set($key, $value, $ttl, ['prevExist' => 'false']);
    }

    /**
     * make a new directory
     *
     * @param string $key
     * @param int    $ttl
     * @return array $body
     * @throws KeyExistsException
     */
    public function createDir($key, $ttl = 0)
    {
        $data = ['dir' => 'true'];

        if ($ttl) {
            $data['ttl'] = $ttl;
        }

        return $this->httpPut($this->getKeyUrl($key), $data, ['prevExist' => 'false']);
    }

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
    public function update($key, $value, $ttl = 0, $condition = [])
    {
        $extra = ['prevExist' => 'true'];

        if ($condition) {
            $extra = array_merge($extra, $condition);
        }

        return $this->set($key, $value, $ttl, $extra);
    }

    /**
     * Update directory
     *
     * @param  string        $key
     * @param  int           $ttl
     * @return array
     * @throws EtcdException
     */
    public function updateDir($key, $ttl)
    {
        if (!$ttl) {
            throw new EtcdException('TTL is required', 204);
        }

        return $this->httpPut($this->getKeyUrl($key), ['ttl' => (int) $ttl], [
            'dir' => 'true',
            'prevExist' => 'true',
        ]);
    }

    /**
     * remove a key
     *
     * @param string $key
     * @return array
     * @throws EtcdException
     */
    public function remove($key)
    {
        return $this->httpDelete($this->getKeyUrl($key));
    }

    /**
     * Removes the key if it is directory
     *
     * @param string  $key
     * @param boolean $recursive
     * @return mixed
     * @throws EtcdException
     */
    public function removeDir($key, $recursive = false)
    {
        $query = ['dir' => 'true'];

        if ($recursive === true) {
            $query['recursive'] = 'true';
        }

        return $this->httpDelete($this->server . $this->getKeyPath($key), $query);
    }

    /**
     * Retrieve a directory
     *
     * @param string  $key
     * @param boolean $recursive
     * @return mixed
     * @throws KeyNotFoundException
     */
    public function listDir($key = '/', $recursive = false)
    {
        $query = [];
        if ($recursive === true) {
            $query['recursive'] = 'true';
        }

        return $this->httpGet($this->getKeyUrl($key), $query);
    }

    /**
     * Retrieve a directories key
     *
     * @param string  $key
     * @param boolean $recursive
     * @return array
     * @throws EtcdException
     */
    public function listDirs($key = '/', $recursive = false)
    {
        try {
            $data = $this->listDir($key, $recursive);
        } catch (EtcdException $e) {
            throw $e;
        }

        $iterator = new RecursiveArrayIterator($data);

        return $this->traversalDir($iterator);
    }

    /**
     * @var array
     */
    private $dirs = [];

    /**
     * @var array
     */
    private $values = [];

    /**
     * Traversal the directory to get the keys.
     *
     * @param RecursiveArrayIterator $iterator
     * @return array
     */
    private function traversalDir(RecursiveArrayIterator $iterator)
    {
        $key = '';
        while ($iterator->valid()) {
            if ($iterator->hasChildren()) {
                $this->traversalDir($iterator->getChildren());
            } else {
                if ($iterator->key() == 'key' && ($iterator->current() != '/')) {
                    $this->dirs[] = $key = $iterator->current();
                }

                if ($iterator->key() == 'value') {
                    $this->values[ $key ] = $iterator->current();
                }
            }
            $iterator->next();
        }

        return $this->dirs;
    }

    /**
     * Get all key-value pair that the key is not directory.
     *
     * @param string  $root
     * @param boolean $recursive
     * @param string  $key
     * @return array
     */
    public function getKeysValue($root = '/', $recursive = true, $key = null)
    {
        $this->listDirs($root, $recursive);
        if (isset($this->values[ $key ])) {
            return $this->values[ $key ];
        }

        return $this->values;
    }
}
