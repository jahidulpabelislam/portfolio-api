<?php

/**
 * The 'index' of the application/API.
 */

namespace App;

use App\HTTP\Controller\Auth;
use App\Projects\Controller as ProjectsController;
use App\HTTP\Request;
use App\HTTP\Response;
use App\Utils\Str;
use DateTime;
use JPI\Utils\Singleton;

class Core {

    use Singleton;

    public const VERSION = "4";

    /**
     * @var Request|null
     */
    protected $request = null;

    /**
     * @var Response|null
     */
    protected $response = null;

    /**
     * @var Router|null
     */
    protected $router = null;

    public function getRequest(): Request {
        if (is_null($this->request)) {
            $this->request = new Request();
        }

        return $this->request;
    }

    private function initRoutes(): void {
        $router = $this->router;

        $router->setBasePath("/v" . static::VERSION);

        $projectsController = ProjectsController::class;
        $authController = Auth::class;

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
        $router->addRoute("/auth/status/", "GET", [$authController, "getStatus"]);
    }

    public function getRouter(): Router {
        if (is_null($this->router)) {
            $this->router = new Router($this->getRequest());
            $this->initRoutes();
        }

        return $this->router;
    }

    /**
     * @param $uri string|string[]
     * @return string
     */
    public function makeFullURL($uri): string {
        if (is_array($uri)) {
            $uri = implode("/", $uri);
        }

        $request = $this->getRequest();

        $protocol = $request->server->get("HTTPS") === "on" ? "https" : "http";
        $domain = Str::removeTrailingSlash($request->server->get("SERVER_NAME"));
        $uri = Str::removeLeadingSlash($uri);

        return Str::addTrailingSlash("$protocol://$domain/$uri");
    }

    public static function makeUrl(string $base, array $params): string {
        $fullURL = Str::addTrailingSlash($base);

        if ($params && count($params)) {
            $fullURL .= "?" . http_build_query($params);
        }

        return $fullURL;
    }

    private function setCORSHeaders(): void {
        $origins = $this->getRequest()->headers->get("Origin");
        $originURL = $origins[0] ?? "";

        // Strip the protocol from domain
        $originDomain = str_replace(["https://", "http://"], "", $originURL);

        // If the domain if allowed send correct header response back
        if (in_array($originDomain, Config::get()->allowed_domains)) {
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

        $sendPretty = Str::toBool($request->getParam("pretty"));
        $this->response->send($sendPretty);
    }
}
