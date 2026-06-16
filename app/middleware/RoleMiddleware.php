<?php

class RoleMiddleware
{
    public static function handle(array|string $roles): void
    {
        require_role($roles);
    }
}

