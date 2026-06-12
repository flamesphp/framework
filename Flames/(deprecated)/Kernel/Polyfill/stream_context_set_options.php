<?php
declare(strict_types=1);


function stream_context_set_options($context, array $options): bool { return stream_context_set_option($context, $options); }