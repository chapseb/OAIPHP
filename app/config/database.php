<?php

$config['database'] = array(
    'default'       => 'pgsql',

    'connections'   => array(
        'pgsql'     => array(
            'driver'    => 'pgsql',
            'host'      => isset($_SERVER['DB1_HOST']) ? $_SERVER['DB1_HOST'] : 'localhost',
            'database'  => isset($_SERVER['DB1_NAME']) ? $_SERVER['DB1_NAME'] : 'oaiphp',
            'username'  => isset($_SERVER['DB1_USER']) ? $_SERVER['DB1_USER'] : 'anaphore',
            'password'  => isset($_SERVER['DB1_PASS']) ? $_SERVER['DB1_PASS'] : 'anaphore',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => ''
        )
    )
);