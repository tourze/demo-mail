<?php

namespace mail\Bootstrap;

use mail\Mail;
use tourze\Base\Base;
use tourze\Base\Helper\Arr;
use tourze\Server\Worker;
use Workerman\Connection\ConnectionInterface;
use Workerman\Lib\Timer;

/**
 * 处理接受任务和发送邮件相关操作
 *
 * @package stat\Bootstrap
 */
class MailWorker extends Worker
{

    /**
     * @var int 每30s检查一次发送队列
     */
    public $mailSendInternal = 10;

    /**
     * @var int 每次最多处理30个任务
     */
    public $mailSendOnce = 30;

    /**
     * @var int 单次发送最多重复次数
     */
    public $mailRetryTimes = 3;

    /**
     * @var array 队列
     */
    public $queue = [];

    /**
     * @var string 队列文件
     */
    public $queueFile;

    /**
     * {@inheritdoc}
     */
    public function __construct($config)
    {
        parent::__construct($config);
        $this->onWorkerStart = [$this, 'onStart'];
        $this->onMessage = [$this, 'onMessage'];
    }

    /**
     * 业务处理
     *
     * @see Man\Core.SocketWorker::dealProcess()
     * @param ConnectionInterface $connection
     * @param array               $data
     */
    public function onMessage($connection, $data)
    {
        $data = trim($data);
        if ($data)
        {
            $data = (array) json_decode($data, true);
            $cmd = Arr::get($data, 'cmd');
            $params = (array) Arr::get($data, 'params');
            $result = 0;

            switch ($cmd)
            {
                // 提交到队列
                case 'submit':
                    $result = $this->submitQueue($params);
                    break;
                default:
                    // ignore
            }

            Base::getLog()->info(__METHOD__ . ' receive data', [
                'data' => $data,
            ]);
            $connection->send($result);
        }
    }

    /**
     * 提交到队列
     *
     * @param array $params
     * @return int
     */
    public function submitQueue(array $params)
    {
        array_push($this->queue, $params);
        Base::getLog()->info(__METHOD__ . ' add one mail into queue', $params);
        return 1;
    }

    /**
     * 开始时增加定时任务
     */
    protected function onStart()
    {
        // 定时发送邮件
        Timer::add($this->mailSendInternal, [$this, 'processSendTask']);

        // 从文件中读取已经存在的队列文件
        if ($this->queueFile)
        {
            $this->queue = json_decode(Base::load($this->queueFile), true);
        }
    }

    /**
     * 进程停止时需要将数据写入磁盘
     */
    protected function onStop()
    {
        // 保存当前队列信息
        if ($this->queueFile)
        {
            file_put_contents($this->queueFile, json_encode($this->queue));
        }
    }

    /**
     * 处理队列任务
     */
    public function processSendTask()
    {
        Base::getLog()->info(__METHOD__ . ' process send task - start');

        $i = 1;
        while ($i <= $this->mailSendOnce)
        {
            if (empty($this->queue))
            {
                break;
            }
            $mail = (array) array_pop($this->queue);
            $result = Mail::send($mail, Arr::get($mail, 'sender'));
            if ($result)
            {
                Base::getLog()->info(__METHOD__ . ' send one mail success', $mail);
            }
            else
            {
                Base::getLog()->warning(__METHOD__ . ' send one mail failed', $mail);
                // 发送失败后
                $retryCount = Arr::get($mail, 'retry', 0);
                if ($retryCount < $this->mailRetryTimes)
                {
                    // 重试次数+1，然后重新插入到队列
                    $mail['retry'] = $retryCount + 1;
                    $this->submitQueue($mail);
                }
            }
            $i++;
        }

        Base::getLog()->info(__METHOD__ . ' process send task - end');
    }
} 
