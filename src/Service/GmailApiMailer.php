<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GmailApiMailer
{
    private HttpClientInterface $client;
    private string $clientId;
    private string $clientSecret;
    private string $refreshToken;
    private string $from;

    public function __construct(HttpClientInterface $client, string $clientId, string $clientSecret, string $refreshToken, string $from)
    {
        $this->client = $client;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->refreshToken = $refreshToken;
        $this->from = $from;
    }

    private function getAccessToken(): ?string
    {
        $response = $this->client->request('POST', 'https://oauth2.googleapis.com/token', [
            'body' => [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $this->refreshToken,
                'grant_type' => 'refresh_token',
            ],
        ]);

        if (200 !== $response->getStatusCode()) {
            return null;
        }
        $data = $response->toArray(false);
        return $data['access_token'] ?? null;
    }

    public function send(string $to, string $subject, string $text): void
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return;
        }
        $raw = sprintf("From: %s\r\nTo: %s\r\nSubject: %s\r\n\r\n%s", $this->from, $to, $subject, $text);
        $raw = rtrim(strtr(base64_encode($raw), ['+' => '-', '/' => '_', '=' => '']));
        $this->client->request('POST', 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'json' => ['raw' => $raw],
        ]);
    }
}
