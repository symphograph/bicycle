<?php

namespace Symphograph\Bicycle\Env;

use stdClass;
use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\Errors\AppErr;

readonly class Env
{
    const string envPath = '/includes/env.php';

    private array  $debugIPs;
    private array  $serverIPs;
    private string $serverName;
    private bool   $debugMode;
    private int    $adminAccountId;
    private string $frontendDomain;
    private array  $telegram;
    private object $mailruSecrets;
    private object $vkSecrets;
    private object $yandexSecrets;
    private object $discordSecrets;
    private array  $debugOnlyFolders;
    private string $apiKey;
    private string $tokenSecret;
    private object $jwt;
    private array  $clientDomains;
    private array  $clientGroups;
    private array  $apiDomains;
    private int    $timeZone;
    private string $recipientEmail;
    private array  $services;
    private array  $clients;
    private bool   $isTest;
    private array $powerManagers;
    private array $curlLocations;

    public function __construct()
    {
        self::initEnv();
    }

    private function initEnv(): void
    {
        /** @noinspection PhpUndefinedFunctionInspection */
        $env = require getRoot() . self::envPath;
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

    public static function getDebugIps(): array
    {
        $Env = self::getMyEnv();
        return $Env->debugIPs;
    }

    public static function isDebugIp(): bool
    {
        $Env = self::getMyEnv();
        return in_array(ServerEnv::REMOTE_ADDR(), $Env->debugIPs);
    }

    public static function isServerIp(): bool
    {
        $Env = self::getMyEnv();
        return in_array(ServerEnv::REMOTE_ADDR(), $Env->serverIPs);
    }

    public static function isDebugMode(): bool
    {
        if (PHP_SAPI === 'cli') {
            return true;
        }
        $Env = self::getMyEnv();
        return $Env->debugMode && in_array(ServerEnv::REMOTE_ADDR(), $Env->debugIPs);
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
        $tg = $Env->telegram[ServerEnv::SERVER_NAME()];
        return new TelegramSecrets(
            $tg->token,
            $tg->bot_name,
            $tg->callback,
            $tg->loginPageTitle ?? 'Вход'
        );
    }

    public static function getVKSecrets(): VKSecrets
    {
        $Env = self::getMyEnv();
        return new VKSecrets(
            $Env->vkSecrets->appId,
            $Env->vkSecrets->privateKey,
            $Env->vkSecrets->serviceKey,
            $Env->vkSecrets->callback,
            $Env->vkSecrets->loginPageTitle ?? 'Вход',
            $Env->vkSecrets->codeRedirect ?? '',
            $Env->vkSecrets->longToken ?? ''
        );
    }

    public static function getYandexSecrets(): YandexSecrets
    {
        $Env = self::getMyEnv();
        return new YandexSecrets(
            $Env->yandexSecrets->clientId ?? '',
            $Env->yandexSecrets->clientSecret ?? '',
            $Env->yandexSecrets->callback ?? '',
            $Env->vkSecrets->loginPageTitle ?? 'Вход',
            $Env->yandexSecrets->suggestKey ?? ''
        );
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

    public static function getJWT(): object
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

    public static function getClientGroups(): array
    {
        $Env = self::getMyEnv();
        return $Env->clientGroups;
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

    public static function getTimeZone(): int
    {
        $Env = self::getMyEnv();
        return $Env->timeZone ?? 0;
    }

    public static function getRecipientEmail(): string
    {
        $Env = self::getMyEnv();
        return $Env->recipientEmail ?? 'email@example.com';
    }

    public static function getServerName(): string
    {
        $Env = self::getMyEnv();
        return $Env->serverName;
    }

    /**
     * @return stdClass[]
     */
    public static function getServices(): array
    {
        $Env = self::getMyEnv();
        return $Env->services;
    }

    /**
     * @return stdClass[]
     */
    public static function getClients(): array
    {
        $Env = self::getMyEnv();
        return $Env->clients;
    }

    public static function getPowerServiceName(): string
    {
        $Env = self::getMyEnv();
        if(empty($Env->powerManagers)) {
            throw new AppErr('powerManagers not set');
        }
        return $Env->powerManagers[ServerEnv::SERVER_NAME()];
    }

    public static function isTest(): bool
    {
        $Env = self::getMyEnv();
        return $Env->isTest;
    }

}