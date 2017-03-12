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

    const MSG_FORWARD_SUBMIT = 'msg.forward.submit';
    const MSG_FORWARD_RESPONSE = 'msg.forward.response';
    const MSG_FORWARD_TIMEOUT = 'msg.forward.timeout';

    const NET_CONNECT_TIMEOUT = 'net.connect.timeout';
}