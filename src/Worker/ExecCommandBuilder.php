<?php

namespace Symphograph\Bicycle\Worker;

/**
 * Класс для построения и управления командами для выполнения через exec в PHP.
 */
class ExecCommandBuilder
{
    /**
     * Путь к PHP-скрипту, который будет выполнен.
     *
     * @var string
     */
    private string $scriptPath;

    /**
     * Массив аргументов, которые будут переданы скрипту.
     *
     * @var array
     */
    private array $arguments = [];

    /**
     * Путь для перенаправления вывода скрипта (например, файл или /dev/null).
     * Если null, вывод не перенаправляется.
     *
     * @var string|null
     */
    private ?string $outputRedirection = null;

    /**
     * Указывает, следует ли запускать скрипт в фоновом режиме.
     *
     * @var bool
     */
    private bool $runInBackground = false;

    /**
     * Конструктор класса.
     *
     * @param string $scriptPath Путь к скрипту для выполнения.
     */
    public function __construct(string $scriptPath)
    {
        $this->scriptPath = $scriptPath;
    }

    /**
     * Добавляет аргумент к команде.
     *
     * @param string | array | object $argument Аргумент для передачи в скрипт.
     * @return self Возвращает текущий объект для цепочного вызова.
     */
    public function addArgument(string | array | object $argument): self
    {
        if (is_array($argument) || is_object($argument)) {
            $argument = urlencode(serialize($argument));
        }
        $this->arguments[] = escapeshellarg($argument);
        return $this;
    }

    /**
     * Устанавливает путь перенаправления вывода.
     *
     * @param string $path Путь для перенаправления вывода.
     * @return self Возвращает текущий объект для цепочного вызова.
     */
    public function setOutputRedirection(string $path): self
    {
        $this->outputRedirection = $path;
        return $this;
    }

    /**
     * Устанавливает, следует ли запускать команду в фоновом режиме.
     *
     * @param bool $runInBackground True, если команда должна быть запущена в фоновом режиме.
     * @return self Возвращает текущий объект для цепочного вызова.
     */
    public function runInBackground(bool $runInBackground = true): self
    {
        $this->runInBackground = $runInBackground;
        return $this;
    }

    /**
     * Строит и возвращает команду для выполнения через exec.
     *
     * @return string Сформированная команда для выполнения.
     */
    public function getCommand(): string
    {
        $command = 'php ' . escapeshellarg($this->scriptPath) . ' ' . implode(' ', $this->arguments);
        if ($this->outputRedirection !== null) {
            $command .= ' > ' . escapeshellarg($this->outputRedirection);
        }
        if ($this->runInBackground) {
            $command .= ' 2>&1 &';
        }
        return $command;
    }
}
