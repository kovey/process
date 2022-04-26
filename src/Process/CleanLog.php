<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2022-04-02 17:58:14
 *
 */
namespace Kovey\Process\Process;

use Swoole\Timer;
use Swoole\Event;
use Kovey\Process\ProcessAbstract;

class CleanLog extends ProcessAbstract
{
    private string $path;

    public function setPath(string $path) : void
    {
        $this->path = $path;
    }

    /**
     * @description init
     *
     * @return void
     */
    protected function init() : void
    {
        $this->processName = 'kovey framework clean log';
    }

    /**
     * @description business process
     *
     * @return void
     */
    protected function busi() : void
    {
        $time = 24 * 3600 * 1000;
        Timer::tick($time, fn() => self::delDir($this->path));
        Event::wait();
    }

    public static function delDir(string $dir) : void
    {
        if (empty($dir) || !is_dir($dir)) {
            return;
        }

        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            $file = $dir . '/' . $file;
            if (is_dir($file)) {
                self::delDir($dir);
                continue;
            }

            try {
                unlink($file);
            } catch (\Throwable $e) {
            }
        }
    }
}
