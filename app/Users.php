<?php

namespace App;

class Users
{
    static function by_accesstoken($token): ?array
    {
        $users = config()['users']['by_accesstoken'] ?? [];
        return $users[$token] ?? null;
    }
}
