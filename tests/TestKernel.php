<?php

namespace Tests;

use \Bogosoft\Configuration\IConfiguration;
use \Bogosoft\Configuration\IMutableConfiguration;
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
    protected function getContainer(IConfiguration $config, Logger $logger) : Container
    {
        return new class implements Container
        {
            public function get($id) { return null; }
            public function has($id) { return false; }
        };
    }

    protected function getConfiguration(Request $request) : IMutableConfiguration
    {
        return new class implements IMutableConfiguration
        {
            function get(string $key, string $default = '') : string { return $default; }
            function getKeys() : iterable { yield from []; }
            function has(string $key) : bool { return false; }
            function remove(string $key) : void {}
            function set(string $key, string $value) : void {}
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

    protected function getLogger(IConfiguration $config) : Logger
    {
        return new NullLogger();
    }

    protected function getMiddleware(Container $container, MiddlewareQueueInterface $queue) : void
    {
    }
}