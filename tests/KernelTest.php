<?php

namespace Tests;

require __DIR__ . '/../vendor/autoload.php';

use \Bogosoft\Configuration\IConfiguration;
use \Bogosoft\Configuration\IMutableConfiguration;
use \Bogosoft\Configuration\ConfigurationSectionInterface as ConfigurationSection;
use \Bogosoft\CornPop\KernelBase;
use \Bogosoft\CornPop\MiddlewareQueueInterface;
use \Closure;
use \PHPUnit\Framework\TestCase;
use \Psr\Container\ContainerInterface as Container;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Server\MiddlewareInterface as Middleware;
use \Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use \Psr\Log\AbstractLogger;
use \Psr\Log\LoggerInterface as Logger;
use \Psr\Log\NullLogger;
use \SplQueue;

define('HEADER_NAME',        'X-Handled-By-Fallback');
define('HEADER_VALUE_FALSE', 'false');
define('HEADER_VALUE_TRUE',  'true');

class KernelTest extends TestCase
{
    public function testFallbackRequestHandlerIsCalledWhenNoMiddlewareShortCircuit() : void
    {
        $kernel = new class() extends TestKernel
        {
            protected function getFallbackRequestHandler(Container $container) : RequestHandler
            {
                return new class implements RequestHandler
                {
                    public function handle(Request $request) : Response
                    {
                        $response = new \GuzzleHttp\Psr7\Response(200);

                        return $response->withHeader(HEADER_NAME, HEADER_VALUE_TRUE);
                    }
                };
            }

            protected function getMiddleware(Container $container, MiddlewareQueueInterface $queue) : void
            {
                $queue->enqueue(new class implements Middleware
                {
                    public function process(Request $request, RequestHandler $handler) : Response
                    {
                        return $handler->handle($request);
                    }
                });
            }
        };

        $request = new \GuzzleHttp\Psr7\ServerRequest('GET', '/index.php');

        $response = $kernel->handle($request);

        $this->assertTrue($response->hasHeader(HEADER_NAME));
        $this->assertEquals($response->getHeader(HEADER_NAME)[0], HEADER_VALUE_TRUE);
    }

    public function testFallbackRequestHandlerIsNotCalledWhenMiddlewareShortCircuitsProcessing() : void
    {
        $kernel = new class() extends TestKernel
        {
            protected function getFallbackRequestHandler(Container $container) : RequestHandler
            {
                return new class implements RequestHandler
                {
                    public function handle(Request $request) : Response
                    {
                        $response = new \GuzzleHttp\Psr7\Response(200);

                        return $response->withHeader(HEADER_NAME, HEADER_VALUE_TRUE);
                    }
                };
            }

            protected function getMiddleware(Container $container, MiddlewareQueueInterface $queue) : void
            {
                $queue->enqueue(new class implements Middleware
                {
                    public function process(Request $request, RequestHandler $handler) : Response
                    {
                        $response = new \GuzzleHttp\Psr7\Response(200);

                        return $response->withHeader(HEADER_NAME, HEADER_VALUE_FALSE);
                    }
                });
            }
        };

        $request = new \GuzzleHttp\Psr7\ServerRequest('GET', '/index.php');

        $response = $kernel->handle($request);

        $this->assertTrue($response->hasHeader(HEADER_NAME));
        $this->assertNotEquals($response->getHeader(HEADER_NAME)[0], HEADER_VALUE_TRUE);
    }

    public function testKernelBaseCallsMethodsInExpectedOrder() : void
    {
        $calls = new SplQueue();

        $kernel = new class($calls) extends TestKernel
        {
            private $calls;

            public function __construct(SplQueue $calls)
            {
                $this->calls = $calls;
            }

            protected function getContainer(IConfiguration $config, Logger $logger) : Container
            {
                $this->calls->enqueue(__FUNCTION__);

                return parent::getContainer($config, $logger);
            }

            protected function getConfiguration(Request $request) : IMutableConfiguration
            {
                $this->calls->enqueue(__FUNCTION__);

                return parent::getConfiguration($request);
            }

            protected function getFallbackRequestHandler(Container $container) : RequestHandler
            {
                $this->calls->enqueue(__FUNCTION__);

                return parent::getFallbackRequestHandler($container);
            }

            protected function getLogger(IConfiguration $config) : Logger
            {
                $this->calls->enqueue(__FUNCTION__);

                return parent::getLogger($config);
            }

            protected function getMiddleware(Container $container, MiddlewareQueueInterface $queue) : void
            {
                $this->calls->enqueue(__FUNCTION__);

                parent::getMiddleware($container, $queue);
            }
        };

        $request = new \GuzzleHttp\Psr7\ServerRequest('GET', '/');

        $response = $kernel->handle($request);

        $this->assertEquals('getConfiguration', $calls->dequeue());
        $this->assertEquals('getLogger', $calls->dequeue());
        $this->assertEquals('getContainer', $calls->dequeue());
        $this->assertEquals('getFallbackRequestHandler', $calls->dequeue());
        $this->assertEquals('getMiddleware', $calls->dequeue());
    }
}