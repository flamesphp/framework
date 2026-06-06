<?php
declare(strict_types=1);


namespace Flames\Http\Exception;

final class InvalidArgumentException extends \InvalidArgumentException implements GuzzleException
{
}
