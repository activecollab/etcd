<?php

namespace ActiveCollab\Etcd;

/**
 * @package ActiveCollab\Etcd
 */
class SrvDiscoverer
{

    /**
     * @var string
     */
    private $domain;

    /**
     * @param string $domain
     */
    public function __construct($domain)
    {
        $this->domain = $domain;
    }

    /**
     * Fetch the servers with a DNS SRV request
     *
     * @return array
     */
    public function getServers()
    {
        $records = dns_get_record($this->domain, DNS_SRV);
        $result = [];
        foreach ($records as $record) {
            $result[] = [
                'target' => $record['target'],
                'port' => $record['port'],
                'pri' => $record['pri'],
                'weight' => $record['weight'],
            ];
        }

        return $result;
    }

    /**
     * Pick a server according to the priority fields.
     * Note that weight is currently ignored.
     *
     * @param array $servers from getServers
     * @return array|bool
     */
    public function pickServer(array $servers)
    {
        if (!$servers) {
            return false;
        }
        $by_prio = [];
        foreach ($servers as $server) {
            $by_prio[$server['pri']][] = $server;
        }

        $min = min(array_keys($by_prio));
        if (count($by_prio[$min]) == 1) {
            return $by_prio[$min][0];
        } else {
            // Choose randomly
            $rand = mt_rand(0, count($by_prio[$min])-1);
            return $by_prio[$min][$rand];
        }
    }
}
