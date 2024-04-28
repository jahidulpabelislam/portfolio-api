<?php

declare(strict_types=1);

/**
 * The 'index' of the application/API.
 */

namespace App;

use App\Auth\Controller as AuthController;
use App\Auth\Middleware as AuthMiddleware;
use App\HTTP\CORSMiddleware;
use App\HTTP\Router;
use App\HTTP\VersionCheckMiddleware;
use App\Projects\Controller as ProjectsController;
use App\Projects\TypeController as ProjectTypesController;
use DateTime;
use JPI\HTTP\App;
use JPI\HTTP\Request;
use JPI\HTTP\Response;
use JPI\Utils\Singleton;

class Core extends App {

    use Singleton;

    public const VERSION = "4";

    protected function __construct() {
        $this->initRoutes();

        $this->middlewares = [
            new AuthMiddleware(),
            new CORSMiddleware(),
            new VersionCheckMiddleware(),
        ];
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
        $projectTypesController = ProjectTypesController::class;
        $authController = AuthController::class;

        $this->addRoute("/project-types/{id}/", "GET", "$projectTypesController::read", "projectType");
        $this->addRoute("/project-types/", "GET", "$projectTypesController::index");

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
