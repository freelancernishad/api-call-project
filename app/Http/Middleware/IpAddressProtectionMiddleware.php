<?php

namespace App\Http\Middleware;

use Closure;

class IpAddressProtectionMiddleware
{


    protected $allowedIPs = [
        '',
        'https://msronytraders.com',
        'http://test.localhost:8000',
        'https://test.uniontax.gov.bd',
        //pouroseba.gov.bd
        'https://boda.pouroseba.gov.bd',
        'https://debiganj.pouroseba.gov.bd',
        'https://panchagarh.pouroseba.gov.bd',
        'https://test.pouroseba.gov.bd',
    ];


    public function handle($request, Closure $next)
    {
       $requestIP = $request->header('Origin');
        if (!in_array($requestIP, $this->allowedIPs)) {
            return response()->json([
                'message' => 'Access denied. Your IP is not allowed.',
            ], 403);
        }

        return $next($request);
    }
}
