<?php
declare(strict_types=1);


namespace Flames\Framework\Controller;

use Flames\Collection\Arr;
use Flames\Framework\Connection;

/**
 *  Class RequestData
 *
 *  This class represents the data associated with a request.
 *
 * @property string $method
 * @property string|null $url
 * @property string $controller
 * @property Arr $request
 * @property Arr $uries
 * @property Arr $queries
 * @property Arr $multipart
 * @property Arr $urlEncoded
 * @property Arr|null $json
 * @property string|null $host
 * @property int|null $port
 * @property string|null $ip
 * @property string|null $command
 * @property Arr $headers
 * @property Arr $data
 */
class RequestData
{
    protected string $method;
    protected string|null $url;
    protected string|null $command;
    protected Arr $request;
    protected Arr $uries;
    protected Arr $queries;
    protected Arr $multipart;
    protected Arr $urlEncoded;
    protected Arr|null $json;
    protected string|null $host;
    protected int|null $port;
    protected string|null $ip;
    protected Arr|null $headers;
    protected Arr|null $data;

    /**
     * Constructor for the class.
     *
     * @param string $method The HTTP request method.
     * @param string|null $url The URL for the request.
     * @param Arr $queries An array of query parameters.
     * @param Arr $uries An array of URI parameters.
     * @param Arr $multipart An array of multipart data.
     * @param Arr $urlEncoded An array of url-encoded data.
     * @param Arr|null $json An optional array of JSON data. Defaults to null.
     * @param Arr $request An array representing the request.
     * @param Arr $headers An array of headers for the request.
     * @param string|null $host The host for the request. Defaults to null.
     * @param string|null $port The port for the request. Defaults to null.
     * @param string|null $ip The IP address for the request. Defaults to null.
     * @param string|null $command The command for the request. Defaults to null.
     * @param Arr|null $data An optional array of additional data. Defaults to null.
     *
     * @return null
     */
    public function __construct(string $method, string|null $url, Arr $queries, Arr $uries, Arr $multipart, Arr $urlEncoded, Arr|null $json, Arr $request, Arr $headers, string|null $host, string|null $port, string|null $ip, string|null $command, Arr|null $data)
    {
        $this->method     = $method;
        $this->url        = $url;
        $this->uries      = $uries;
        $this->queries    = $queries;
        $this->headers    = $headers;
        $this->host       = $host;
        $this->port       = (int)$port;
        $this->ip         = $ip;
        $this->multipart  = $multipart;
        $this->urlEncoded = $urlEncoded;
        $this->json       = $json;
        $this->request    = $request;
        $this->command    = $command;
        $this->data       = $data;

        return null;
    }

    /**
     * Magic method to retrieve a property value.
     *
     * @param string $key The name of the property.
     *
     * @return mixed The value of the property.
     */
    public function __get(string $key) : mixed
    {
        $key = strtolower((string)$key);

        if ($key === 'method') {
            return $this->method;
        } elseif ($key === 'url') {
            return $this->url;
        } elseif ($key === 'controller') {
            return $this->controller;
        } elseif ($key === 'uries') {
            return $this->uries;
        } elseif ($key === 'queries') {
            return $this->queries;
        } elseif ($key === 'headers') {
            return $this->headers;
        } elseif ($key === 'host') {
            return $this->host;
        } elseif ($key === 'port') {
            return $this->port;
        } elseif ($key === 'ip') {
            return $this->ip;
        } elseif ($key === 'multipart') {
            return $this->multipart;
        } elseif ($key === 'urlencoded') {
            return $this->urlEncoded;
        } elseif ($key === 'json') {
            return $this->json;
        } elseif ($key === 'request') {
            return $this->request;
        } elseif ($key === 'command') {
            return $this->command;
        } elseif ($key === 'data') {
            return $this->data;
        }

        return null;
    }

    public static function getBase()
    {
        return new RequestData(
            'GET',
            '/',
            Arr(),
            Arr(),
            Arr(),
            Arr(),
            Arr(),
            Arr(),
            Arr(),
            $_SERVER['SERVER_NAME'],
            $_SERVER['SERVER_PORT'],
            Connection::getIp(),
            null,
            null
        );
    }
}