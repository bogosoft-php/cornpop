<?php

namespace Bogosoft\CornPop;

use \Psr\Http\Server\MiddlewareInterface;

/**
 * Describes any type capapble of adding a middleware component to a queue.
 */
interface MiddlewareQueueInterface
{
    /**
     * Add a middleware component to the current queue.
     * 
     * @param MiddlewareInterface A middleware component to be added to the current queue.
     * 
     * @return MiddlewareQueuable The current queue.
     */
    function enqueue(MiddlewareInterface $middleware) : MiddlewareQueueInterface;
}