<?php

namespace Symphograph\Bicycle\Env;

readonly class Env
{
    private array  $debugIPs;
    private bool   $debugMode;
    private int    $adminAccountId;
    private string $frontendDomain;
    private object $telegram;
    private object $mailruSecrets;
    private object $discordSecrets;
    private array  $debugOnlyFolders;
    private string $apiKey;
    private string $tokenSecret;
    private object $jwt;
    private array  $clientDomains;
    private array  $apiDomains;
    private int    $timeZone;

    public function __construct()
    {
        self::initEnv();
    }

    private function initEnv(): void
    {
        $env = require dirname($_SERVER['DOCUMENT_ROOT']) . '/includes/env.php';
        $vars = (object)get_class_vars(self::class);
        foreach ($vars as $k => $v) {
            if (!isset($env->$k)) continue;
            $this->$k = $env->$k;
        }
    }

    protected static function getMyEnv(): self
    {
        global $Env;
        if (!isset($Env)) {
            $Env = new self();
        }
        return $Env;
    }

    public static function isDebugIp(): bool
    {
        $Env = self::getMyEnv();
        return in_array($_SERVER['REMOTE_ADDR'], $Env->debugIPs);
    }

    public static function isDebugMode(): bool
    {
        $Env = self::getMyEnv();
        return $Env->debugMode && in_array($_SERVER['REMOTE_ADDR'], $Env->debugIPs);
    }

    public static function getAdminAccountId(): int
    {
        $Env = self::getMyEnv();
        return $Env->adminAccountId;
    }

    public static function getFrontendDomain(): string
    {
        $Env = self::getMyEnv();
        return $Env->frontendDomain;
    }

    public static function getApiKey(): string
    {
        $Env = self::getMyEnv();
        return $Env->apiKey;
    }

    public static function getTokenSecret(): string
    {
        $Env = self::getMyEnv();
        return $Env->tokenSecret;
    }

    public static function getTelegramSecrets(): TelegramSecrets
    {
        $Env = self::getMyEnv();
        return new TelegramSecrets($Env->telegram->token, $Env->telegram->bot_name, $Env->telegram->loginPageTitle ?? 'Вход');
    }

    public static function getMailruSecrets(): MailruSecrets
    {
        $Env = self::getMyEnv();
        return new MailruSecrets($Env->mailruSecrets->app_id, $Env->mailruSecrets->app_secret);
    }

    public static function getDiscordSecrets(): DiscordSecrets
    {
        $Env = self::getMyEnv();
        return new DiscordSecrets($Env->discordSecrets->clientId, $Env->discordSecrets->clientSecret);
    }

    public static function getDebugOnlyFolders(): array
    {
        $Env = self::getMyEnv();
        return $Env->debugOnlyFolders;
    }

    public static function getJWT()
    {
        $Env = self::getMyEnv();
        return $Env->jwt;
    }

    public static function getClientDomains(?string $protocol = null): array
    {
        $Env = self::getMyEnv();
        if (empty($protocol)) {
            return $Env->clientDomains;
        }
        return array_map(
            fn($var) => $protocol . $var,
            $Env->clientDomains
        );
    }

    public static function getAPIDomains(?string $protocol = null): array
    {
        $Env = self::getMyEnv();
        if (empty($protocol)) {
            return $Env->apiDomains;
        }

        return array_map(
            fn($var) => $protocol . $var,
            $Env->apiDomains
        );
    }

    public static function getTimeZone(): string
    {
        $Env = self::getMyEnv();
        return $Env->timeZone;
    }

}