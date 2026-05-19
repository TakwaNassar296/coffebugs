<?php

namespace App\Services;

use Exception;
use Google\Client as GoogleClient;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;

class FirebaseNotificationService
{
    protected string $credentialsPath;

    public function __construct()
    {
        $this->credentialsPath = storage_path('app/gettin-caffe-firebase-adminsdk-fbsvc-6bfe3eefdb.json');
    }

    public function sendNotification(
        string $title,
        string $body,
        string $target = 'all',
        bool $isTopic = true,
        array $extraData = []
    ): void {
        if (!file_exists($this->credentialsPath)) {
            throw new Exception("Firebase credentials file not found.");
        }

        try {
            $googleClient = new GoogleClient();
            $googleClient->setAuthConfig($this->credentialsPath);
            $googleClient->addScope('https://www.googleapis.com/auth/firebase.messaging');

            $httpClient = new GuzzleClient([
                'verify' => false,
            ]);

            $googleClient->setHttpClient($httpClient);

            $token = $googleClient->fetchAccessTokenWithAssertion();

            $formattedData = [];

            foreach ($extraData as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $formattedData[(string) $key] = json_encode($value);
                } else {
                    $formattedData[(string) $key] = (string) $value;
                }
            }

            $message = [
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $formattedData,
            ];

            if ($isTopic) {
                $message['topic'] = $target;
            } else {
                $message['token'] = $target;
            }

            $httpClient->post(
                'https://fcm.googleapis.com/v1/projects/getin-943cd/messages:send',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token['access_token'],
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'message' => $message,
                    ],
                ]
            );
        } catch (Exception $e) {
            Log::error('Firebase Notification Error: ' . $e->getMessage());
            throw $e;
        }
    }
}