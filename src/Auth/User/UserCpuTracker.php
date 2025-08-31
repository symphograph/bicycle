<?php

namespace Symphograph\Bicycle\Auth\User;

use Symphograph\Bicycle\Helpers\IntSafety;
use Symphograph\Bicycle\Logs\ErrorLog;
use Symphograph\Bicycle\PDO\DB;
use Throwable;

class UserCpuTracker {

    /** @var int[] */
    private static array $startCpu;
    private static User  $user;

    const int microSecMul = 1000000;

    /**
     * Singleton
     */
    public static function init(): void
    {
        if(isset(self::$user)) return;
        try {
            $user = User::byAccessToken();
        } catch (Throwable $err) {
            return;
        }

        self::$user = $user;
        self::$startCpu = getrusage();
    }

    public static function handleShutdown(): void
    {
        if (empty(self::$user) || empty(self::$startCpu)) return;

        $endCpu = getrusage();
        if($endCpu === false) return;

        // Вычисление в микросекундах
        $cpu = (
                ($endCpu['ru_utime.tv_sec'] - self::$startCpu['ru_utime.tv_sec']) * self::microSecMul
            ) + (
                $endCpu['ru_utime.tv_usec'] - self::$startCpu['ru_utime.tv_usec']
            );

        self::updateCpuUsage($cpu);
    }

    private static function updateCpuUsage(int $cpu): void {
        $userId = self::$user->id;

        if(!IntSafety::isSafeAdd($cpu, self::$user->cpu)){
            self::handleOverflow($userId);
            return;
        }

        $sql = "UPDATE users SET cpu = (cpu + :cpu) WHERE id = :userId";
        $params = ["userId" => $userId, "cpu" => $cpu];
        DB::qwe($sql, $params);
    }

    private static function handleOverflow($userId): void
    {
        // 1. Логируем инцидент
        ErrorLog::writeMsg("CPU overflow for user $userId");

        /*
        // 2. Сбрасываем счетчик
        $sql = "update users set cpu = 0 where id = :userId";
        $params = ["userId" => $userId];
        DB::qwe($sql, $params);
        */
    }
}