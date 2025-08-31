<?php

namespace Symphograph\Bicycle\Auth\Account\Profile\Email;

use Symphograph\Bicycle\PDO\DB;

class EmailCodeRepoDB
{
    public static function getLastActiveCode(string $email, string $fingerPrint): ?EmailCode
    {
        $mins = EmailCode::minsBeforeExpired;
        $sql = "SELECT * 
            FROM emailCodes 
            WHERE 
                email = :email 
                AND fingerPrint = :fingerPrint
                AND createdAt > NOW() - INTERVAL $mins MINUTE 
            ORDER BY createdAt DESC 
            LIMIT 1";

        $params = ['email' => $email, 'fingerPrint' => $fingerPrint];

        $result = DB::qwe($sql, $params);
        return $result->fetchObject(EmailCode::class) ?: null;
    }

    public static function incrementTryCount(EmailCode $emailCode): void
    {
        $sql = "UPDATE emailCodes 
            SET 
                tryCount = tryCount + 1,
                lastTryAt = NOW()
            WHERE 
                email = :email 
                AND createdAt = :createdAt"; // Уникальность по email + времени

        $params = [
            'email' => $emailCode->email,
            'createdAt' => $emailCode->createdAt
        ];

        DB::qwe($sql, $params);
    }

    public static function insert(EmailCode $emailCode): void
    {
        $sql = "INSERT INTO emailCodes 
            (email, hash, createdAt, fingerPrint) 
            VALUES 
            (:email, :hash, :createdAt, :fingerPrint)";

        $params = [
            'email' => $emailCode->email,
            'hash' => $emailCode->hash,
            'createdAt' => $emailCode->createdAt,
            'fingerPrint' => $emailCode->fingerPrint
        ];
        DB::qwe($sql, $params);
    }

    public static function deleteExpired(): void
    {
        $mins = EmailCode::minsBeforeExpired;
        $sql = "DELETE FROM emailCodes where createdAt < NOW() - INTERVAL $mins MINUTE";
        DB::qwe($sql);
    }

    public static function delByEmail(string $email): void
    {
        $sql = "DELETE FROM emailCodes WHERE email = :email";
        $params = ['email' => $email];
        DB::qwe($sql, $params);
    }

}