<?php

namespace Hnllyrp\LaravelSupport\Http\Middleware;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 跨域中间件
 *
 * @link https://github.com/fruitcake/laravel-cors
 */
class Cors
{

    /** @var \Illuminate\Contracts\Container\Container $container */
    protected $container;

    protected $allowedOrigins = [];

    protected $allowedOriginsPatterns = [];
    protected $allowedMethods = [];
    protected $allowedHeaders = [];
    protected $exposedHeaders = [];
    protected $supportsCredentials = false;
    protected $maxAge = 0;

    protected $allowAllOrigins = false;
    protected $allowAllMethods = false;
    protected $allowAllHeaders = false;

    public function __construct(Container $container)
    {
        $this->container = $container;

        $this->setOptions();
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if we're dealing with CORS and if we should handle it
        if (!$this->shouldRun($request)) {
            return $next($request);
        }

        // 默认允许所有
        if ($this->allowAllOrigins === true) {
            $response = $next($request);
            return $this->simple($request, $response);
        }

        // OPTIONS 预检请求
        if ($request->getMethod() === 'OPTIONS' && $request->headers->has('Access-Control-Request-Method')) {

            $response = $this->handlePreflightRequest($request);

            $this->varyHeader($response, 'Access-Control-Request-Method');

            return $response;
        }

        // Handle the request
        $response = $next($request);

        // OPTIONS 预检请求
        if ($request->getMethod() === 'OPTIONS') {
            $this->varyHeader($response, 'Access-Control-Request-Method');
        }

        // 简单请求
        if (!$response->headers->has('Access-Control-Allow-Origin')) {
            // Add the CORS headers to the Response
            $this->configureAllowedOrigin($response, $request);

            if ($response->headers->has('Access-Control-Allow-Origin')) {
                // $this->configureAllowCredentials($response, $request);
                if ($this->supportsCredentials) {
                    $response->headers->set('Access-Control-Allow-Credentials', 'true');
                }

                // $this->configureExposedHeaders($response, $request);
                if ($this->exposedHeaders) {
                    $response->headers->set('Access-Control-Expose-Headers', implode(', ', $this->exposedHeaders));
                }
            }
        }

        return $response;
    }

    /**
     * 最简单宽松条件的，有安全风险
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    protected function simple(Request $request, Response $response)
    {
        $allow_origin = config('cors.allowed_origins', ['*', 'http://localhost:8080']);

        $origin = (string)$request->headers->get('Origin');

        if (in_array($origin, $allow_origin)) {
            if (!$response->headers->has('Access-Control-Allow-Origin')) {
                if ($origin) {
                    $response->headers->set('Access-Control-Allow-Origin', $origin);
                } else {
                    $response->headers->set('Access-Control-Allow-Origin', '*');
                }
            }

            $response->headers->set('Access-Control-Allow-Headers', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, OPTIONS, DELETE');
            $response->headers->set('Access-Control-Max-Age', 7200);
            // $response->headers->set('Access-Control-Expose-Headers', 'Authorization, authenticated');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected function shouldRun(Request $request)
    {
        // Get the paths from the config or the middleware
        $paths = $this->getPathsByHost($request->getHost());

        foreach ($paths as $path) {
            if ($path !== '/') {
                $path = trim($path, '/');
            }

            if ($request->fullUrlIs($path) || $request->is($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Paths by given host or string values in config by default
     *
     * @param string $host
     * @return array
     */
    protected function getPathsByHost(string $host)
    {
        $paths = $this->container['config']->get('cors.paths', []);

        // If where are paths by given host
        if (isset($paths[$host])) {
            return $paths[$host];
        }

        // Defaults
        return array_filter($paths, function ($path) {
            return is_string($path);
        });
    }

    /**
     * OPTIONS 预检请求
     * @param Request $request
     * @return Response
     */
    public function handlePreflightRequest(Request $request): Response
    {
        $response = new Response();

        $response->setStatusCode(204);

        $this->configureAllowedOrigin($response, $request);

        if ($response->headers->has('Access-Control-Allow-Origin')) {
            if ($this->supportsCredentials) {
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
            }

            $this->configureAllowedMethods($response, $request);

            $this->configureAllowedHeaders($response, $request);

            if ($this->maxAge !== null) {
                $response->headers->set('Access-Control-Max-Age', (string)$this->maxAge);
            }
        }

        return $response;
    }

    public function varyHeader(Response $response, string $header)
    {
        if (!$response->headers->has('Vary')) {
            $response->headers->set('Vary', $header);
        } elseif (!in_array($header, explode(', ', (string)$response->headers->get('Vary')))) {
            $response->headers->set('Vary', ((string)$response->headers->get('Vary')) . ', ' . $header);
        }

        return $response;
    }

    private function configureAllowedOrigin(Response $response, Request $request): void
    {
        if ($this->allowAllOrigins === true && !$this->supportsCredentials) {
            // Safe+cacheable, allow everything
            $response->headers->set('Access-Control-Allow-Origin', '*');
        } elseif ($this->isSingleOriginAllowed()) {
            // Single origins can be safely set
            $response->headers->set('Access-Control-Allow-Origin', array_values($this->allowedOrigins)[0]);
        } else {
            // For dynamic headers, set the requested Origin header when set and allowed
            if ($request->headers->has('Origin') && $this->isOriginAllowed($request)) {
                $response->headers->set('Access-Control-Allow-Origin', (string)$request->headers->get('Origin'));
            }

            $this->varyHeader($response, 'Origin');
        }
    }

    /**
     * 获取配置
     */
    public function setOptions(): void
    {
        $options = $this->container['config']->get('cors', []);

        $this->allowedOrigins = $options['allowedOrigins'] ?? $options['allowed_origins'] ?? $this->allowedOrigins;
        $this->allowedOriginsPatterns = $options['allowedOriginsPatterns'] ?? $options['allowed_origins_patterns'] ?? $this->allowedOriginsPatterns;
        $this->allowedMethods = $options['allowedMethods'] ?? $options['allowed_methods'] ?? $this->allowedMethods;
        $this->allowedHeaders = $options['allowedHeaders'] ?? $options['allowed_headers'] ?? $this->allowedHeaders;
        $this->supportsCredentials = $options['supportsCredentials'] ?? $options['supports_credentials'] ?? $this->supportsCredentials;

        $maxAge = $this->maxAge;
        if (array_key_exists('maxAge', $options)) {
            $maxAge = $options['maxAge'];
        } elseif (array_key_exists('max_age', $options)) {
            $maxAge = $options['max_age'];
        }
        $this->maxAge = $maxAge === null ? null : (int)$maxAge;

        $exposedHeaders = $options['exposedHeaders'] ?? $options['exposed_headers'] ?? $this->exposedHeaders;
        $this->exposedHeaders = $exposedHeaders === false ? [] : $exposedHeaders;

        $this->normalizeOptions();
    }

    private function normalizeOptions(): void
    {
        // Normalize case
        $this->allowedHeaders = array_map('strtolower', $this->allowedHeaders);
        $this->allowedMethods = array_map('strtoupper', $this->allowedMethods);

        // Normalize ['*'] to true
        $this->allowAllOrigins = in_array('*', $this->allowedOrigins);
        $this->allowAllHeaders = in_array('*', $this->allowedHeaders);
        $this->allowAllMethods = in_array('*', $this->allowedMethods);

        // Transform wildcard pattern
        if (!$this->allowAllOrigins) {
            foreach ($this->allowedOrigins as $origin) {
                if (strpos($origin, '*') !== false) {
                    $this->allowedOriginsPatterns[] = $this->convertWildcardToPattern($origin);
                }
            }
        }
    }

    /**
     * Create a pattern for a wildcard, based on Str::is() from Laravel
     *
     * @see https://github.com/laravel/framework/blob/5.5/src/Illuminate/Support/Str.php
     * @param string $pattern
     * @return string
     */
    private function convertWildcardToPattern($pattern)
    {
        $pattern = preg_quote($pattern, '#');

        // Asterisks are translated into zero-or-more regular expression wildcards
        // to make it convenient to check if the strings starts with the given
        // pattern such as "*.example.com", making any string check convenient.
        $pattern = str_replace('\*', '.*', $pattern);

        return '#^' . $pattern . '\z#u';
    }

    private function isSingleOriginAllowed(): bool
    {
        if ($this->allowAllOrigins === true || count($this->allowedOriginsPatterns) > 0) {
            return false;
        }

        return count($this->allowedOrigins) === 1;
    }

    public function isOriginAllowed(Request $request): bool
    {
        if ($this->allowAllOrigins === true) {
            return true;
        }

        $origin = (string)$request->headers->get('Origin');

        if (in_array($origin, $this->allowedOrigins)) {
            return true;
        }

        foreach ($this->allowedOriginsPatterns as $pattern) {
            if (preg_match($pattern, $origin)) {
                return true;
            }
        }

        return false;
    }

    private function configureAllowedMethods(Response $response, Request $request): void
    {
        if ($this->allowAllMethods === true) {
            $allowMethods = strtoupper((string)$request->headers->get('Access-Control-Request-Method'));
            $this->varyHeader($response, 'Access-Control-Request-Method');
        } else {
            $allowMethods = implode(', ', $this->allowedMethods);
        }

        $response->headers->set('Access-Control-Allow-Methods', $allowMethods);
    }

    private function configureAllowedHeaders(Response $response, Request $request): void
    {
        if ($this->allowAllHeaders === true) {
            $allowHeaders = (string)$request->headers->get('Access-Control-Request-Headers');
            $this->varyHeader($response, 'Access-Control-Request-Headers');
        } else {
            $allowHeaders = implode(', ', $this->allowedHeaders);
        }

        $response->headers->set('Access-Control-Allow-Headers', $allowHeaders);
    }


}
