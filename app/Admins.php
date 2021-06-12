<?php

namespace App;

class Admins
{
    static function by_accesstoken($token): ?array
    {
        $admin = config()['admins']['by_accesstoken'] ?? [];
        return $admin[$token] ?? null;
    }
}
