<?php
declare(strict_types=1);


http_response_code(500);
echo json_encode(['error' => 'Missing mbstring extension.']);
