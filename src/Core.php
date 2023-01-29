<?php

/**
 * The 'index' of the application/API.
 */

namespace App;

use App\Auth\Controller as AuthController;
use App\HTTP\Request;
use App\HTTP\Response;
use App\HTTP\Router;
use App\Projects\Controller as ProjectsController;
use App\Utils\Str;
use DateTime;
use JPI\Utils\Singleton;
use JPI\Utils\URL;

class Core {

    use Singleton;

    public const VERSION = "4";

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response|null
     */
    protected $response = null;

    /**
     * @var Router
     */
    protected $router;

    protected function __construct() {
        $config = new Config();

        include_once __DIR__ . "/../config.php";

        if (file_exists(__DIR__ . "/../config.local.php")) {
            include_once __DIR__ . "/../config.local.php";
        }

        $this->config = $config;

        $this->request = new Request();
        $this->router = new Router($this->getRequest());
        $this->initRoutes();
    }

    public function getConfig(): Config {
        return $this->config;
    }

    public function getRequest(): Request {
        return $this->request;
    }

    private function initRoutes(): void {
        $router = $this->router;

        $projectsController = ProjectsController::class;
        $authController = AuthController::class;

        $router->addRoute("/projects/{projectId}/images/{id}/", "GET", [$projectsController, "getImage"], "projectImage");
        $router->addRoute("/projects/{projectId}/images/{id}/", "DELETE", [$projectsController, "deleteImage"]);

        $router->addRoute("/projects/{projectId}/images/", "GET", [$projectsController, "getImages"], "projectImages");
        $router->addRoute("/projects/{projectId}/images/", "POST", [$projectsController, "addImage"]);

        $router->addRoute("/projects/{id}/", "GET", [$projectsController, "read"], "project");
        $router->addRoute("/projects/{id}/", "PUT", [$projectsController, "update"]);
        $router->addRoute("/projects/{id}/", "DELETE", [$projectsController, "delete"]);

        $router->addRoute("/projects/", "GET", [$projectsController, "index"]);
        $router->addRoute("/projects/", "POST", [$projectsController, "create"]);

        $router->addRoute("/auth/login/", "POST", [$authController, "login"]);
        $router->addRoute("/auth/logout/", "DELETE", [$authController, "logout"]);
        $router->addRoute("/auth/status/", "GET", [$authController, "status"]);
    }

    public function getRouter(): Router {
        return $this->router;
    }

    /**
     * @param $uri string
     * @return URL
     */
    public function makeFullURL(string $uri): URL {
        $request = $this->getRequest();

        return (new URL())
            ->setScheme($request->server->get("HTTPS") === "on" ? "https" : "http")
            ->setHost($request->server->get("SERVER_NAME"))
            ->setPath($uri)
        ;
    }

    private function setCORSHeaders(): void {
        $origins = $this->getRequest()->headers->get("Origin");
        $originURL = $origins[0] ?? "";

        // Strip the protocol from domain
        $originDomain = str_replace(["https://", "http://"], "", $originURL);

        // If the domain if allowed send correct header response back
        if (in_array($originDomain, $this->getConfig()->allowed_domains)) {
            $this->response->withHeader("Access-Control-Allow-Origin", $originURL)
                ->withHeader("Access-Control-Allow-Methods", $this->getRouter()->getMethodsForPath())
                ->withHeader("Access-Control-Allow-Headers", ["Authorization", "Content-Type", "Process-Data"])
                ->withHeader("Vary", "Origin")
            ;
        }
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

    public function handleRequest(): void {
        $request = $this->getRequest();

        $this->response = $this->getRouter()->performRequest();

        if ($this->response->headers->get("ETag", "") === $request->headers->get("If-None-Match")) {
            $this->response->withStatus(304)
                ->withContent(null)
            ;
        }

        $this->setCORSHeaders();

        if (!is_null($this->response->getContent())) {
            $this->response->addHeader("Content-Type", "application/json");
        }

        $this->response->send();
    }
}
