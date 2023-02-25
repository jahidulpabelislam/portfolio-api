<?php

/**
 * The 'index' of the application/API.
 */

namespace App;

use App\Auth\Controller as AuthController;
use App\Auth\Middleware as AuthMiddleware;
use App\HTTP\CORSMiddleware;
use App\HTTP\VersionCheckMiddleware;
use App\HTTP\Router;
use App\Projects\Controller as ProjectsController;
use DateTime;
use JPI\HTTP\App;
use JPI\HTTP\Request;
use JPI\HTTP\Response;
use JPI\Utils\Singleton;
use JPI\Utils\URL;

class Core extends App {

    use Singleton;

    public const VERSION = "4";

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Router
     */
    protected $router;

    protected function __construct() {
        $this->initConfig();
        $this->initRoutes();

        $this->middlewares = [
            new AuthMiddleware(),
            new CORSMiddleware(),
            new VersionCheckMiddleware(),
        ];
    }

    public function initConfig(): void {
        $config = new Config();

        include_once __DIR__ . "/../config.php";

        if (file_exists(__DIR__ . "/../config.local.php")) {
            include_once __DIR__ . "/../config.local.php";
        }

        $this->config = $config;
    }

    public function getConfig(): Config {
        return $this->config;
    }

    private function initRoutes(): void {
        $this->router = new Router(
            Request::fromGlobals(),
            function (Request $request) {
                return Response::json(404, [
                    "message" => "Unrecognised URI ({$request->getPath()}).",
                ]);
            },
            function (Request $request) {
                return Response::json(405, [
                    "message" => "Method {$request->getMethod()} not allowed on {$request->getPath()}.",
                ]);
            }
        );

        $projectsController = ProjectsController::class;
        $authController = AuthController::class;

        $this->addRoute("/projects/{projectId}/images/{id}/", "GET", "$projectsController::getImage", "projectImage");
        $this->addRoute("/projects/{projectId}/images/{id}/", "DELETE", "$projectsController::deleteImage");

        $this->addRoute("/projects/{projectId}/images/", "GET", "$projectsController::getImages", "projectImages");
        $this->addRoute("/projects/{projectId}/images/", "POST", "$projectsController::addImage");

        $this->addRoute("/projects/{id}/", "GET", "$projectsController::read", "project");
        $this->addRoute("/projects/{id}/", "PUT", "$projectsController::update");
        $this->addRoute("/projects/{id}/", "DELETE", "$projectsController::delete");

        $this->addRoute("/projects/", "GET", "$projectsController::index");
        $this->addRoute("/projects/", "POST", "$projectsController::create");

        $this->addRoute("/auth/login/", "POST", "$authController::login");
        $this->addRoute("/auth/logout/", "DELETE", "$authController::logout");
        $this->addRoute("/auth/status/", "GET", "$authController::status");
    }

    public function getRouter(): Router {
        return $this->router;
    }

    public function makeFullURL(string $path): URL {
        $request = $this->getRequest();

        $url = $request->getURL();
        $url->setPath($path);
        return $url;
    }

    public static function getDefaultCacheHeaders(): array {
        $secondsToCache = 2678400; // 31 days

        return [
            "Cache-Control" => ["max-age=$secondsToCache", "public"],
            "Expires" => new DateTime("+$secondsToCache seconds"),
            "Pragma" => "cache",
            "ETag" => true,
        ];
    }
}
