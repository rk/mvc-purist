<?php

return array(
    // If you have the ability to set a custom php.ini file for this project,
    // do so and configure your session variables there. If you do, delete the
    // "session" section. Otherwise, configure your session parameters here.
    'session' => array(
        // The cookie name that contains the session ID
        'name' => 'purist-session',
        // How many seconds should a session expire after?
        'gc_maxlifetime' => 1440,
        // Provides some protection versus XSS attacks stealing the session id
        'http_only' => true,
        // Set the session hash function to an intensive 512-bit hash like whirlpool or sha512
        'hash_function' => 'whirlpool',
        // Roughly base64 encoding of the session id
        'hash_bits' => '6',
    ),
);