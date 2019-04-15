<?php

namespace Hartmann\PlanckSlimBridge\Providers;


use Hartmann\PlanckSlimBridge\Strategies\ControllerInvoker;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface;
use Slim\CallableResolver;
use Slim\Collection;
use Slim\Handlers\Error;
use Slim\Handlers\NotAllowed;
use Slim\Handlers\NotFound;
use Slim\Handlers\PhpError;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Router;

class SlimProvider implements ServiceProviderInterface
{

    /**
     * Returns a list of all container entries registered by this service provider.
     *
     * - the key is the entry name
     * - the value is a callable that will return the entry, aka the **factory**
     *
     * Factories have the following signature:
     *        function(\Psr\Container\ContainerInterface $container)
     *
     * @return callable[]
     */
    public function getFactories(): array
    {
        return [
            'settings' => function () {
                return new Collection([
                    'httpVersion' => '1.1',
                    'responseChunkSize' => 4096,
                    'outputBuffering' => 'append',
                    'determineRouteBeforeAppMiddleware' => false,
                    'displayErrorDetails' => true,
                    'addContentLengthHeader' => true,
                    'routerCacheFile' => false,
                ]);
            },
            'router' => function (ContainerInterface $container) {
                $router = new Router();
                $router->setContainer($container);
                $router->setCacheFile($container->get('settings')->get('routerCacheFile'));

                return $router;
            },
            Router::class => function (ContainerInterface $container) {
                return $container->get('router');
            },
            'errorHandler' => function (ContainerInterface $container) {
                return new Error($container->get('settings')->get('displayErrorDetails'));
            },
            'phpErrorHandler' => function (ContainerInterface $container) {
                return new PhpError($container->get('settings')->get('displayErrorDetails'));
            },
            'notFoundHandler' => function () {
                return new NotFound();
            },
            'notAllowedHandler' => function () {
                return new NotAllowed();
            },
            'environment' => function () {
                return new Environment($_SERVER);
            },
            'request' => function (ContainerInterface $container) {
                return Request::createFromEnvironment($container->get('environment'));
            },
            'response' => function (ContainerInterface $container) {
                $response = new Response(200, new Headers(['Content-Type' => 'text/html; charset=UTF-8']));

                return $response->withProtocolVersion($container->get('settings')->get('httpVersion'));
            },
            Request::class => function (ContainerInterface $container) {
                return $container->get('request');
            },
            Response::class => function (ContainerInterface $container) {
                return $container->get('response');
            },
            'foundHandler' => function (ContainerInterface $container) {
                return new ControllerInvoker($container);
            },
            'callableResolver' => function (ContainerInterface $container) {
                return new CallableResolver($container);
            },
        ];
    }

    /**
     * Returns a list of all container entries extended by this service provider.
     *
     * - the key is the entry name
     * - the value is a callable that will return the modified entry
     *
     * Callables have the following signature:
     *        function(Psr\Container\ContainerInterface $container, $previous)
     *     or function(Psr\Container\ContainerInterface $container, $previous = null)
     *
     * About factories parameters:
     *
     * - the container (instance of `Psr\Container\ContainerInterface`)
     * - the entry to be extended. If the entry to be extended does not exist and the parameter is nullable, `null` will be passed.
     *
     * @return callable[]
     */
    public function getExtensions(): array
    {
        return [];
    }
}