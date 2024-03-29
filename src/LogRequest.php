<?php

namespace Akempes\RequestLogging;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class LogRequest
{

    public $startedAt;
    public $dbRecordId;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $this->startedAt = microtime(true);

        if (
            config('request-logging.enabled', false) &&
            $this->inMethodsArray($request) &&
            !$this->inExceptArray($request)
        ) {

            $this->logRequest($request);

            $response = $next($request);

            $this->logResponse($response, $request);

            return $response;
        }

        return $next($request);
    }

    private function logRequest(Request $request)
    {
        $method = strtoupper($request->getMethod());

        $uri = $request->getPathInfo();

        $bodyAsJson = json_encode($request->except(config('request-logging.exclude-request-fields', ['password', 'password_confirmation'])));

        $files = collect(iterator_to_array($request->files))
            ->map(function ($file)
            {
                return $this->flattenFiles($file);
            })
            ->flatten();
        ;

        $data = [
            '{microTimeStamp}' => $this->startedAt,
            '{requestId}' => $this->getRequestId(),
            '{ip}' => $request->ip(),
            '{method}' => $method,
            '{uri}' => $uri,
            '{requestBody}' => $bodyAsJson,
            '{files}' => $files->implode(','),
        ];
        $format = '#{requestId} IP: {ip} {method} {uri} - Body: {requestBody} - Files: {files}';
        $message = strtr(config('request-logging.request-log-format', $format), $data);

        $this->writeMessage($message);

        if (config('request-logging.database-logging.enabled')) {
            $this->dbRecordId = DB::table(config('request-logging.database-logging.table'))
                ->insertGetId([
                    'ip' => $request->ip(),
                    'method' => $method,
                    'uri' => $uri,
                    'body' => $bodyAsJson,
                    'request_size' => strlen($bodyAsJson),
                    'files' => json_encode($files),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
        }
    }

    private function logResponse($response, Request $request)
    {
        $duration = (microtime(true) - $this->startedAt) * 1000;

        $durationLimit = config('request-logging.request-duration-limit', false);
        if($durationLimit && $duration > $durationLimit) {
            $this->writeWarningMessage('Request exceeded response duration threshold. It took ' . $duration . 'ms to respond to ' . $request->getPathInfo());
        }

        $status = strtoupper($response->getStatusCode());
        $userId = optional($request->user())->id;

        $redirect = '';
        $target = '';
        if ($status >= 300 && $status < 400) {
            $target = $response->getTargetUrl();
            $redirect = "Redirecting to $target";
        }

        $bodyAsJson = $request->expectsJson() ? json_encode(Arr::except(json_decode($response->getContent(), true), config('request-logging.exclude-response-fields', []))) : (config('request-logging.show-response-html', false) ? $response->getContent() : 'Non-JSON content returned');

        $data = [
            '{microTimeStamp}' => $this->startedAt,
            '{requestId}' => $this->getRequestId(),
            '{userId}' => $userId ? $userId : 'unknown ',
            '{ip}' => $request->ip(),
            '{databaseId}' => $this->dbRecordId,
            '{responseStatusCode}' => $status,
            '{duration}' => $duration . 'ms',
            '{responseBody}' => $bodyAsJson,
            '{targetUrl}' => $target,
            '{isRedirecting}' => $redirect,
        ];
        $format = '#{requestId} User: #{userId} IP: {ip} DB: #{databaseId} {responseStatusCode} - Duration: {duration} - Body: {responseBody} {isRedirecting}';
        $message = strtr(config('request-logging.response-log-format', $format), $data);

        $this->writeMessage($message);

        if (config('request-logging.database-logging.enabled')) {
            DB::table(config('request-logging.database-logging.table'))
                ->where('id', $this->dbRecordId)
                ->update([
                    'user_id' => $userId,
                    'status' => $status,
                    'duration' => $duration,
                    'response' => config('request-logging.database-logging.limit-response', 0) > 0 ? substr($bodyAsJson, 0, config('request-logging.database-logging.limit-response')) . '...' : $bodyAsJson,
                    'response_size' => strlen($bodyAsJson),
                    'updated_at' => Carbon::now(),
                ]);

            if (Cache::get('request-logging-truncate-table', 0) < Carbon::now()->unix()) {

                DB::table(config('request-logging.database-logging.table'))
                    ->where('created_at', '<', Carbon::now()->subDay(config('request-logging.database-logging.persistence', 2)))
                    ->delete();

                Cache::set('request-logging-truncate-table', Carbon::now()->addDay()->startOfDay()->unix());
            }
        }
    }

    public function flattenFiles($file)
    {
        if ($file instanceof UploadedFile) {
            return $file->getClientOriginalName();
        }

        return collect($file)->map(function ($file)
        {
            return $this->flattenFiles($file);
        });
    }

    private function getRequestId()
    {
        return Str::after($this->startedAt, '.');
    }

    private function writeMessage($message)
    {
        Log::stack(config('request-logging.log-channels', []))->{config('request-logging.log-level', 'info')}($message);
    }

    private function writeWarningMessage($message)
    {
        Log::stack(config('request-logging.warning-log-channels', []))->{config('request-logging.warning-log-level', 'warning')}($message);
    }

    private function inMethodsArray($request)
    {
        foreach (config('request-logging.methods', []) as $method) {
            if (strtoupper($method) === $request->getMethod()) {
                return true;
            }
        }

        return false;
    }

    private function inExceptArray($request)
    {
        foreach (config('request-logging.exclude-routes', []) as $route) {
            if ($route !== '/') {
                $route = trim($route, '/');
            }

            if ($request->is($route)) {
                return true;
            }
        }

        return false;
    }

}
