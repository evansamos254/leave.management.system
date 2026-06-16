<?php

class AuthMiddleware
{
    public static function handle(): void
    {
        require_auth();
    }
}

