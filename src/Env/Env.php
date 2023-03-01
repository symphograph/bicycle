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


    public function __construct()
    {
        self::initEnv();
    }

    private function initEnv(): void
    {
        $env = require dirname($_SERVER['DOCUMENT_ROOT']) . '/includes/env.php';
        $vars = (object)get_class_vars(self::class);
        foreach ($vars as $k => $v){
            if(!isset($env->$k)) continue;
            $this->$k = $env->$k;
        }
    }

    protected static function getMyEnv(): self
    {
        global $Env;
        if(!isset($Env)){
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

    public static function getTelegramSecrets(): TelegramSecrets
    {
        $Env = self::getMyEnv();
        return new TelegramSecrets($Env->telegram->token, $Env->telegram->bot_name);
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

}