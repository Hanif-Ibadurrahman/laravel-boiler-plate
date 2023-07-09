<?php

return [
    'key' => [
        'rsa' => [
            'public' => base64_decode(env('JWT_RSA_BASE64_PUBLIC_KEY', '')),
            'private' => base64_decode(env('JWT_RSA_BASE64_PRIVATE_KEY', '')),
        ],
    ],
];
