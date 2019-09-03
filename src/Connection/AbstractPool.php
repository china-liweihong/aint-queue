<?php

/*
 * This file is part of the littlesqx/aint-queue.
 *
 * (c) littlesqx <littlesqx@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Littlesqx\AintQueue\Connection;

abstract class AbstractPool implements PoolInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var Channel
     */
    protected $channel;

    /**
     * @var int
     */
    protected $currentConnectionNum = 0;

    public function __construct(array $options)
    {
        $this->options = $options;
        $this->channel = new Channel($options['size'] ?? 50);
    }

    /**
     * Get a connection from current pool.
     *
     * @return mixed
     *
     * @throws \Throwable
     */
    public function get()
    {
        $num = $this->channel->length();
        try {
            if (0 === $num && $this->currentConnectionNum < ($this->options['size'] ?? 50)) {
                $this->currentConnectionNum++;
                return $this->createConnection();
            }
        } catch (\Throwable $t) {
            $this->currentConnectionNum--;
            throw $t;
        }

        return $this->channel->pop($this->options['wait_timeout'] ?? 3);
    }

    /**
     * Release a connection back to current pool.
     *
     * @param $connection
     */
    public function release($connection): void
    {
        $this->channel->push($connection);
    }

    /**
     * Close and clear current pool.
     */
    public function flush(): void
    {
        while ($conn = $this->channel->pop($this->options['wait_timeout'] ?? 3)) {
            $this->closeConnection($conn);
        }
    }

    /**
     * Create a connection.
     *
     * @return mixed
     */
    abstract public function createConnection();

    /**
     * Close a connection.
     *
     * @param $connection
     */
    abstract public function closeConnection($connection): void;
}
