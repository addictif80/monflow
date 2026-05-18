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
                ->withHeaders(['x-nd-authorization' => "Bearer {$this->token}", 'Cache-Control' => 'no-cache'])
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

    public function searchSongs(string $query, int $limit = 50): array
    {
        $encoded = urlencode($query);
        return $this->request('get', "/song?_end={$limit}&_order=ASC&_sort=title&_start=0&title={$encoded}");
    }

    public function getSong(string $id): array
    {
        return $this->request('get', "/song/{$id}");
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

    public function streamSong(string $id): \Illuminate\Http\Client\Response
    {
        $salt = bin2hex(random_bytes(6));
        $token = md5($this->adminPassword . $salt);
        $publicUrl = rtrim(config('navidrome.public_url'), '/');
        $url = "{$publicUrl}/rest/stream.view?" . http_build_query([
            'u' => $this->adminUser, 't' => $token, 's' => $salt,
            'v' => '1.16.1', 'c' => 'MonFlowAdmin', 'f' => 'json', 'id' => $id,
        ]);

        return retry(3, fn () => Http::timeout(30)->withOptions(['stream' => true])->get($url),
            fn (int $attempt) => $attempt * 1000,
            fn ($e) => $e instanceof \Illuminate\Http\Client\ConnectionException
        );
    }

    public function getAlbum(string $id): array
    {
        return $this->request('get', "/album/{$id}");
    }

    public function getAlbumSongs(string $albumId, int $limit = 500): array
    {
        return $this->request('get', "/song?_end={$limit}&_order=ASC&_sort=trackNumber&_start=0&album_id={$albumId}");
    }

    public function getAllSongs(int $start = 0, int $end = 100, string $sort = 'title', string $order = 'ASC', array $filters = []): array
    {
        $query = "_end={$end}&_order={$order}&_sort={$sort}&_start={$start}";
        foreach ($filters as $k => $v) {
            $query .= '&' . urlencode($k) . '=' . urlencode($v);
        }
        return $this->request('get', "/song?{$query}");
    }

    public function triggerScan(bool $full = false): void
    {
        $salt = bin2hex(random_bytes(6));
        $token = md5($this->adminPassword . $salt);
        $publicUrl = rtrim(config('navidrome.public_url'), '/');
        $params = [
            'u' => $this->adminUser, 't' => $token, 's' => $salt,
            'v' => '1.16.1', 'c' => 'MonFlowAdmin', 'f' => 'json',
        ];
        if ($full) $params['fullScan'] = 'true';
        Http::timeout(10)->get("{$publicUrl}/rest/startScan.view", $params);
    }

    private function sshPrefix(): string
    {
        $sshHost = config('navidrome.ssh_host');
        if (!$sshHost) {
            throw new \RuntimeException('NAVIDROME_SSH_HOST non configure. Necessaire pour les operations sur fichiers distants.');
        }
        $sshUser = config('navidrome.ssh_user', 'root');
        $sshPassword = config('navidrome.ssh_password');
        $sshKey = config('navidrome.ssh_key');
        $sshOpts = '-o StrictHostKeyChecking=no -o ConnectTimeout=10';
        if ($sshKey) $sshOpts .= ' -i ' . escapeshellarg($sshKey);

        $prefix = '';
        if ($sshPassword && !$sshKey) {
            $prefix = 'sshpass -p ' . escapeshellarg($sshPassword) . ' ';
        }

        return $prefix . "ssh {$sshOpts} " . escapeshellarg("{$sshUser}@{$sshHost}");
    }

    private function scpPrefix(): string
    {
        $sshPassword = config('navidrome.ssh_password');
        $sshKey = config('navidrome.ssh_key');
        $sshOpts = '-o StrictHostKeyChecking=no -o ConnectTimeout=10';
        if ($sshKey) $sshOpts .= ' -i ' . escapeshellarg($sshKey);

        $prefix = '';
        if ($sshPassword && !$sshKey) {
            $prefix = 'sshpass -p ' . escapeshellarg($sshPassword) . ' ';
        }

        return $prefix . "scp {$sshOpts}";
    }

    private function sudoPrefix(): string
    {
        $sudoPass = config('navidrome.sudo_password') ?: config('navidrome.ssh_password');
        return 'echo ' . escapeshellarg($sudoPass) . ' | sudo -S';
    }

    public function sshCommand(string $cmd): array
    {
        $remoteCmd = config('navidrome.ssh_sudo')
            ? $this->sudoPrefix() . ' ' . $cmd
            : $cmd;
        $fullCmd = $this->sshPrefix() . ' ' . escapeshellarg($remoteCmd);
        exec($fullCmd . ' 2>&1', $output, $exitCode);
        $filtered = array_filter($output, fn ($l) => !str_contains($l, '[sudo]') && !str_contains($l, 'password for'));
        return ['output' => implode("\n", $filtered), 'exitCode' => $exitCode];
    }

    public function sshWriteFile(string $remotePath, string $content): array
    {
        $sshHost = config('navidrome.ssh_host');
        if (!$sshHost) {
            throw new \RuntimeException('NAVIDROME_SSH_HOST non configure.');
        }
        $sshUser = config('navidrome.ssh_user', 'root');
        $useSudo = config('navidrome.ssh_sudo');

        $tmpFile = tempnam(sys_get_temp_dir(), 'monflow_');
        file_put_contents($tmpFile, $content);

        $remoteTmp = '/tmp/monflow_upload_' . basename($tmpFile);
        $scpCmd = $this->scpPrefix() . ' ' . escapeshellarg($tmpFile) . ' ' . escapeshellarg("{$sshUser}@{$sshHost}:{$remoteTmp}");
        exec($scpCmd . ' 2>&1', $scpOutput, $scpExit);
        @unlink($tmpFile);

        if ($scpExit !== 0) {
            return ['output' => implode("\n", $scpOutput), 'exitCode' => $scpExit];
        }

        $escapedDir = escapeshellarg(dirname($remotePath));
        $escapedPath = escapeshellarg($remotePath);
        $escapedTmp = escapeshellarg($remoteTmp);

        if ($useSudo) {
            $moveCmd = $this->sudoPrefix() . " sh -c " . escapeshellarg("mkdir -p {$escapedDir} && mv -f {$escapedTmp} {$escapedPath}");
        } else {
            $moveCmd = "mkdir -p {$escapedDir} && mv -f {$escapedTmp} {$escapedPath}";
        }

        $sshMoveCmd = $this->sshPrefix() . ' ' . escapeshellarg($moveCmd);
        exec($sshMoveCmd . ' 2>&1', $output, $exitCode);

        return ['output' => implode("\n", $output), 'exitCode' => $exitCode];
    }

    public function deleteRemoteFile(string $remotePath): array
    {
        return $this->sshCommand("rm -f " . escapeshellarg($remotePath));
    }

    public function getLyricsBySongId(string $id): ?string
    {
        $salt = bin2hex(random_bytes(6));
        $token = md5($this->adminPassword . $salt);
        $publicUrl = rtrim(config('navidrome.public_url'), '/');

        try {
            $response = Http::timeout(10)->get("{$publicUrl}/rest/getLyricsBySongId.view", [
                'u' => $this->adminUser, 't' => $token, 's' => $salt,
                'v' => '1.16.1', 'c' => 'MonFlowAdmin', 'f' => 'json', 'id' => $id,
            ]);
            $data = $response->json('subsonic-response');
            if (($data['status'] ?? '') !== 'ok') return null;
            $lyrics = $data['lyricsList']['structuredLyrics'] ?? [];
            if (empty($lyrics)) return null;
            $synced = collect($lyrics)->firstWhere('synced', true) ?? $lyrics[0];
            $lines = $synced['line'] ?? [];
            if (empty($lines)) return null;
            $lrc = '';
            foreach ($lines as $line) {
                if (isset($line['start'])) {
                    $ms = (int) $line['start'];
                    $min = str_pad(intdiv($ms, 60000), 2, '0', STR_PAD_LEFT);
                    $sec = str_pad(intdiv($ms % 60000, 1000), 2, '0', STR_PAD_LEFT);
                    $cs = str_pad(intdiv($ms % 1000, 10), 2, '0', STR_PAD_LEFT);
                    $lrc .= "[{$min}:{$sec}.{$cs}]" . ($line['value'] ?? '') . "\n";
                } else {
                    $lrc .= ($line['value'] ?? '') . "\n";
                }
            }
            return $lrc;
        } catch (\Exception $e) {
            Log::warning("Failed to get lyrics for song {$id}: {$e->getMessage()}");
            return null;
        }
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

    // ─── Subsonic Playlist API (user credentials) ───

    private function subsonicAsUser(string $username, string $password, string $endpoint, array $extraParams = []): array
    {
        $salt = bin2hex(random_bytes(6));
        $qs = http_build_query(array_merge([
            'u' => $username, 't' => md5($password . $salt), 's' => $salt,
            'v' => '1.16.1', 'c' => 'MonFlow', 'f' => 'json',
        ], $extraParams));
        $url = rtrim(config('navidrome.public_url'), '/') . "/rest/{$endpoint}?{$qs}";
        $response = retry(3, fn() => Http::timeout(10)->get($url),
            fn(int $a) => $a * 1000, fn($e) => $e instanceof \Illuminate\Http\Client\ConnectionException);
        $response->throw();
        $data = $response->json('subsonic-response');
        if (($data['status'] ?? '') !== 'ok') {
            throw new \RuntimeException($data['error']['message'] ?? 'Subsonic API error');
        }
        return $data;
    }

    private function subsonicUrlAsUser(string $username, string $password, string $endpoint, array $baseParams, array $repeatedParams = []): string
    {
        $salt = bin2hex(random_bytes(6));
        $qs = http_build_query(array_merge([
            'u' => $username, 't' => md5($password . $salt), 's' => $salt,
            'v' => '1.16.1', 'c' => 'MonFlow', 'f' => 'json',
        ], $baseParams));
        foreach ($repeatedParams as [$key, $value]) {
            $qs .= '&' . urlencode($key) . '=' . urlencode($value);
        }
        return rtrim(config('navidrome.public_url'), '/') . "/rest/{$endpoint}?{$qs}";
    }

    public function getPlaylists(string $username, string $password): array
    {
        $data = $this->subsonicAsUser($username, $password, 'getPlaylists.view');
        $raw = $data['playlists']['playlist'] ?? [];
        // Navidrome returns a single object instead of array when there's only one playlist
        return isset($raw['id']) ? [$raw] : array_values($raw);
    }

    public function getPlaylist(string $username, string $password, string $id): array
    {
        $data = $this->subsonicAsUser($username, $password, 'getPlaylist.view', ['id' => $id]);
        $pl = $data['playlist'] ?? [];
        // Normalise: entry can be a single object when there's only one song
        if (isset($pl['entry']) && isset($pl['entry']['id'])) {
            $pl['entry'] = [$pl['entry']];
        }
        $pl['entry'] = $pl['entry'] ?? [];
        return $pl;
    }

    public function createPlaylist(string $username, string $password, string $name): array
    {
        $data = $this->subsonicAsUser($username, $password, 'createPlaylist.view', ['name' => $name]);
        return $data['playlist'] ?? [];
    }

    public function renamePlaylist(string $username, string $password, string $id, string $name): void
    {
        $this->subsonicAsUser($username, $password, 'updatePlaylist.view', ['playlistId' => $id, 'name' => $name]);
    }

    public function addSongsToPlaylist(string $username, string $password, string $playlistId, array $songIds): void
    {
        if (empty($songIds)) return;
        $repeated = array_map(fn($id) => ['songIdToAdd', $id], $songIds);
        $url = $this->subsonicUrlAsUser($username, $password, 'updatePlaylist.view', ['playlistId' => $playlistId], $repeated);
        $response = retry(3, fn() => Http::timeout(10)->get($url),
            fn(int $a) => $a * 1000, fn($e) => $e instanceof \Illuminate\Http\Client\ConnectionException);
        $response->throw();
    }

    public function removeSongsFromPlaylist(string $username, string $password, string $playlistId, array $indices): void
    {
        if (empty($indices)) return;
        $repeated = array_map(fn($i) => ['songIndexToRemove', (string)(int)$i], $indices);
        $url = $this->subsonicUrlAsUser($username, $password, 'updatePlaylist.view', ['playlistId' => $playlistId], $repeated);
        $response = retry(3, fn() => Http::timeout(10)->get($url),
            fn(int $a) => $a * 1000, fn($e) => $e instanceof \Illuminate\Http\Client\ConnectionException);
        $response->throw();
    }

    public function deletePlaylist(string $username, string $password, string $id): void
    {
        $this->subsonicAsUser($username, $password, 'deletePlaylist.view', ['id' => $id]);
    }

    public function searchSongsSubsonic(string $username, string $password, string $query, int $limit = 20): array
    {
        $data = $this->subsonicAsUser($username, $password, 'search3.view', [
            'query' => $query, 'songCount' => $limit, 'albumCount' => 0, 'artistCount' => 0,
        ]);
        return $data['searchResult3']['song'] ?? [];
    }
}
