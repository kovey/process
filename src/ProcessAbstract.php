<?php
/**
 *
 * @description 用户自定义进程基类
 *
 * @package     Components\Process
 *
 * @time        Tue Sep 24 09:09:20 2019
 *
 * @author      kovey
 */
namespace Kovey\Process;

use Kovey\Library\Config\Manager;
use Swoole\Event;

abstract class ProcessAbstract
{
    /**
     * @description 进程
     *
     * @var Swoole\Process
     */
    protected \Swoole\Process $process;

    /**
     * @description 回调
     *
     * @var callable
     */
    protected $callBack;

    /**
     * @description 服务器对象
     *
     * @var Swoole\Server
     */
    protected \Swoole\Server $server;

    /**
     * @description woker进程数
     *
     * @var Swoole\Atomic
     */
    protected \Swoole\Atomic $workerAtomic;

    /**
     * @description 进程名称
     *
     * @var string
     */
    protected string $processName;

    /**
     * @description 进程
     *
     * @var int
     */
    protected int $workNum = 0;

    /**
     * @description 构造函数
     */
    final public function __construct()
    {
        $this->init();
        $this->process = new \Swoole\Process(array($this, 'callBack'), false, SOCK_DGRAM);
    }

    /**
     * @description 设置服务器对象
     *
     * @param Swoole\Server $server
     *
     * @return ProcessAbstract
     */
    public function setServer(\Swoole\Server $server) : ProcessAbstract
    {
        $this->server = $server;
        $this->workNum = $this->server->setting['worker_num'];
        $this->server->addProcess($this->process);
        return $this;
    }

    /**
     * @description 设置
     *
     * @param Swoole\Atomic $workerAtomic
     *
     * @return ProcessAbstract
     */
    public function setWorkerAtomic(\Swoole\Atomic $workerAtomic) : ProcessAbstract
    {
        $this->workerAtomic = $workerAtomic;
        return $this;
    }

    /**
     * @description 向进程管道写入数据
     *
     * @param mixed $data
     *
     * @return bool
     */
    public function push($data) : bool
    {
        return $this->process->write(serialize($data));
    }

    /**
     * @description 回调处理
     *
     * @param mixed $worker
     *
     * @return null
     */
    public function callBack($worker)
    {
        ko_change_process_name($this->processName);

        $this->busi();
    }

    /**
     * @description 设置进程名称
     *
     * @param string $processName
     *
     * @return ProcessAbstract
     */
    public function setProcessName(string $processName) : ProcessAbstract
    {
        $this->processName = $processName;
        return $this;
    }

    /**
     * @description 向woker进程发送数据
     *
     * @param string $path
     * 
     * @param string $method
     *
     * @param Array $args
     *
     * @param string $traceId
     *
     * @return bool
     */
    protected function send(string $path, string $method, Array $params = array(), string $traceId = '') : bool
    {
        return $this->server->sendMessage(array(
            'p' => $path,
            'm' => $method,
            'a' => $params,
            't' => $traceId
        ), $this->getWorkerId());
    }

    /**
     * @description 获取workerID
     *
     * @return int
     */
    protected function getWorkerId() : int
    {
        $id = $this->workerAtomic->get();
        if ($id >= $this->workNum) {
            $this->workerAtomic->set(0);
            $id = 0;
        }

        $this->workerAtomic->add();
        return $id;
    }

    /**
     * @description 监听管道
     *
     * @param callable $callback
     *
     * @return ProcessAbstract
     */
    protected function listen(callable $callback) : ProcessAbstract
    {
        Event::add($this->process->pipe, $callback);
        return $this;
    }

    /**
     * @description 从管道中读取数据
     *
     * @return mixed
     */
    public function read()
    {
        return unserialize($this->process->read());
    }

    /**
     * @description 初始化
     *
     * @return null
     */
    abstract protected function init();

    /**
     * @description 业务处理
     *
     * @return null
     */
    abstract protected function busi();
}
