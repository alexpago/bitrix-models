<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Console\Traits;

/**
 * Форматирование и вывод сообщенийв консоль
 */
trait ConsoleMessage
{
    /**
     * @param  string  $str
     * @param  string  $type
     * @return string|null
     */
    public function question(string $str, string $type = 'i'): ?string
    {
        return readline(trim($this->getConsoleTextColor($str, $type)) . ': ');
    }

    /**
     * @param  string  $str
     * @return void
     */
    public function error(string $str): void
    {
        fwrite(STDOUT, trim($this->getConsoleTextColor($str, 'e')) . PHP_EOL);
        exit();
    }

    /**
     * @param  string  $message
     * @param  string  $type
     * @return void
     */
    public function message(string $message, string $type = 'i'): void
    {
        fwrite(STDOUT, trim($this->getConsoleTextColor($message, $type)) . PHP_EOL);
    }

    /**
     * @param  string  $message
     * @return void
     */
    public function success(string $message): void
    {
        $this->message($message, 's');
    }

    /**
     * @param  string  $message
     * @return void
     */
    public function warning(string $message): void
    {
        $this->message($message, 'w');
    }

    /**
     * @param  string  $message
     * @return void
     */
    public function info(string $message): void
    {
        $this->message($message);
    }

    /**
     * Установка цвета текста для вывода в консоль
     * @param  string  $str
     * @param  string  $type
     * @return string
     */
    private function getConsoleTextColor(string $str, string $type = 'i'): string
    {
        switch ($type) {
            case 'e': //error
                $message = "\033[31m$str \033[0m";
                break;
            case 's': //success
                $message = "\033[32m$str \033[0m";
                break;
            case 'w': //warning
                $message = "\033[33m$str \033[0m";
                break;
            case 'i': //info
                $message = "\033[36m$str \033[0m";
                break;
            default:
                $message = null;
                break;
        }

        return $message;
    }
}
