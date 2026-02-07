<?php

namespace Xavcha\PageContentManager\Mcp\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMcpToken
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): \Symfony\Component\HttpFoundation\Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) config('page-content-manager.mcp.token', '');
        $requireToken = (bool) config('page-content-manager.mcp.require_token', false);

        if ($token === '') {
            if ($requireToken) {
                return response()->json([
                    'error' => 'MCP token is required but not configured.',
                ], 500);
            }

            return $next($request);
        }

        $headerName = (string) config('page-content-manager.mcp.token_header', 'X-MCP-Token');
        $provided = (string) $request->header($headerName, '');

        if ($provided === '') {
            $authorization = (string) $request->header('Authorization', '');
            if (str_starts_with($authorization, 'Bearer ')) {
                $provided = substr($authorization, 7);
            }
        }

        if (! hash_equals($token, $provided)) {
            return response()->json([
                'error' => 'Unauthorized MCP request.',
            ], 401);
        }

        return $next($request);
    }
}
