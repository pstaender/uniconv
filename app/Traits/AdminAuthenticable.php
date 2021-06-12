<?php

namespace App\Traits;

trait AdminAuthenticable
{
  protected function authorize(): bool
  {
    $token = $this->headers['Authorization'] ?? null;
    if ($token) {
      $token = explode('Bearer ', $token)[1] ?? null;
    }
    if (!empty($token)) {
      if (\App\config('admin_by_accesstoken') ?? null) {
        $user = call_user_func(\App\config('admin_by_accesstoken'), $token);
      } else {
        $user = \App\Admins::by_accesstoken($token);
      }
      if (!empty($user)) {
        $this->user = $user;
        return true;
      }
    }
    throw new \App\UnauthorizedException();
  }
}
