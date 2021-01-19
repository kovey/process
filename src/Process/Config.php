<?php
/**
 *
 * @description config parse
 *
 * @package     Process
 *
 * @time        Tue Sep 24 09:07:51 2019
 *
 * @author      kovey
 */
namespace Kovey\Process\Process;

use Kovey\Library\Config\Manager;
use Kovey\Logger\Logger;
use Swoole\Timer;
use Kovey\Process\ProcessAbstract;

class Config extends ProcessAbstract
{
    /**
     * @description init
     *
     * @return void
     */
    protected function init() : void
    {
        $this->processName = 'kovey framework config';
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
        });

        Timer::tick(Manager::get('server.sleep.config') * 1000, function () {
            Manager::parse();
            Logger::writeInfoLog(__LINE__, __FILE__, 'reload config');
        });
    }
}
