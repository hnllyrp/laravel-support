<?php

namespace Hnllyrp\LaravelSupport\Support;

use Hnllyrp\LaravelSupport\Support\Traits\HttpResponse;
use Illuminate\Http\JsonResponse;

class Response
{
    use HttpResponse;

    /**
     *  Respond with an accepted response and associate a location and/or content if provided.
     *
     * @param array $data
     * @param string $message
     * @param string $location
     * @return JsonResponse
     */
    public function accepted($data = [], string $message = '', string $location = '')
    {
        $response = $this->setHttpCode(202)->success($data, $message);
        if ($location) {
            $response->header('Location', $location);
        }

        return $response;
    }

    /**
     * Respond with a created response and associate a location if provided.
     *
     * @param null $data
     * @param string $message
     * @param string $location
     * @return JsonResponse
     */
    public function created($data = [], string $message = '', string $location = '')
    {
        $response = $this->setHttpCode(201)->success($data, $message);
        if ($location) {
            $response->header('Location', $location);
        }

        return $response;
    }

    /**
     * Return a 400 bad request error.
     *
     * @param string $message
     * @return JsonResponse
     */
    public function errorBadRequest(string $message = '')
    {
        return $this->setHttpCode(400)->fail($message);
    }

    /**
     * Return a 401 unauthorized error.
     *
     * @param string $message
     * @return JsonResponse
     */
    public function errorUnauthorized(string $message = '')
    {
        return $this->setHttpCode(401)->fail($message);
    }

    /**
     * Return a 403 forbidden error.
     *
     * @param string $message
     * @return JsonResponse
     */
    public function errorForbidden(string $message = '')
    {
        return $this->setHttpCode(403)->fail($message);
    }

    /**
     * Return a 404 not found error.
     *
     * @param string $message
     * @return JsonResponse
     */
    public function errorNotFound(string $message = '')
    {
        return $this->setHttpCode(404)->fail($message);
    }

    /**
     * Return a 405 method not allowed error.
     *
     * @param string $message
     * @return JsonResponse
     */
    public function errorMethodNotAllowed(string $message = '')
    {
        return $this->setHttpCode(405)->fail($message);
    }

    /**
     * Return a 500 internal server error.
     *
     * @param string $message
     * @return JsonResponse
     */
    public function errorInternal(string $message = '')
    {
        return $this->setHttpCode(500)->fail($message);
    }
}
