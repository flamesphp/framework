<?php

/**
 * Class ErrorPage
 *
 * Handles the dispatching of 404 and 500 error pages.
 */

namespace Flames;

use Flames\Forge\Cli\Command\Build\App\StaticEx;

/**
 * Class ErrorPage
 *
 * The ErrorPage class handles dispatching of error pages.
 */
final class ErrorPage
{
    /**
     * Dispatches a 404 error response.
     *
     * This method clears the headers, routes the 404 request, and sets the appropriate
     * status code. If the routed data is not false, it sets the status code, sends the
     * headers, and outputs the routed data. If the routed data is false, it simply sets
     * the status code and sends the headers.
     *
     * @return void
     */
    public static function dispatch404() : void
    {
        Header::clear();

        $data = self::route404();
        if ($data !== false) {
            Header::set('code', 404);
            Header::send();
            echo $data;
            return;
        }

        Header::set('code', 404);
        Header::send();
    }

    /**
     * Dispatches a 500 HTTP status code response header.
     *
     * This method clears any previously set headers, sets the code to 500,
     * and sends the header to the client.
     *
     * @return void
     */
    public static function dispatch500() : void
    {
        Header::clear();
        Header::set('code', 500);
        Header::send();
    }

    /**
     * Returns the output for the 404 route if it is defined in the router metadata.
     *
     * This method retrieves the default router from the Kernel, checks if it exists. If not,
     * it returns false indicating that the 404 route does not exist.
     *
     * If no 404 route is found in the metadata, it returns false.
     *
     * @return string|bool The output for the 404 route if it exists, otherwise false.
     */
    protected static function route404() : string|bool
    {
        $router = Kernel::getDefaultRouter();
        if ($router === null) {
            return false;
        }

        $metadatas = $router->getMetadata();
        foreach ($metadatas as $metadata) {
            if ($metadata->routeFormatted === '404') {
                $currentUri = $_SERVER['REQUEST_URI'];
                $_SERVER['REQUEST_URI'] = '404';
                $match = $router->getMatch();
                $_SERVER['REQUEST_URI'] = $currentUri;
                if ($match === null) {
                    return false;
                }
                $responseData = (new StaticEx())->getResponse($match);
                return $responseData->output;
            }
        }

        return false;
    }
}