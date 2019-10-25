#!/bin/bash

wget -c https://dl.google.com/go/go1.13.3.linux-amd64.tar.gz
tar -zxf go1.13.3.linux-amd64.tar.gz
git clone https://github.com/coreos/etcd.git
export GOROOT=$PWD/go
cd etcd && ./build

# Start etcd
./bin/etcd > /dev/null 2>&1 &