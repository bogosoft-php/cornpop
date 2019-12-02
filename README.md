# bogosoft/cornpop

A PSR-compliant nano-framework for generating responses to HTTP requests.

CornPop provides an extensible kernel for setting up and executing an HTTP response pipeline.

The kernel expects PSR implementations for various HTTP-based objects but provides none, leaving you free to bring-your-own implementations (BYOI).

You'll need to BYOI for:

- `Bogosoft\Configuration\ConfigurationInterface`
- `Psr\Container\ContainerInterface`
- `Psr\Http\Message\ResponseFactoryInterface`
- `Psr\Http\Message\ResponseInterface`
- `Psr\Http\Message\ServerRequestInterface`
- `Psr\Http\Server\MiddlewareInterface`
- `Psr\Http\Server\RequestHandlerInterface`
- `Psr\Log\LoggerInterface`

## Requirements

- PHP 7.1+

## Installation

```bash
composer require bogosoft/cornpop
```

## The Kernel

`KernelBase`, the class that you will need to extend, itself implements the `RequestHandlerInterface` interface. The only method implemented on the base kernel is the `RequestHandlerInterface::handler` method. Calling this method sets in motion several actions and ultimately returns an HTTP response.

### Configuration

The first thing that the kernel takes care of is obtaining an application configuration. This configuration is of the type, `Bogosoft\Configuration\ConfigurationInterface`.

Although CornPop does not provide any implementations of PSR objects, it does ship with an `ArrayConfiguration` that can be used as the application configuration.

The HTTP request passed into the kernel can be used to configure the configuration so to speak.

### Logging

Once the configuration has been obtained, it is then used to obtain a logger of type, `Psr\Log\LoggerInterface`.

### Dependency Injection (DI) Container

After obtaining a logger, the logger and the configuration are used to obtain a DI container of type, `Psr\Contaienr\ContainerInterface`.

### Fallback Handler

The container is then used to obtain a fallback request handler of type, `Psr\Http\Server\RequestHandlerInterface`, which will be invoked if none of the configured middleware returns an HTTP response.

This would be useful, for instance, in returning a 404 response.

### Middleware

The container is again used to configure a sequence of middleware components to be called. Each component will need to be of the type, `Psr\Http\Server\MiddlewareInterface`, and will be called in the order that it was queued.

### Execution

Once the middleware components have been queued, they will each be called in succession. If a middleware component returns a response instead of calling the passed-in request handler, it will short-circuit further middleware processing.
