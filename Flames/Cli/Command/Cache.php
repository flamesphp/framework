<?php
namespace Flames\Cli\Command;
/** @deprecated @internal */
class Cache { public function __construct($d){} public function run($debug=false){return (new \Flames\Forge\Cli\Command\Cache($d ?? null))->run($debug);} }
