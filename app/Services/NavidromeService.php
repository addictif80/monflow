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

    private function requestPaginated(string $endpoint): array
    {
        if (!$this->token) {
            $this->authenticate();
        }

        $doRequest = fn () => Http::timeout(30)
            ->withHeaders(['x-nd-authorization' => "Bearer {$this->token}", 'Cache-Control' => 'no-cache'])
            ->get("{$this->baseUrl}/api{$endpoint}");

        $response = retry(3, $doRequest, fn ($a) => $a * 1000, fn ($e) => $e instanceof \Illuminate\Http\Client\ConnectionException);

        if ($response->status() === 401) {
            $this->authenticate();
            $response = retry(3, $doRequest, fn ($a) => $a * 1000, fn ($e) => $e instanceof \Illuminate\Http\Client\ConnectionException);
        }

        // Back off and retry once on rate-limit
        if ($response->status() === 429) {
            $retryAfter = (int) ($response->header('Retry-After') ?? 2);
            sleep(max(1, $retryAfter));
            $response = retry(3, $doRequest, fn ($a) => $a * 2000, fn ($e) => $e instanceof \Illuminate\Http\Client\ConnectionException);
        }

        $response->throw();
        return [
            'data'  => $response->json() ?? [],
            'total' => (int) ($response->header('X-Total-Count') ?? 0),
        ];
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

    public function getRecentAlbums(int $limit = 10, ?\DateTimeInterface $since = null): array
    {
        // Navidrome's REST API does not support date range filters, so we fetch
        // a larger set sorted by createdAt and filter in PHP.
        $fetch = $since ? 500 : $limit;
        $albums = $this->request('get', "/album?_end={$fetch}&_order=DESC&_sort=createdAt&_start=0");
        if ($since) {
            $sinceTs = $since->getTimestamp();
            $albums = array_values(array_filter($albums, function ($a) use ($sinceTs) {
                $created = strtotime($a['createdAt'] ?? '');
                return $created && $created >= $sinceTs;
            }));
        }
        return array_slice($albums, 0, $limit);
    }

    public function getTopPlayedArtists(int $limit = 5): array
    {
        // The /api/artist endpoint does not expose a sortable playCount field.
        // Aggregate from top songs instead (songs do sort by playCount correctly).
        $songs = $this->request('get', "/song?_end=300&_order=DESC&_sort=playCount&_start=0");
        $artists = [];
        foreach ($songs as $song) {
            $pc = (int)($song['playCount'] ?? 0);
            if ($pc === 0) break;
            $name = $song['artist'] ?? '';
            if ($name === '') continue;
            if (!isset($artists[$name])) {
                $artists[$name] = ['name' => $name, 'playCount' => 0, 'albumCount' => 0, '_albums' => []];
            }
            $artists[$name]['playCount'] += $pc;
            $albumId = $song['albumId'] ?? null;
            if ($albumId && !in_array($albumId, $artists[$name]['_albums'])) {
                $artists[$name]['_albums'][] = $albumId;
                $artists[$name]['albumCount']++;
            }
        }
        usort($artists, fn($a, $b) => $b['playCount'] - $a['playCount']);
        return array_map(
            fn($a) => ['name' => $a['name'], 'playCount' => $a['playCount'], 'albumCount' => $a['albumCount']],
            array_slice(array_values($artists), 0, $limit)
        );
    }

    public function getTopPlayedSongs(int $limit = 10): array
    {
        return $this->request('get', "/song?_end={$limit}&_order=DESC&_sort=playCount&_start=0");
    }

    public function getCoverArt(string $id, int $size = 300): \Illuminate\Http\Client\Response
    {
        $salt  = bin2hex(random_bytes(6));
        $token = md5($this->adminPassword . $salt);
        $url   = rtrim(config('navidrome.public_url'), '/') . '/rest/getCoverArt.view?' . http_build_query([
            'u' => $this->adminUser, 't' => $token, 's' => $salt,
            'v' => '1.16.1', 'c' => 'MonFlowAdmin', 'f' => 'json',
            'id' => $id, 'size' => $size,
        ]);
        return Http::timeout(10)->get($url);
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

    public function getAllSongsPaginated(int $start, int $perPage, string $sort = 'title', string $order = 'ASC', string $search = ''): array
    {
        $query = "_start={$start}&_end=" . ($start + $perPage) . "&_order={$order}&_sort={$sort}";
        if ($search !== '') {
            $query .= '&title_like=' . urlencode($search);
        }
        return $this->requestPaginated("/song?{$query}");
    }

    public function triggerScan(bool $full = false): void
    {
        // Use internal REST API — Subsonic startScan.view is incremental only and
        // does not remove deleted files from the database.
        try {
            $this->request('post', '/scanner', ['fullScan' => $full]);
        } catch (\Throwable) {
            // Fallback: Subsonic API
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
    }

    public function getScanStatus(): array
    {
        try {
            $data = $this->request('get', '/scanner');
            return [
                'scanning'  => (bool)($data['scanning'] ?? false),
                'count'     => (int)($data['count'] ?? 0),
                'folderCount' => (int)($data['folderCount'] ?? 0),
                'lastScan'  => $data['lastScan'] ?? null,
            ];
        } catch (\Throwable) {
            return ['scanning' => false, 'count' => 0, 'folderCount' => 0, 'lastScan' => null];
        }
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
        // Preserve PATH so sudo can find docker/sqlite3 not in the restricted sudo PATH
        return 'echo ' . escapeshellarg($sudoPass) . ' | sudo -S env PATH=/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin';
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

    public function batchCheckLrc(array $hostPaths): array
    {
        if (empty($hostPaths)) return [];
        $parts = implode(' ', array_map('escapeshellarg', $hostPaths));
        $inner = "for f in {$parts}; do [ -f \"\$f\" ] && echo \"\$f\"; done";
        $result = $this->sshCommand('sh -c ' . escapeshellarg($inner));
        if (empty(trim($result['output']))) return [];
        return array_values(array_filter(array_map('trim', explode("\n", $result['output']))));
    }

    public function writeLrcViaSSH(string $hostPath, string $content): array
    {
        $b64     = base64_encode($content);
        $escaped = escapeshellarg($hostPath);
        $dir     = escapeshellarg(dirname($hostPath));
        $inner   = "mkdir -p {$dir} && printf '%s' " . escapeshellarg($b64) . " | base64 -d > {$escaped}";
        return $this->sshCommand('sh -c ' . escapeshellarg($inner));
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

    private function userJwt(string $username, string $password): string
    {
        $resp = Http::timeout(10)->post("{$this->baseUrl}/auth/login", [
            'username' => $username,
            'password' => $password,
        ]);
        $resp->throw();
        $token = $resp->json('token');
        if (!$token) throw new \RuntimeException('Authentification Navidrome échouée.');
        return $token;
    }

    public function getUserTopSongs(string $username, string $password, int $limit = 3): array
    {
        try {
            $jwt = $this->userJwt($username, $password);
            $resp = Http::timeout(15)
                ->withHeaders(['x-nd-authorization' => "Bearer {$jwt}"])
                ->get("{$this->baseUrl}/api/song", [
                    '_sort' => 'playCount', '_order' => 'DESC',
                    '_start' => 0, '_end' => $limit,
                ]);
            return $resp->ok() ? ($resp->json() ?? []) : [];
        } catch (\Exception) {
            return [];
        }
    }

    public function getUserListeningStats(string $username, string $password): array
    {
        try {
            $jwt = $this->userJwt($username, $password);
            $resp = Http::timeout(30)
                ->withHeaders(['x-nd-authorization' => "Bearer {$jwt}"])
                ->get("{$this->baseUrl}/api/song", [
                    '_sort' => 'playCount', '_order' => 'DESC',
                    '_start' => 0, '_end' => 2000,
                ]);
            if (!$resp->ok()) return ['totalSeconds' => 0];
            $songs = $resp->json() ?? [];
            $totalSeconds = 0;
            foreach ($songs as $s) {
                $pc = (int)($s['playCount'] ?? 0);
                if ($pc === 0) break;
                $totalSeconds += $pc * (int)($s['duration'] ?? 0);
            }
            return ['totalSeconds' => $totalSeconds];
        } catch (\Exception) {
            return ['totalSeconds' => 0];
        }
    }

    public function copyPlaylistToUser(
        string $fromUser, string $fromPass,
        string $toUser, string $toPass,
        string $playlistId, string $playlistName
    ): void {
        $source = $this->getPlaylist($fromUser, $fromPass, $playlistId);
        $songIds = array_column($source['entry'] ?? [], 'id');
        $new = $this->createPlaylist($toUser, $toPass, $playlistName);
        $newId = $new['id'] ?? null;
        if ($newId && !empty($songIds)) {
            $this->addSongsToPlaylist($toUser, $toPass, $newId, $songIds);
        }
    }
}
