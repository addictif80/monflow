<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client pour l'API REST native de Navidrome.
 *
 * Navidrome n'a PAS de fonctionnalité suspend/disable native.
 * Pour suspendre : on randomise le mot de passe (bloque l'accès, conserve les données).
 * Pour réactiver : on restaure le mot de passe original depuis le champ encrypted_password.
 */
class NavidromeService
{
    private string $baseUrl;
    private string $adminUser;
    private string $adminPassword;
    private ?string $token = null;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('navidrome.url'), '/');
        $this->adminUser = config('navidrome.admin_user');
        $this->adminPassword = config('navidrome.admin_password');
    }

    private function authenticate(): string
    {
        $response = retry(3, fn () => Http::timeout(10)->post("{$this->baseUrl}/auth/login", [
            'username' => $this->adminUser,
            'password' => $this->adminPassword,
        ]), function (int $attempt) {
            return $attempt * 1000;
        }, function ($e) {
            return $e instanceof \Illuminate\Http\Client\ConnectionException;
        });
        $response->throw();
        $this->token = $response->json('token');
        return $this->token;
    }

    private function request(string $method, string $endpoint, array $data = []): array
    {
        if (!$this->token) {
            $this->authenticate();
        }

        $doRequest = function () use ($method, $endpoint, $data) {
            return Http::timeout(10)
                ->withHeaders(['x-nd-authorization' => "Bearer {$this->token}"])
                ->$method("{$this->baseUrl}/api{$endpoint}", $data);
        };

        $retryWhen = function ($e) {
            return $e instanceof \Illuminate\Http\Client\ConnectionException;
        };

        $retryDelay = function (int $attempt) {
            return $attempt * 1000;
        };

        $response = retry(3, $doRequest, $retryDelay, $retryWhen);

        // Re-auth on 401
        if ($response->status() === 401) {
            $this->authenticate();
            $response = retry(3, $doRequest, $retryDelay, $retryWhen);
        }

        $response->throw();
        return $response->json() ?? [];
    }

    public function createUser(string $username, string $password, string $name = '', string $email = ''): array
    {
        $data = $this->request('post', '/user', [
            'userName' => $username,
            'name' => $name ?: $username,
            'email' => $email,
            'password' => $password,
            'isAdmin' => false,
        ]);
        Log::info("Navidrome: created user {$username} (ID: " . ($data['id'] ?? '?') . ")");
        return $data;
    }

    public function updateUser(string $navidromeId, array $fields): array
    {
        $map = ['username' => 'userName', 'name' => 'name', 'email' => 'email'];
        $payload = [];
        foreach ($map as $key => $apiKey) {
            if (isset($fields[$key])) {
                $payload[$apiKey] = $fields[$key];
            }
        }
        if (empty($payload)) return [];
        if (!isset($payload['userName'])) {
            $user = $this->getUser($navidromeId);
            $payload['userName'] = $user['userName'] ?? '';
        }
        $data = $this->request('put', "/user/{$navidromeId}", $payload);
        Log::info("Navidrome: updated user {$navidromeId}");
        return $data;
    }

    public function changePassword(string $navidromeId, string $newPassword): array
    {
        $user = $this->getUser($navidromeId);
        $data = $this->request('put', "/user/{$navidromeId}", [
            'userName' => $user['userName'] ?? $user['name'] ?? '',
            'password' => $newPassword,
        ]);
        Log::info("Navidrome: changed password for user {$navidromeId}");
        return $data;
    }

    /**
     * Suspendre un utilisateur en randomisant son mot de passe Navidrome.
     * L'utilisateur est bloqué de tous les clients mais ses données sont conservées.
     */
    public function suspendUser(string $navidromeId): void
    {
        $randomPassword = bin2hex(random_bytes(32));
        $this->changePassword($navidromeId, $randomPassword);
        Log::info("Navidrome: suspended user {$navidromeId} (password randomized)");
    }

    /**
     * Réactiver un utilisateur en restaurant son mot de passe original.
     */
    public function reactivateUser(string $navidromeId, string $originalPassword): void
    {
        $this->changePassword($navidromeId, $originalPassword);
        Log::info("Navidrome: reactivated user {$navidromeId} (password restored)");
    }

    public function deleteUser(string $navidromeId): void
    {
        if (!$this->token) {
            $this->authenticate();
        }

        $doRequest = function () use ($navidromeId) {
            return Http::timeout(10)
                ->withHeaders(['x-nd-authorization' => "Bearer {$this->token}"])
                ->delete("{$this->baseUrl}/api/user/{$navidromeId}");
        };

        $retryWhen = function ($e) {
            return $e instanceof \Illuminate\Http\Client\ConnectionException;
        };

        $retryDelay = function (int $attempt) {
            return $attempt * 1000;
        };

        $response = retry(3, $doRequest, $retryDelay, $retryWhen);

        if ($response->status() === 401) {
            $this->authenticate();
            $response = retry(3, $doRequest, $retryDelay, $retryWhen);
        }

        $response->throw();
        Log::info("Navidrome: deleted user {$navidromeId}");
    }

    public function getUser(string $navidromeId): array
    {
        return $this->request('get', "/user/{$navidromeId}");
    }

    public function listUsers(): array
    {
        return $this->request('get', '/user?_end=10000&_order=ASC&_sort=userName&_start=0');
    }

    public function getRecentAlbums(int $limit = 10): array
    {
        return $this->request('get', "/album?_end={$limit}&_order=DESC&_sort=createdAt&_start=0");
    }

    public function getTopPlayedArtists(int $limit = 5): array
    {
        return $this->request('get', "/artist?_end={$limit}&_order=DESC&_sort=playCount&_start=0");
    }

    public function getTopPlayedSongs(int $limit = 10): array
    {
        return $this->request('get', "/song?_end={$limit}&_order=DESC&_sort=playCount&_start=0");
    }

    public function testConnection(): array
    {
        try {
            $this->authenticate();
            return ['success' => true, 'message' => 'Connexion réussie'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => "Erreur: {$e->getMessage()}"];
        }
    }
}
