<?php

namespace App\Http\Middleware;

use Closure;

class IpAddressProtectionMiddleware
{


    protected $allowedIPs = [
        '',
        'https://msronytraders.com',
        'http://test.localhost:8000',
        'https://amardesh.xyz',
        'http://amardesh.xyz',
        //uniontax.gov.bd
        'https://alowakhowa.uniontax.gov.bd',
        'https://amarkhana.uniontax.gov.bd',
        'https://balarampur.uniontax.gov.bd',
        'https://banghari.uniontax.gov.bd',
        'https://banglabandha.uniontax.gov.bd',
        'https://bhojoanpur.uniontax.gov.bd',
        'https://boda.uniontax.gov.bd',
        'https://bodap.uniontax.gov.bd',
        'https://boroshoshi.uniontax.gov.bd',
        'https://buraburi.uniontax.gov.bd',
        'https://chaklahat.uniontax.gov.bd',
        'https://chandanbari.uniontax.gov.bd',
        'https://chargopalpur.uniontax.gov.bd',
        'https://chengthihazradanga.uniontax.gov.bd',
        'https://chilahati.uniontax.gov.bd',
        'https://dandopal.uniontax.gov.bd',
        'https://debiduba.uniontax.gov.bd',
        'https://debiganjp.uniontax.gov.bd',
        'https://debiganjsadar.uniontax.gov.bd',
        'https://debnagar.uniontax.gov.bd',
        'https://dhakkamara.uniontax.gov.bd',
        'https://dhamor.uniontax.gov.bd',
        'https://garinabari.uniontax.gov.bd',
        'https://hafizabad.uniontax.gov.bd',
        'https://haribhasa.uniontax.gov.bd',
        'https://jholaishalshiri.uniontax.gov.bd',
        'https://kajoldighikaligonj.uniontax.gov.bd',
        'https://kamatkajoldighi.uniontax.gov.bd',
        'https://kutubpur.uniontax.gov.bd',
        'https://magura.uniontax.gov.bd',
        'https://mareabamonhat.uniontax.gov.bd',
        'https://mirgapur.uniontax.gov.bd',
        'https://moidandighi.uniontax.gov.bd',
        'https://pachpir.uniontax.gov.bd',
        'https://pamuli.uniontax.gov.bd',
        'https://panchagarhp.uniontax.gov.bd',
        'https://panchagarhsadar.uniontax.gov.bd',
        'https://radhanagar.uniontax.gov.bd',
        'https://sakoa.uniontax.gov.bd',
        'https://salbahan.uniontax.gov.bd',
        'https://satmara.uniontax.gov.bd',
        'https://shaldanga.uniontax.gov.bd',
        'https://sonaharmollikadaha.uniontax.gov.bd',
        'https://sundardighi.uniontax.gov.bd',
        'https://tepriganj.uniontax.gov.bd',
        'https://tetulia.uniontax.gov.bd',
        'https://tirnaihat.uniontax.gov.bd',
        'https://toria.uniontax.gov.bd',
        'https://tungibaria.uniontax.gov.bd',
        'https://test.uniontax.gov.bd',
        //pouroseba.gov.bd
        'https://boda.pouroseba.gov.bd',
        'https://debiganj.pouroseba.gov.bd',
        'https://panchagarh.pouroseba.gov.bd',
        'https://test.pouroseba.gov.bd',

        // https://uzpseba.gov.bd/
        'https://uzpseba.gov.bd',
        'https://panchagarh.uzpseba.gov.bd',
        'https://debiganj.uzpseba.gov.bd',
        'https://boda.uzpseba.gov.bd',
        'https://atwari.uzpseba.gov.bd',
        'https://tetulia.uzpseba.gov.bd',





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
