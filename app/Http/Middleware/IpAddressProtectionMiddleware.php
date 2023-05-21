<?php

namespace App\Http\Middleware;

use Closure;

class IpAddressProtectionMiddleware
{
    public function handle($request, Closure $next)
    {
        // Define the allowed IP addresses
        $allowedIps = ['127.0.0.1', '192.168.0.1'];

        // Define the allowed domain names
        $allowedDomains = ['example.com', 'api.example.com'];

        // Get the client's IP address and domain name
        $clientIp = $request->ip();
        $clientDomain = $request->getHost();

        // Check if the client IP or domain is allowed
        if (!in_array($clientIp, $allowedIps) && !in_array($clientDomain, $allowedDomains)) {
            return response()->json([
                'message' => 'You are not authorized ip or domain',
            ], 401);
        }

        return $next($request);
    }
}
