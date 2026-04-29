<?php

return [
    'url' => env('NAVIDROME_URL', 'http://localhost:4533'),
    'public_url' => env('NAVIDROME_PUBLIC_URL', env('NAVIDROME_URL', 'http://localhost:4533')),
    'admin_user' => env('NAVIDROME_ADMIN_USER', 'admin'),
    'admin_password' => env('NAVIDROME_ADMIN_PASSWORD', 'admin'),
    'music_path' => env('NAVIDROME_MUSIC_PATH', '/music'),
    'ssh_host' => env('NAVIDROME_SSH_HOST'),
    'ssh_user' => env('NAVIDROME_SSH_USER', 'root'),
    'ssh_password' => env('NAVIDROME_SSH_PASSWORD'),
    'ssh_key' => env('NAVIDROME_SSH_KEY'),
    'ssh_sudo' => env('NAVIDROME_SSH_SUDO', false),
];
