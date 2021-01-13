<?php
/**
 *
 * @description 配置文件检测进程
 *
 * @package     Components\Process
 *
 * @time        Tue Sep 24 09:07:51 2019
 *
 * @author      kovey
 */
namespace Kovey\Process\Process;

use Kovey\Library\Config\Manager;
use Kovey\Logger\Logger;
use Swoole\Timer;

class Config extends ProcessAbstract
{
    /**
     * @description 初始化
     *
     * @return null
     */
    protected function init()
    {
        $this->processName = 'kovey framework config';
    }

    /**
     * @description 业务逻辑处理
     *
     * @return null
     */
    protected function busi()
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
