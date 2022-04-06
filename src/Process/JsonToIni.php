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

use Kovey\Logger\Logger;
use Swoole\Timer;
use Swoole\Event;
use Kovey\Process\ProcessAbstract;
use Kovey\Library\Util\Json;

class JsonToIni extends ProcessAbstract
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
        $this->processName = 'kovey framework json to ini';
    }

    /**
     * @description business process
     *
     * @return void
     */
    protected function busi() : void
    {
        Timer::tick(5000, function () {
            $this->jsonToIni();
        });

        Event::wait();
    }

    protected function jsonToIni() : void
    {
        if (!is_dir($this->path)) {
            return;
        }

        $files = scandir($this->path);
        foreach ($files as $file) {
            $suffix = substr($file, -9);
            if ($suffix === false || strtolower($suffix) !== '.ini.json') {
                continue;
            }

            $filePath = $this->path . '/' . $file;
            $content = file_get_contents($filePath);
            if (empty($content)) {
                continue;
            }

            try {
                $result = array();
                $config = Json::decode($content);
                foreach ($config as $area => $conf) {
                    if (!is_array($conf)) {
                        continue;
                    }

                    $result[] = "[" . $area . "]\n";
                    $result[] = $this->toIni($conf);
                }

                file_put_contents(str_replace('.ini.json', '.ini', $filePath), implode("", $result));
            } catch (\Throwable $e) {
            }
        }
    }

    protected function toIni(Array $config, string | int | bool $key = false) : string
    {
        $result = '';
        foreach ($config as $k => $value) {
            $field = $k;
            if ($key !== false) {
                $field = $key .'.' . $k;
            }

            if (is_array($value)) {
                $result .= $this->toIni($value, $field);
                continue;
            }

            $result .= $field .'=' . $value . "\n";
        }

        return $result;
    }
}
