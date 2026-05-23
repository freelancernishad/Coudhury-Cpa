<?php

use Carbon\Carbon;
use App\Models\TokenBlacklist;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

function TokenBlacklist($token){
// Get the authenticated user for each guard
    $user = null;
    $guardType = null;

    if (Auth::guard('admin')->check()) {
        $user = Auth::guard('admin')->user();
        $guardType = 'admin';
    } elseif (Auth::guard('user')->check()) {
        $user = Auth::guard('user')->user();
        $guardType = 'user';
    }


    TokenBlacklist::create([
            'token' => $token,
            'user_id' => $user->id,
            'user_type' => $guardType,
            'date' => Carbon::now(),
            ]);
}



function validateRequest(array $data, array $rules)
{
    $validator = Validator::make($data, $rules);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    return null; // Return null if validation passes
}

function getAppOrigin()
{
    $request = request();
    $origin = $request->header('Origin') ?: $request->header('Referer');
    if ($origin) {
        $parsed = parse_url($origin);
        $scheme = $parsed['scheme'] ?? 'http';
        $host = $parsed['host'] ?? '';
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $originUrl = $scheme . '://' . $host . $port;
    } else {
        $originUrl = $request->getSchemeAndHttpHost();
    }

    $parsedAppUrl = parse_url($originUrl);
    $host = $parsedAppUrl['host'] ?? '';

    // Port mapping for local development
    if (isset($parsedAppUrl['port']) && $parsedAppUrl['port'] == '8000') {
        $originUrl = str_replace(':8000', ':3000', $originUrl);
    } 
    // Subdomain mappings for production/staging APIs
    elseif (str_starts_with($host, 'api.')) {
        $newHost = substr($host, 4);
        $originUrl = str_replace($host, $newHost, $originUrl);
    } elseif (str_starts_with($host, 'cu-api.')) {
        $newHost = 'cu.' . substr($host, 7);
        $originUrl = str_replace($host, $newHost, $originUrl);
    }

    return rtrim($originUrl, '/');
}

function getDomainAuthConfig()
{
    $originUrl = getAppOrigin();
    $host = parse_url($originUrl, PHP_URL_HOST);

    $isStudent = false;
    if ($host && (str_starts_with($host, 'cu.') || str_contains($host, '.cu.'))) {
        $isStudent = true;
    }

    return [
        'role' => $isStudent ? 'student' : 'client',
        'redirect_url' => $originUrl . ($isStudent ? '/student/my-courses' : '/dashboard'),
    ];
}
