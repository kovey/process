<?php
/**
 *
 * @description monitor process
 *
 * @package     Process
 *
 * @time        2020-01-19 14:52:47
 *
 * @author      kovey
 */
namespace Kovey\Process\Process;

use Kovey\Library\Config\Manager;
use Kovey\Rpc\Client\Client;
use Kovey\Logger\Logger;
use Kovey\Library\Util\Json;
use Swoole\Timer;
use Kovey\Process\ProcessAbstract;

class Monitor extends ProcessAbstract
{
    /**
     * @description init
     *
     * @return void
     */
    protected function init() : void
    {
        $this->processName = 'kovey framework cluster monitor';
    }

    /**
     * @description business process
     *
     * @return void
     */
    protected function busi() : void
    {
        $this->listen(function ($pipe) {
            $logger = $this->read();
            if (!is_array($logger)) {
                return;
            }

            $this->sendToMonitor('save', Json::encode($logger), Manager::get('server.server.project'));
        });

        Timer::tick(60000, function () {
            $result = sys_getloadavg();
            $this->sendToMonitor('load', $result, Manager::get('server.server.name'), Manager::get('server.server.project'));
            Logger::writeInfoLog(__LINE__, __FILE__, 'sys load average: ' . Json::encode($result));
        });

        Timer::after(5000, function () {
            try {
                $dir = APPLICATION_PATH . '/application/controllers';
                $namespace = '';
                $suffix = 'Controller';
                if (!is_dir($dir)) {
                    $dir = APPLICATION_PATH . '/application/Handler';
                    if (!is_dir($dir)) {
                        return;
                    }
                    $namespace = 'Handler';
                    $suffix = '';
                }

                $apis = array();
                foreach (scandir($dir) as $file) {
                    if (substr($file, -4) !== '.php') {
                        continue;
                    }

                    $class = trim($namespace . '\\' . substr($file, 0, -4) . $suffix, '\\');
                    $ref = new \ReflectionMethod($class);
                    foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                        $apis[] = $ref->getName() . '::' . $method->getName();
                    }
                }

                $this->sendToMonitor('apis', Manager::get('server.server.name'), Manager::get('server.server.project'), $apis);
            } catch (\Throwable $e) {
            }
        });
    }

    /**
     * @description send data to monitor system
     *
     * @param string $method
     *
     * @param ...mixed $args
     *
     * @return void
     */
    protected function sendToMonitor(string $method, ...$args) : void
    {
        go(function ($method, $args) {
            $cli = new Client(Manager::get('rpc.monitor'));
            if (!$cli->connect()) {
                Logger::writeWarningLog(__LINE__, __FILE__, $cli->getError());
                return;
            }

            if (!$cli->send(array(
                'p' => 'Monitor',
                'm' => $method,
                'a' => $args,
                't' => hash('sha256', uniqid('monitor', true) . random_int(0, 9999999)),
                'f' => Manager::get('server.server.name')
            ))) {
                $cli->close();
                Logger::writeWarningLog(__LINE__, __FILE__, $cli->getError());
                return;
            }

            $result = $cli->recv();
            $cli->close();

            if (empty($result)) {
                Logger::writeWarningLog(__LINE__, __FILE__, 'response error');
                return;
            }

            if ($result['code'] > 0) {
                if ($result['type'] != 'success') {
                    Logger::writeWarningLog(__LINE__, __FILE__, $result['err']);
                }
            }

            if (empty($result['result'])) {
                Logger::writeWarningLog(__LINE__, __FILE__, 'save fail, logger: ' . json_encode($result));
            }
        }, $method, $args);
    }
}
