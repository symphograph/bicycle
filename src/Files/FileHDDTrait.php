<?php

namespace Symphograph\Bicycle\Files;

use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\FileHelper;

trait FileHDDTrait
{
    public function isPublic(): bool
    {
        $linkPath = $this->publicPath();
        return is_link($linkPath);
    }

    public function privatePath(): string
    {
        $privateFolder = Env::getStorageFolder()->data;
        return $this->path($privateFolder);
    }

    public function publicPath(): string
    {
        $publicFolder = Env::getStorageFolder()->public;
        return $this->path($publicFolder);
    }

    public function setAsPublic(): void
    {
        if($this->isPublic()) return;
        $target = $this->privatePath();
        $linkPath = $this->publicPath();
        FileHelper::symlink($target, $linkPath);
    }

    public function setAsPrivate(): void
    {
        if(!$this->isPublic()) return;

        $linkPath = $this->publicPath();
        FileHelper::deleteAndCleanup($linkPath);
    }

    public function delete(): void
    {
        $this->setAsPrivate();
        FileHelper::deleteAndCleanup($this->privatePath());
    }

    public function isExistsInStorage(): bool
    {
        return FileHelper::fileExists($this->privatePath());
    }
}