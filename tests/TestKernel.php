<?php

namespace Tests;

use \Bogosoft\Configuration\ConfigurationInterface as Configuration;
use \Bogosoft\Configuration\ConfigurationSectionInterface as ConfigurationSection;
use \Bogosoft\CornPop\KernelBase;
use \Bogosoft\CornPop\MiddlewareQueueInterface;
use \Psr\Container\ContainerInterface as Container;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Server\MiddlewareInterface as Middleware;
use \Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use \Psr\Log\LoggerInterface as Logger;
use \Psr\Log\NullLogger;

class TestKernel extends KernelBase
{
    protected function getContainer(Configuration $config, Logger $logger) : Container
    {
        return new class implements Container
        {
            public function get($id) { return null; }
            public function has($id) { return false; }
        };
    }

    protected function getConfiguration(Request $request) : Configuration
    {
        return new class implements ConfigurationSection
        {
            public function getChildren() : iterable { return []; }
            public function getKey() : string { return ''; }
            public function getPath() : string { return ''; }
            public function getSection(string $key) : ConfigurationSection { return $this; }
            public function offsetExists($offset) { return false; }
            public function offsetGet($offset) { return null; }
            public function offsetSet($offset, $value) {}
            public function offsetUnset($offset) {}
        };
    }

    protected function getFallbackRequestHandler(Container $container) : RequestHandler
    {
        return new class implements RequestHandler
        {
            public function handle(Request $request) : Response
            {
                return new \GuzzleHttp\Psr7\Response(200);
            }
        };
    }

    protected function getLogger(Configuration $config) : Logger
    {
        return new NullLogger();
    }

    protected function getMiddleware(Container $container, MiddlewareQueueInterface $queue) : void
    {
    }
}