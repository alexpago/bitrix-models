<?php

namespace Pago\Bitrix\Models\Console\Generate;

/**
 * Результат генерации модели
 */
class GenerateResult
{
    /**
     * @var bool
     */
    public bool $success;

    /**
     * @var string
     */
    public string $path;

    /**
     * @var string
     */
    public string $filename;

    /**
     * @var string
     */
    public string $namespace;

    /**
     * @var string
     */
    public string $name;

    /**
     * @var array
     */
    public array $warnings;

    public function __construct(
        bool $success = false,
        string $path = '',
        string $filename = '',
        string $namespace = '',
        string $name = '',
        array $warnings = []
    ) {
        $this->success = $success;
        $this->path = $path;
        $this->filename = $filename;
        $this->namespace = $namespace;
        $this->name = $name;
        $this->warnings = $warnings;
    }
}
