<?php

declare(strict_types=1);

/**
 * The Plugs Framework
 *
 * @package ThePlugs
 * @author  ThePlugs Team
 * @license https://opensource.org/licenses/MIT MIT License
 */

if (!function_exists('response')) {
    /**
     * Return a new response from the application.
     *
     * @param string $content
     * @param int $status
     * @param array $headers
     * @return \Plugs\Http\Response\Response
     */
    function response(string $content = '', int $status = 200, array $headers = []): \Plugs\Http\Response\Response
    {
        return new \Plugs\Http\Response\Response($content, $status, $headers);
    }
}

if (!function_exists('redirect')) {
    /**
     * Get an instance of the redirector.
     *
     * @param string|null $to
     * @param int $status
     * @param array $headers
     * @return \Plugs\Http\Response\Response
     */
    function redirect(?string $to = null, int $status = 302, array $headers = []): \Plugs\Http\Response\Response
    {
        if ($to === null) {
            $to = $_SERVER['HTTP_REFERER'] ?? '/';
        }

        $headers['Location'] = $to;
        return response('', $status, $headers);
    }
}

if (!function_exists('abort')) {
    /**
     * Throw an HttpException with the given data.
     *
     * @param int $code
     * @param string $message
     * @param array $headers
     * @return never
     */
    function abort(int $code, string $message = '', array $headers = []): never
    {
        throw new \Exception($message ?: "HTTP $code Error", $code);
    }
}