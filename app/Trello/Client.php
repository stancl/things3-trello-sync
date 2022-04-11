<?php

namespace App\Trello;

use Illuminate\Support\Str;
use App\Trello\API\BoardClient;
use App\Trello\API\BoardListClient;
use App\Trello\API\CardClient;
use App\Trello\API\ChecklistClient;
use App\Trello\API\LabelClient;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;

/**
 * @see https://developer.atlassian.com/cloud/trello/rest/api-group-cards/
 */
class Client
{
    use CardClient, BoardClient, BoardListClient, ChecklistClient, LabelClient;

    public static array $log = [];

    protected static function client(): PendingRequest
    {
        return Http::asJson()->baseUrl('https://api.trello.com/1/')->beforeSending(function (Request $request) {
            $url = Str::of($request->url())
                ->after('https://api.trello.com/1/')
                ->replaceMatches('/[\?\&]key\=[a-z\d]{32}/', '')
                ->replaceMatches('/[\?\&]token=[a-z\d]{64}/', '');

            static::$log[] = $request->method() . " " . $url;
        });
    }

    public static function responseReceived(ResponseReceived $event)
    {
        $request = $event->request;
        $response = $event->response;

        if (! $response->successful()) {
            dd($response->body(), $request->url());
        }
    }

    protected static function auth(): array
    {
        return config('trello.auth');
    }
}
