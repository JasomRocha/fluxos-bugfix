<?php

use App\Http\Middleware\AutenticarMcp;
use App\Mcp\Servers\FluxosServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp', FluxosServer::class)->middleware([AutenticarMcp::class, 'throttle:60,1']);
