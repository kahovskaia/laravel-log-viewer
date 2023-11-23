<?php

namespace Kahovskaia\LaravelLogViewer;

class LogViewer
{
    private array $filesIgnore = [
        '.',
        '..',
        '.gitignore'
    ];

    private array $icons = [
        'debug' => 'circle-filled',
        'info' => 'circle-filled',
        'notice' => 'circle-filled',
        'warning' => 'triangle-filled',
        'error' => 'triangle-filled',
        'critical' => 'triangle-filled',
        'alert' => 'triangle-filled',
        'emergency' => 'triangle-filled',
        'processed' => 'circle-filled',
        'failed' => 'triangle-filled'
    ];

    private array $levelsClasses = [
        'debug' => 'info',
        'info' => 'info',
        'notice' => 'info',
        'warning' => 'warning',
        'error' => 'danger',
        'critical' => 'danger',
        'alert' => 'danger',
        'emergency' => 'danger',
        'processed' => 'info',
        'failed' => 'warning',
    ];

    public function all()
    {
        return array_keys($this->icons);
    }

    public function getCssClass($level)
    {
        return $this->levelsClasses[$level];
    }

    public function getImg($level)
    {
        return $this->icons[$level];
    }

    public function getFilesIgnore()
    {
        return $this->filesIgnore;
    }
}
