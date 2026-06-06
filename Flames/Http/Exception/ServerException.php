<?php
declare(strict_types=1);


namespace Flames\Http\Exception;

/**
 * Exception when a server error is encountered (5xx codes)
 */
class ServerException extends BadResponseException
{
}
