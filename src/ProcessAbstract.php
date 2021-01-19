<?php
/**
 *
 * @description user custom process
 *
 * @package     Process
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
     * @description process
     *
     * @var Swoole\Process
     */
    protected \Swoole\Process $process;

    /**
     * @description callback
     *
     * @var callable | Array
     */
    protected mixed $callBack;

    /**
     * @description server
     *
     * @var Swoole\Server
     */
    protected \Swoole\Server $server;

    /**
     * @description woker count
     *
     * @var Swoole\Atomic
     */
    protected \Swoole\Atomic $workerAtomic;

    /**
     * @description process name
     *
     * @var string
     */
    protected string $processName;

    /**
     * @description woker number
     *
     * @var int
     */
    protected int $workNum = 0;

    /**
     * @description constructor
     */
    final public function __construct()
    {
        $this->init();
        $this->process = new \Swoole\Process(array($this, 'callBack'), false, SOCK_DGRAM);
    }

    /**
     * @description set server
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
     * @description set worker count
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
     * @description write data into pipe
     *
     * @param mixed $data
     *
     * @return bool
     */
    public function push(mixed $data) : bool
    {
        return $this->process->write(serialize($data));
    }

    /**
     * @description callBack
     *
     * @param mixed $worker
     *
     * @return void
     */
    public function callBack(mixed $worker) : void
    {
        ko_change_process_name($this->processName);

        $this->busi();
    }

    /**
     * @description set process name
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
     * @description send data to worker process
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
     * @description get worker id
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
     * @description listen pipe event
     *
     * @param callable | array $callback
     *
     * @return ProcessAbstract
     */
    protected function listen(callable | Array $callback) : ProcessAbstract
    {
        Event::add($this->process->pipe, $callback);
        return $this;
    }

    /**
     * @description read data from pipe
     *
     * @return mixed
     */
    public function read() : mixed
    {
        return unserialize($this->process->read());
    }

    /**
     * @description init
     *
     * @return void
     */
    abstract protected function init() : void;

    /**
     * @description business process
     *
     * @return void
     */
    abstract protected function busi() : void;
}
