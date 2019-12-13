<?php

declare(strict_types=1);

namespace Bogosoft\CornPop;

use \Bogosoft\Configuration\IConfiguration;
use \Bogosoft\Configuration\IMutableConfiguration;
use \Psr\Container\ContainerInterface as Container;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Server\MiddlewareInterface;
use \Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use \Psr\Log\LoggerInterface as Logger;
use \SplQueue;

abstract class KernelBase implements RequestHandler
{
    /**
     * Get an application configuration derived from an unaltered HTTP request.
     * 
     * @param Request $unalteredRequest An HTTP request that has not yet been processed by middleware.
     * 
     * @return IMutableConfiguration An application configuration derived from the given HTTP request.
     */
    protected abstract function getConfiguration(Request $unalteredRequest) : IMutableConfiguration;

    /**
     * Get a dependency injection (DI) container.
     * 
     * This method must return a DI container that optionally is configured with the given configuration.
     * 
     * @param IConfiguration $config An application configuration that can be used when configuring a DI container.
     * @param Logger         $logger An application logger.
     * 
     * @return Container A DI container.
     */
    protected abstract function getContainer(IConfiguration $config, Logger $logger) : Container;

    /**
     * Get a fallback request handler.
     * 
     * The fallback handler will be the handler called to process a request in the event that
     * no middleware component manages to do so.
     * 
     * @param Container $container A DI container that may be used to compose the fallback request handler.
     * 
     * @return RequestHandler A request handler intended to be called as a last resort.
     */
    protected abstract function getFallbackRequestHandler(Container $container) : RequestHandler;

    /**
     * Get a logger.
     * 
     * The given configuration can be used to compose the logger.
     * 
     * @param IConfiguration $config A configuration that can be used to configure the logger.
     * 
     * @return Logger A new logger.
     */
    protected abstract function getLogger(IConfiguration $config) : Logger;

    /**
     * Get the current application's middleware components.
     * 
     * This method should return one or more middleware components for processing HTTP requests and responses.
     * A DI container is made available to assist with middleware configuration.
     * 
     * @param Container       $container A DI container that can be used in configuring middleware components.
     * @param MiddlewareQueue $queue     A queue to populate with middleware components.
     */
    protected abstract function getMiddleware(Container $container, MiddlewareQueueInterface $queue) : void;

    public function handle(Request $request) : Response
    {
        #
        # Get an application configuration. The provided HTTP request can be used to compose the configuration.
        #
        $config = $this->getConfiguration($request);

        #
        # Get an application logger.
        #
        $logger = $this->getLogger($config);

        #
        # Use the application's configuration to generate a dependency injection container.
        #
        $container = $this->getContainer($config, $logger);

        #
        # Use the DI container to obtain a fallback request handler.
        #
        $fallback = $this->getFallbackRequestHandler($container);

        #
        # Use the DI container to queue a sequence of middleware components for execution.
        #
        $middleware = new SplQueue();

        $queue = new class($middleware) implements MiddlewareQueueInterface
        {
            private $items;

            public function __construct(SplQueue $items)
            {
                $this->items = $items;
            }

            public function enqueue(MiddlewareInterface $item) : MiddlewareQueueInterface
            {
                $this->items->enqueue($item);

                return $this;
            }
        };

        $this->getMiddleware($container, $queue);

        #
        # Get a fallback handler to use if no middleware component has handled the request.
        #
        $fallback = $this->getFallbackRequestHandler($container);

        #
        # Create a master request handler to handle this HTTP request.
        #
        $master = new class($middleware, $fallback) implements RequestHandler
        {
            private $fallback;
            private $middleware;

            public function __construct(SplQueue $middleware, RequestHandler $fallback)
            {
                $this->fallback   = $fallback;
                $this->middleware = $middleware;
            }

            public function handle(Request $request) : Response
            {
                return $this->middleware->count() === 0
                     ? $this->fallback->handle($request)
                     : $this->middleware->dequeue()->process($request, $this);
            }
        };

        #
        # Process this request and return a response.
        #
        return $master->handle($request);
    }
}