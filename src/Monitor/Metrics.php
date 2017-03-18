<?php
/**
 * Metrics keys
 * User: moyo
 * Date: 12/03/2017
 * Time: 8:26 PM
 */

namespace ONS\Monitor;

class Metrics
{
    const POOL_CONN_IDLE = 'pool.conn.idle';
    const POOL_CONN_BUSY = 'pool.conn.busy';
    const POOL_QUEUE_SIZE = 'pool.queue.size';
    const POOL_QUEUE_DROPS = 'pool.queue.drops';

    const NET_PACKET_SEND = 'net.packet.send';
    const NET_PACKET_RECV = 'net.packet.recv';

    const NET_TIMEOUT_CONNECT = 'net.time.connect';
    const NET_TIMEOUT_SEND = 'net.timeout.send';

    const NET_CONN_RECONNECT = 'net.conn.reconnect';

    const ONS_MSG_FETCHED = 'ons.msg.fetched';
    const ONS_MSG_CREATED = 'ons.msg.created';
    const ONS_MSG_DELETED = 'ons.msg.deleted';
    const ONS_REQ_FAILED = 'ons.request.failed';
    const ONS_REQ_DENIED = 'ons.request.denied';
    const ONS_TIMEOUT_SERV = 'ons.timeout.serv';
    const ONS_TIMEOUT_GATE = 'ons.timeout.gate';

    const STATS_UP_TIME = 'stats.up.time';
    const STATS_CPU_USAGE = 'stats.cpu.usage';
    const STATS_MEM_BYTES = 'stats.mem.bytes';
}