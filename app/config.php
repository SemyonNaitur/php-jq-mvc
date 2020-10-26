<?php

define('DEBUG', true);

$app_config['app_name'] = 'blog';
$app_config['log_dir'] = ROOT_DIR . '/log';
$app_config['log_format'] = '[Y-m-d H:i:s] {level}: {message}';

$app_config['db'] = [
    'default' => [
        'host' => 'localhost',
        'dbname' => 'intn_blog',
        'user' => 'root',
        'pass' => ''
    ],
];


$app_config['routes'] = [
    ['path' => '/print-request', 'method' => 'test/RouterTest::printRequest'],
    ['path' => 'cat-id-prop/:cat/:id/:prop', 'method' => 'test/RouterTest::catIdProp'],
    ['path' => 'cat-id-prop/:cat/:id/...', 'method' => 'test/RouterTest::catIdProps'],
    ['path' => 'cat-id-props/:cat/:id/...', 'method' => 'test/RouterTest::catIdProps'],
    ['regex' => '/regex\/route/', 'method' => 'test/RouterTest::regexRoute'],
    ['callback' => 'route_callback_test', 'method' => 'test/RouterTest::callbackRoute'],
    ['path' => 'not-found', 'method' => 'test/RouterTest::notFound'],
];


function route_callback_test($url)
{
    return ($url === 'callback-route') ? ['works' => true] : false;
}
