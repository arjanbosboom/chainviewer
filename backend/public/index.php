<?php

declare(strict_types=1);

header('Content-Type: application/json');

echo json_encode([
    'project' => 'ChainViewer',
    'status' => 'bootstrap',
    'message' => 'Phase 1 structure is in place.',
]);