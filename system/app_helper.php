<?php

use System\Core\App;
use System\Libraries\Db;

function html_title(string $title = null)
{
    static $html_title = '';

    if (is_string($title)) {
        $html_title = strip_tags($title);
    } else {
        return $html_title ?: app_config('app_name');
    }
}

function base_url(): string
{
    return App::request()::base();
}

function default_db(): ?Db
{
    return App::getInstance()->getLoader()->getDefaultDb();
}

function log_debug(string $message): void
{
    if (!app_config('debug')) return;
    App::logger()->debug($message);
}

function log_error(string $message): void
{
    App::logger()->error($message);
}
