<?php

namespace FridayCollective\LaravelGmail\Http\Controllers;

use FridayCollective\LaravelGmail\LaravelGmail;
use FridayCollective\LaravelGmail\Models\UserMailConfig;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    public function fetchMailConfig()
    {
        $mailConfig = auth()->user()->mailConfig;
        if ($mailConfig && $mailConfig->status !== 'pending'){
            return $mailConfig;
        }
        return null;
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
        $error = Request::capture()->get('error');

        if (!$error) {
            $stateUuid = Request::capture()->get('state');
            $mailConfig = UserMailConfig::where('state_uuid', $stateUuid)->first();

            $gmailService = new LaravelGmail($mailConfig);
            $gmailService->makeToken();

            $mailConfig->status = "active";
            $mailConfig->save();

            UserMailConfig::where('user_id', $mailConfig->user_id)
                ->where('status', 'pending')
                ->delete();
        }

        return redirect()->to(env('PORTAL_URL') . '/settings/email-integration');
    }

    public function gmailLogout()
    {
        $mailConfig = auth()->user()->mailConfig;

        try {
            $gmailService = new LaravelGmail($mailConfig);
            $gmailService->stop();
            $gmailService->logout();
        } catch (\Exception $exception) {
            try {
                $gmailService = new LaravelGmail($mailConfig);
                $gmailService->logout();
            } catch (\Exception $exception) {
                Log::warning("Error disconnecting gmail. Forcing removal.");
            }
        }

        UserMailConfig::where('user_id', auth()->user()->id)
            ->where('type', 'google')
            ->delete();


        return response()->json(['message' => 'Disconnected from Google']);
    }

}
