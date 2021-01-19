<?php
/**
 *
 * @description user process manager
 *
 * @package     Server
 *
 * @time        Tue Sep 24 08:53:06 2019
 *
 * @author      kovey
 */
namespace Kovey\Process;

class UserProcess
{
    /**
     * @description all custom process
     *
     * @var Array
     */
    private Array $procs;

    /**
     * @description constructor
     *
     * @param int $workerNum;
     */
    public function __construct(int $workerNum)
    {
        $this->workerAtomic = new \Swoole\Atomic($workerNum);
        $this->procs = array();
    }

    /**
     * @description add process
     *
     * @param string $name
     *
     * @param ProcessAbstract $process
     *
     * @return UserProcess
     */
    public function addProcess(string $name, ProcessAbstract $process) : UserProcess
    {
        $process->setWorkerAtomic($this->workerAtomic);
        $this->procs[$name] = $process;
        return $this;
    }

    /**
     * @description write data into process pipe
     *
     * @param string $name
     *
     * @param mixed $data
     *
     * @return bool
     */
    public function push(string $name, mixed $data) : bool
    {
        if (!isset($this->procs[$name])) {
            return false;
        }

        return $this->procs[$name]->push($data);
    }
}
