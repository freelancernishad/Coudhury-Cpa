<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        // Skip ApiResponse middleware for the /files/{path} route
        if ($request->is('files/*')) {

            return $next($request);
        }

        // Capture the response
        $response = $next($request);

        // Check if the response is a valid Response object
        if ($response instanceof Response) {
            // Decode the response content if it's JSON
            $responseData = json_decode($response->getContent(), true) ?? [];

            // Initialize the formatted response structure
            $formattedResponse = [
                // 'encoded' => [
                    'data' => $responseData,
                    'isError' => false,
                    'error' => null,
                    'status_code' => $response->status(),
                // ],
                // 'jrn' => microtime(true) * 10000,
            ];

            // Check if the response status indicates an error (>=400)
            if ($response->status() >= 400) {
                // $formattedResponse['encoded']['isError'] = true;
                $formattedResponse['isError'] = true;

                // Extract the error message from response data or use a default error message
                $errorMessage = $responseData['error'] ?? 'An error occurred';

                // Set the error details in the response structure
                // $formattedResponse['encoded']['error'] = [
                $formattedResponse['error'] = [
                    'code' => $response->status(),
                    'message' => Response::$statusTexts[$response->status()] ?? 'Unknown error',
                    'errMsg' => $errorMessage,
                ];

                // Adjust status code if necessary
                // $formattedResponse['encoded']['status_code'] = $response->status();
                $formattedResponse['status_code'] = $response->status();
            }

            // Return a 200 status code with the formatted response for consistency
            return response()->json($formattedResponse, 200);
        }

        // If the response is not an instance of Response, return it as is
        return $response;
    }
}
