<?php

namespace Symphograph\Bicycle\Api;

class ApiRouter
{
    private string $basePath;
    private string $scriptRoot;
    private ?string $internalPath = null;

    public function __construct(string $basePath, string $scriptRoot)
    {
        $this->basePath = '/' . trim($basePath, '/') . '/';
        $this->scriptRoot = rtrim($scriptRoot, '/');
    }

    /**
     * Разбирает текущий URL, возвращает внутренний путь без .php.
     * Одновременно кладёт метод в $_POST['method'] для совместимости.
     */
    public function prepare(): string
    {
        if ($this->internalPath !== null) {
            return $this->internalPath;
        }

        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $relativePath = substr($requestUri, strlen($this->basePath));
        $segments = array_values(array_filter(explode('/', $relativePath), fn($s) => $s !== ''));

        if (empty($segments)) {
            http_response_code(404);
            exit('Method not specified');
        }

        // Получаем последний сегмент, который может быть "script.method" или просто "method"
        $lastSegment = array_pop($segments);

        // Ищем последнюю точку
        $dotPos = strrpos($lastSegment, '.');
        if ($dotPos !== false) {
            // Формат: script.method
            $scriptName = substr($lastSegment, 0, $dotPos);
            $method = substr($lastSegment, $dotPos + 1);
            if ($scriptName === '' || $method === '') {
                http_response_code(400);
                exit('Invalid endpoint format');
            }
            // Добавляем scriptName к сегментам пути
            $segments[] = $scriptName;
            $this->internalPath = implode('/', $segments);
            $_POST['method'] = $method;
        } else {
            // Старый формат: последний сегмент целиком — метод
            $method = $lastSegment;
            $this->internalPath = implode('/', $segments);
            $_POST['method'] = $method;
        }

        // Валидация всех сегментов пути (кроме метода)
        foreach ($segments as $seg) {
            if ($seg === '.' || $seg === '..' || !preg_match('/^[a-zA-Z0-9_-]+$/', $seg)) {
                http_response_code(400);
                exit('Invalid path segment: ' . htmlspecialchars($seg));
            }
        }

        return $this->internalPath;
    }

    /**
     * Подключает файл-обработчик на основе подготовленного внутреннего пути.
     */
    public function dispatch(): void
    {
        $internalPath = $this->prepare();

        $scriptRelativePath = $internalPath . '.php';
        $scriptFullPath = $this->scriptRoot . '/' . $scriptRelativePath;

        $realScriptPath = realpath($scriptFullPath);

        // Проверка на самоподключение (рекурсию)
        if ($realScriptPath !== false && $realScriptPath === realpath($_SERVER['SCRIPT_FILENAME'])) {
            http_response_code(404);
            exit("Endpoint not found: $scriptRelativePath");
        }

        if ($realScriptPath === false || !str_starts_with($realScriptPath, $this->scriptRoot . '/')) {
            http_response_code(404);
            exit("Endpoint not found: $scriptRelativePath");
        }

        if (!is_file($realScriptPath)) {
            http_response_code(404);
            exit("Endpoint not found: $scriptRelativePath");
        }

        require $realScriptPath;
    }

    /**
     * Удобный метод: подготовка + подключение.
     */
    public function handle(): void
    {
        $this->dispatch();
    }
}