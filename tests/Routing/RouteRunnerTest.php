<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\CallableResolver;
use Slim\MiddlewareDispatcher;
use Slim\Routing\RouteCollector;
use Slim\Routing\RouteParser;
use Slim\Routing\RouteResolver;
use Slim\Routing\RouteRunner;
use Slim\Routing\RoutingResults;
use Slim\Tests\TestCase;

class RouteRunnerTest extends TestCase
{
    /**
     * Provide a boolean flag to indicate whether the test case should use the
     * advanced callable resolver or the non-advanced callable resolver
     *
     * @return array
     */
    public function useAdvancedCallableResolverDataProvider(): array
    {
        return [[true], [false]];
    }

    /**
     * @dataProvider useAdvancedCallableResolverDataProvider
     *
     * @param bool $useAdvancedCallableResolver
     */
    public function testRoutingIsPerformedIfRoutingResultsAreUnavailable(bool $useAdvancedCallableResolver)
    {
        $handler = (function (ServerRequestInterface $request, ResponseInterface $response) {
            $routingResults = $request->getAttribute('routingResults');
            $this->assertInstanceOf(RoutingResults::class, $routingResults);
            return $response;
        })->bindTo($this);

        $callableResolver = $useAdvancedCallableResolver ?
            $this->getAdvancedCallableResolver() : $this->getCallableResolver();
        $responseFactory = $this->getResponseFactory();

        $routeCollector = new RouteCollector($responseFactory, $callableResolver);
        $routeCollector->map(['GET'], '/hello/{name}', $handler);

        $routeParser = new RouteParser($routeCollector);
        $routeResolver = new RouteResolver($routeCollector);

        $request = $this->createServerRequest('https://example.com:443/hello/foo', 'GET');
        $dispatcher = new RouteRunner($routeResolver, $routeParser);

        $middlewareDispatcher = new MiddlewareDispatcher($dispatcher, $callableResolver);
        $middlewareDispatcher->handle($request);
    }
}
