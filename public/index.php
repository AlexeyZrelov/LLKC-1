<?php

session_start();

use App\Controllers\LoginController;
use App\Controllers\MainController;
use App\Controllers\SignupController;
use App\Libs\Redirect;
use App\Libs\View;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;

//require_once '../vendor/autoload.php';
require_once __DIR__ . '/../vendor/autoload.php';

$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
    $r->addRoute('GET', '/users', 'get_all_users_handler');
    // {id} must be a number (\d+)
    $r->addRoute('GET', '/user/{id:\d+}', 'get_user_handler');
    // The /{title} suffix is optional
    $r->addRoute('GET', '/articles/{id:\d+}[/{title}]', 'get_article_handler');

    $r->addRoute('GET', '/', [MainController::class, 'index']);
    $r->addRoute('GET', '/home', [MainController::class, 'home']);
    $r->addRoute('GET', '/delete/{id:\d+}', [MainController::class, 'delete']);
    $r->addRoute('GET', '/update/{id:\d+}', [MainController::class, 'updateForm']);
    $r->addRoute('POST', '/update/{id:\d+}', [MainController::class, 'update']);
    $r->addRoute('GET', '/signup', [SignupController::class, 'index']);
    $r->addRoute('POST', '/signup', [MainController::class, 'create']);
    $r->addRoute('GET', '/login', [LoginController::class, 'index']);
    $r->addRoute('POST', '/login', [LoginController::class, 'login']);
    $r->addRoute('GET', '/logout', [MainController::class, 'logout']);

});

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        // ... 404 Not Found
        var_dump('404 Not Found');
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        // ... 405 Method Not Allowed
        var_dump('405 Method Not Allowed');
        break;
    case FastRoute\Dispatcher::FOUND:
        $controller = $routeInfo[1][0];
        $method = $routeInfo[1][1];

        /** @var View $response */
        $response = (new $controller)->$method($routeInfo[2]);

        $twig = new Environment(new FilesystemLoader('../app/Views'));

        if ($response instanceof View) {
            try {
                echo $twig->render($response->getPath(), $response->getVariables());
            } catch (LoaderError|RuntimeError|SyntaxError $e) {
                echo "Error!: " . $e->getMessage() . "<br/>";
            }
        }

        if ($response instanceof Redirect) {
            header('Location: ' . $response->getLocation());
            exit;
        }

        break;
}