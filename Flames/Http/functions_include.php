<?php
declare(strict_types=1);


// HttpGuzzle fork: https://github.com/guzzle/guzzle

// Don't redefine the functions if included multiple times.
if (!\function_exists('Http\describe_type')) {
    require __DIR__.'/functions.php';
}
