<?php

namespace Symphograph\Bicycle\Worker;


use Symphograph\Bicycle\Logs\ErrorLog;
use Symphograph\Bicycle\Logs\Log;
use Symphograph\Bicycle\Repo\Redis\RedisDB;
use Throwable;

abstract class AbstractWorker
{
    const string wakeKey = 'hgh';
    protected int $minCooldown; // sec
    protected int $maxCooldown; // sec
    protected int $emptyCycles = 0;

    public function __construct(int $minCooldown = 1, int $maxCooldown = 60)
    {
        $this->minCooldown = $minCooldown;
        $this->maxCooldown = $maxCooldown;
    }

    /**
     * Получить список задач для обработки
     * @param int $limit
     * @return object|null Объект списка задач или null, если задач нет
     */
    abstract protected function fetchTasks(): ?object;

    /**
     * Проверить, пуст ли список задач
     * @param object $List
     * @return bool
     */
    protected function isListEmpty(object $List): bool
    {
        return empty($List->getList());
    }

    /**
     * Обработать список задач
     * @param object $List
     * @return void
     */
    abstract protected function processTasks(object $List): void;

    abstract function logFolder(): string;
    /**
     * Логирование сообщений
     * @param string $msg
     * @return void
     */
    protected function log(string $msg): void
    {
        Log::msg($msg, [], $this->logFolder());
    }

    /**
     * Запуск бесконечного цикла воркера
     * @return void
     */
    public function run(): void
    {
        $this->log('worker started');

        while (true) {
            $List = $this->fetchTasks();

            if ($List === null || $this->isListEmpty($List)) {
                $this->emptyCycles++;
                $pause = min($this->minCooldown * pow(2, $this->emptyCycles), $this->maxCooldown);

                // Блокируемся на Redis – ждём сигнал или истечение времени
                $result = RedisDB::blPop(static::wakeKey, $pause);

                if ($result) {
                    $this->log('Woken up by Redis signal');
                    $this->emptyCycles = 0;   // сбрасываем счётчик, чтобы следующая пауза была минимальной
                }

                continue;
            }

            $this->emptyCycles = 0;
            $this->log('start processing part');

            try {
                $this->processTasks($List);
            } catch (Throwable $err) {
                ErrorLog::writeToLog($err);
                continue;
            }

            $this->log('end processing part');
        }
    }

    /**
     * Отправить сигнал пробуждения для указанного ключа
     * @return void
     */
    public static function kickWakeup(): void
    {
        RedisDB::lPush(static::wakeKey, 1);
    }
}