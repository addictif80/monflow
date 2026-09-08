<?php

return [
    'url' => env('NAVIDROME_URL', 'http://localhost:4533'),
    'public_url' => env('NAVIDROME_PUBLIC_URL', env('NAVIDROME_URL', 'http://localhost:4533')),
    'admin_user' => env('NAVIDROME_ADMIN_USER', 'admin'),
    'admin_password' => env('NAVIDROME_ADMIN_PASSWORD', 'admin'),
    'music_path' => env('NAVIDROME_MUSIC_PATH', '/music'),
    'docker_container' => env('NAVIDROME_DOCKER_CONTAINER'),
    'db_path' => env('NAVIDROME_DB_PATH', '/data/navidrome.db'),
    // Host path to the music directory (where files physically live on the SSH host).
    // Required when Navidrome runs in Docker: container paths like /music/... don't
    // exist on the host. Set this to the host-side mount point (e.g. /opt/music).
    // The container-side prefix (default /music) is stripped and replaced with this.
    'music_host_path' => env('NAVIDROME_MUSIC_HOST_PATH'),
    // Container-internal music directory prefix, used only when music_host_path is set.
    'container_music_path' => env('NAVIDROME_CONTAINER_MUSIC_PATH', '/music'),
    'ssh_host' => env('NAVIDROME_SSH_HOST'),
    'ssh_user' => env('NAVIDROME_SSH_USER', 'root'),
    'ssh_password' => env('NAVIDROME_SSH_PASSWORD'),
    'ssh_key' => env('NAVIDROME_SSH_KEY'),
    'ssh_sudo' => env('NAVIDROME_SSH_SUDO', false),
    'sudo_password' => env('NAVIDROME_SUDO_PASSWORD'),
];
