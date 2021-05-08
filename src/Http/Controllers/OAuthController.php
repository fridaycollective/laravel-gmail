<?php

namespace FridayCollective\LaravelGmail\Http\Controllers;


use Carbon\Carbon;
use FridayCollective\LaravelGmail\LaravelGmail;
use FridayCollective\LaravelGmail\Models\UserMailConfig;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    public function fetchMailConfig()
    {
        return auth()->user()->mailConfig;
    }

    public function gmailRedirect()
    {
        $mailConfig = new UserMailConfig();
        $mailConfig->user_id = auth()->user()->id;
        $mailConfig->type = 'google';
        $mailConfig->initial_sync_days = 100;
        $mailConfig->state_uuid = Str::uuid()->toString();
        $mailConfig->status = "pending";
        $mailConfig->save();

        $gmailService = new LaravelGmail($mailConfig);
        return $gmailService->redirect();
    }

    public function gmailCallback()
    {
        $stateUuid = Request::capture()->get('state');
        $mailConfig = UserMailConfig::where('state_uuid', $stateUuid)->first();

        $gmailService = new LaravelGmail($mailConfig);
        $gmailService->makeToken();

        $mailConfig->status = "active";
        $mailConfig->save();

        return redirect()->to(env('PORTAL_URL') . '/settings/email-integration');
    }

    public function gmailLogout()
    {
        $mailConfig = auth()->user()->mailConfig;

        $gmailService = new LaravelGmail($mailConfig);
        $gmailService->logout();

        return response()->json(['message' => 'Disconnected from Google']);
    }

}
