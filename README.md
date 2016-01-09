# Etcd PHP Client

[![Build Status](https://travis-ci.org/activecollab/etcd.svg?branch=master)](https://travis-ci.org/activecollab/etcd)

etcd is a distributed configuration system, part of the coreos project.

This repository provides a client library for etcd for PHP applications. It is based on [linkorb/etcd-php](https://github.com/linkorb/etcd-php). To learn why we forked it, jump [here](#why-fork).

## Installating etcd

To install etcd, follow instructions that etcd team posts on Releases page of the project:

[https://github.com/coreos/etcd/releases/](https://github.com/coreos/etcd/releases/)

## Installing ActiveCollab/etcd

Easiest way is to install it using composer:

```json
{
    "require" : {
        "activecollab/etcd": "^1.0"
    }
}
```

## Using Client

```php
use use ActiveCollab\Etcd\Client as EtcdClient;

$client = new EtcdClient('http://127.0.0.1:4001');

// Get, set, update, remove key
if (!$client->exists('/key/name')) {
    $client->set('/key/name', 'value');
}
$client->set('/key/name', 'value', 10); // Set TTL
print $client->get('/key/name');

$client->update('/key/name', 'new value');

$client->remove('/key/name');

// Working with dirs
if (!$client->dirExists('/dir/path')) {
    $client->createDir('/dir/path');
}
$client->updateDir('/dir/path', 10); // Set TTL
$client->removeDir('/dir/path');

// Get dir info
$client->dirInfo('/dir/path');

// List subdirectories
$client->listDirs('/dir/path');

// Return key value map for a given dir
$client->getKeyValueMap('/dir/path')
```

## Sandbox Path

If you configure sandbox path in the client instance, all keys will be prefixed with that path:

```php
$client = new EtcdClient('http://127.0.0.1:4001');
$client->setSandboxPath('/my/namespace');

$client->set('/key/name', 'value'); // will set /my/namespace/key/name
print $client->get('/key/name'); // will print /my/namespace/key/name
```

One more conenient method that client offers is option to call several commands in a specific sanbox, and have client revert back to the previous sandbox path when done:

```php
$client = new EtcdClient('http://127.0.0.1:4001');
$client->setSandboxPath('/my/namespace');

// Path relative to the current sandbox path
$client->sandboxed('./different/namespace', function(EtcdClient &$c) {
    $c->set('new_key', 123); // Sets /my/namespace/different/namespace/new_key
});

// Absolute path
$client->sandboxed('/different/namespace', function(EtcdClient &$c) {
    $c->set('new_key', 123); // Sets /different/namespace/new_key
});

print $client->getSandboxPath(); // Prints '/my/namespace'
```

## SSL

Client can be configured not to verify SSL peer:

```php
$client = (new Client('https://127.0.0.1:4001'))->verifySslPeer(false);
```

as well as to use a custom CA file:

```php
$client = (new Client('https://127.0.0.1:4001'))->verifySslPeer(true, '/path/to/ca/file');
```

## Why Fork?

While [original library](https://github.com/linkorb/etcd-php) works well, it depends on two big packages: Symfony Console and Guzzle. For a feature as low level as config access, we wanted something a bit nimbler, so we removed CLI commands and refactored the original library to use PHP's curl extension.
