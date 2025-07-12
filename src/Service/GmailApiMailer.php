<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;


class GmailApiMailer
{
    private HttpClientInterface $client;
    private LoggerInterface $logger;

    private string $clientId;
    private string $clientSecret;
    private string $refreshToken;
    private string $from;

    public function __construct(HttpClientInterface $client, LoggerInterface $logger, string $clientId, string $clientSecret, string $refreshToken, string $from)
    {
        $this->client = $client;
        $this->logger = $logger;

        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->refreshToken = $refreshToken;
        $this->from = $from;
    }

    private function getAccessToken(): ?string
    {
        try {
            $response = $this->client->request('POST', 'https://oauth2.googleapis.com/token', [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'body' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $this->refreshToken,
                    'grant_type' => 'refresh_token',
                ],
            ]);

            if (200 !== $response->getStatusCode()) {
                $this->logger->error('Failed to fetch Gmail access token', [
                    'status' => $response->getStatusCode(),
                    'body' => $response->getContent(false),
                ]);
                return null;
            }

            $data = $response->toArray(false);
            return $data['access_token'] ?? null;
        } catch (\Throwable $e) {
            $this->logger->error('Error requesting Gmail token', ['exception' => $e]);
            return null;
        }

    }

    public function send(string $to, string $subject, string $text): void
    {
        $token = $this->getAccessToken();
        if (!$token) {
            $this->logger->error('No Gmail access token available');

            return;
        }
        $raw = sprintf("From: %s\r\nTo: %s\r\nSubject: %s\r\n\r\n%s", $this->from, $to, $subject, $text);
        $raw = rtrim(strtr(base64_encode($raw), ['+' => '-', '/' => '_', '=' => '']));
        try {
            $response = $this->client->request('POST', 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => ['raw' => $raw],
            ]);

            if (200 !== $response->getStatusCode()) {
                $this->logger->error('Failed to send Gmail message', [
                    'status' => $response->getStatusCode(),
                    'body' => $response->getContent(false),
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Error sending Gmail message', ['exception' => $e]);
        }
=
    }
}
