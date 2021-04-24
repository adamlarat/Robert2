<?php
declare(strict_types=1);

namespace Robert2\API;

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Robert2\API\Config\Config;
use Robert2\API\Errors\ErrorHandler;
use Robert2\API\Services\View;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Routing\RouteContext;

class App
{
    private $container;
    private $app;

    public function __construct()
    {
        $this->container = static::createContainer();

        $this->app = AppFactory::create(null, $this->container);
        $this->app->addBodyParsingMiddleware();

        $this->configureCors();
        $this->configureRouter();
        $this->configureErrorHandlers();
    }

    public function __call($name, $arguments)
    {
        return \call_user_func_array([$this->app, $name], $arguments);
    }

    // ------------------------------------------------------
    // -
    // -    Internal Methods
    // -
    // ------------------------------------------------------

    protected function configureCors()
    {
        $isCORSEnabled = (bool)$this->container->get('settings')['enableCORS'];
        if (isTestMode() || !$isCORSEnabled) {
            return;
        }

        $this->app->add(function (Request $request, RequestHandler $handler): Response {
            $response = $handler->handle($request);

            $response = $response->withHeader('Access-Control-Allow-Origin', '*');
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
            $response = $response->withHeader(
                'Access-Control-Allow-Headers',
                'X-Requested-With, Content-Type, Accept, Origin, Authorization'
            );

            // - Allowed methods
            $routingResults = RouteContext::fromRequest($request)->getRoutingResults();
            $methods = $routingResults->getAllowedMethods();
            $response = $response->withHeader('Access-Control-Allow-Methods', implode(',', $methods));

            return $response;
        });
    }

    protected function configureRouter()
    {
        $settings = $this->container->get('settings');
        $useRouterCache = (bool)$settings['useRouterCache'] && !isTestMode();
        $isCORSEnabled = (bool)$settings['enableCORS'] && !isTestMode();

        // - Route cache
        if ($useRouterCache) {
            $routeCollector = $this->app->getRouteCollector();
            $routeCollector->setCacheFile(VAR_FOLDER . DS . 'cache' . DS . 'routes.php');
        }

        // - Middleware
        $this->app->addRoutingMiddleware();

        // - Routes
        // -- Api routes
        $this->app->group('/api', function (RouteCollectorProxy $group) use ($isCORSEnabled) {
            // - Autorise les requêtes de type OPTIONS sur les routes d'API.
            if ($isCORSEnabled) {
                $group->options('/{routes:.+}', function (Request $request, Response $response) {
                    return $response;
                });
            }

            // - Toutes les routes d'API sont définies dans le fichier `Config/routes.php`.
            $routeMethods = include __DIR__ . DS . 'Config' . DS . 'routes.php';
            foreach ($routeMethods as $method => $routes) {
                foreach ($routes as $route => $action) {
                    $group->$method($route, sprintf('Robert2\\API\\Controllers\\%s', $action));
                }
            }

            // - Not found API
            $group->any('/[{path:.*}]', function (Request $request) {
                throw new HttpNotFoundException($request);
            });
        });
    }

    protected function configureErrorHandlers()
    {
        $shouldLog = true;
        $displayErrorDetails = (bool)$this->container->get('settings')['displayErrorDetails'];
        if (isTestMode()) {
            $shouldLog = false;
            $displayErrorDetails = true;
        }

        $logger = $this->container->get('logger')->createLogger('error');
        $errorMiddleware = $this->app->addErrorMiddleware($displayErrorDetails, $shouldLog, $shouldLog, $logger);

        // - Default error handler.
        $defaultErrorHandler = new ErrorHandler(
            $this->app->getCallableResolver(),
            $this->app->getResponseFactory(),
            $logger
        );
        $errorMiddleware->setDefaultErrorHandler($defaultErrorHandler);
    }

    // ------------------------------------------------------
    // -
    // -    Internal static Methods
    // -
    // ------------------------------------------------------

    protected static function createContainer()
    {
        $container = new Container();

        //
        // - Settings
        //

        $container->set('settings', Config::getSettings());

        //
        // - Http Cache Provider
        //

        $container->set('cache', function () {
            return new \Slim\HttpCache\CacheProvider();
        });

        //
        // - Services
        //

        $container->set('logger', function () use ($container) {
            $settings = $container->get('settings')['logger'] ?? [];
            return new Services\Logger($settings);
        });

        $container->set('auth', new Services\Auth([
            new Services\Auth\JWT,
        ]));

        $container->set('view', View::class);

        return $container;
    }
}
