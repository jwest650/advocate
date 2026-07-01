<?php

namespace App\Http\Controllers;

use App\Services\GoogleCalendarService;
use App\Models\Setting;
use Illuminate\Http\Request;
use Google_Client;
use Google_Service_Calendar;

class GoogleCalendarController extends Controller
{
    protected $calendarService;

    public function __construct(GoogleCalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    public function getEvents(Request $request)
    {
        try {
            $events = $this->calendarService->getEvents(auth()->id(), $request->get('maxResults', 50));
            
            return response()->json([
                'success' => true,
                'events' => $events
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'events' => []
            ]);
        }
    }

    public function syncEvents(Request $request)
    {
        try {
            // Check if Google Calendar is enabled
            $isEnabled = $this->calendarService->isEnabled(createdBy());
            $isAuthorized = $this->calendarService->isAuthorized(createdBy());
            
            \Log::info('Google Calendar check', [
                'user_id' => createdBy(),
                'isEnabled' => $isEnabled,
                'isAuthorized' => $isAuthorized
            ]);
            
            if (!$isEnabled || !$isAuthorized) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Calendar not configured. Please configure Google Calendar JSON credentials in settings.',
                    'needsConfig' => true,
                    'debug' => [
                        'isEnabled' => $isEnabled,
                        'isAuthorized' => $isAuthorized
                    ]
                ]);
            }

            $events = $this->calendarService->getEvents(auth()->id(), 100);
            
            return response()->json([
                'success' => true,
                'message' => 'Calendar events synchronized successfully',
                'events' => $events
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync calendar events: ' . $e->getMessage(),
                'needsAuth' => str_contains($e->getMessage(), 'not authorized') || str_contains($e->getMessage(), 'expired')
            ]);
        }
    }

    public function authorize(Request $request)
    {
        try {
            $client = new Google_Client();
            $settings = Setting::where('user_id', createdBy())
                ->whereIn('key', ['googleCalendarClientId', 'googleCalendarSecret', 'googleCalendarRedirectUri'])
                ->pluck('value', 'key');

            if (!isset($settings['googleCalendarClientId']) || !isset($settings['googleCalendarSecret'])) {
                return redirect()->back()->with('error', 'Google Calendar credentials not configured');
            }

            $client->setClientId($settings['googleCalendarClientId']);
            $client->setClientSecret($settings['googleCalendarSecret']);
            $client->setRedirectUri($settings['googleCalendarRedirectUri'] ?? route('google-calendar.callback'));
            $client->setScopes(Google_Service_Calendar::CALENDAR);
            $client->setAccessType('offline');
            $client->setPrompt('select_account consent');

            $authUrl = $client->createAuthUrl();
            return redirect($authUrl);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to authorize Google Calendar: ' . $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        try {
            $client = new Google_Client();
            $settings = Setting::where('user_id', createdBy())
                ->whereIn('key', ['googleCalendarClientId', 'googleCalendarSecret', 'googleCalendarRedirectUri'])
                ->pluck('value', 'key');

            $client->setClientId($settings['googleCalendarClientId']);
            $client->setClientSecret($settings['googleCalendarSecret']);
            $client->setRedirectUri($settings['googleCalendarRedirectUri'] ?? route('google-calendar.callback'));

            if ($request->has('code')) {
                $token = $client->fetchAccessTokenWithAuthCode($request->get('code'));
                
                if (isset($token['access_token'])) {
                    // Store the access token
                    Setting::updateOrCreate(
                        ['user_id' => createdBy(), 'key' => 'googleCalendarAccessToken'],
                        ['value' => json_encode($token)]
                    );

                    return redirect()->route('calendar.index', ['google' => 1])->with('success', 'Google Calendar connected successfully');
                } else {
                    return redirect()->route('calendar.index')->with('error', 'Failed to get access token');
                }
            }

            return redirect()->route('calendar.index')->with('error', 'Authorization code not received');
        } catch (\Exception $e) {
            return redirect()->route('calendar.index')->with('error', 'Failed to complete authorization: ' . $e->getMessage());
        }
    }
}