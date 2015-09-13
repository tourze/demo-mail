<?php

namespace mailServer;

use Swift_Attachment;
use Swift_Mailer;
use Swift_MailTransport;
use Swift_Message;
use Swift_SendmailTransport;
use Swift_SmtpTransport;
use tourze\Base\Base;
use tourze\Base\Config;
use tourze\Base\Helper\Arr;

/**
 * 邮件发送业务
 *
 * @package mail
 */
class MailServer
{

    /**
     * @var array Swift_Mailer[]
     */
    public static $mailerList = [];

    /**
     * 从配置文件中加载发送器信息
     */
    public static function loadConfig()
    {
        $config = Config::load('mailServer')->asArray();
        foreach ($config as $id => $params)
        {
            if ( ! isset($params['id']))
            {
                $params['id'] = $id;
            }
            self::createMailer($params);
        }
    }

    /**
     * 创建指定配置的发送器
     *
     * @param array $params
     * @return bool
     */
    public static function createMailer($params)
    {
        $mailerID = Arr::get($params, 'id', md5(json_encode($params)));

        switch (Arr::get($params, 'transport'))
        {
            case 'smtp':
                $transport = new Swift_SmtpTransport(
                    Arr::get($params, 'host', 'localhost'),
                    Arr::get($params, 'port', 25),
                    Arr::get($params, 'security', null)
                );
                $transport->setUsername(Arr::get($params, 'username'));
                $transport->setPassword(Arr::get($params, 'password'));
                break;
            case 'sendmail':
                $transport = new Swift_SendmailTransport(Arr::get($params, 'command', '/usr/sbin/sendmail -bs'));
                break;
            case 'mail':
                $transport = new Swift_MailTransport(Arr::get($params, 'params', '-f%s'));
                break;
            default:
                $transport = false;
        }

        if ( ! $transport)
        {
            return false;
        }

        $mailer = new Swift_Mailer($transport);
        self::$mailerList[$mailerID] = $mailer;
        return true;
    }

    /**
     * 发送邮件
     *
     * @param array               $params
     * @param string|Swift_Mailer $mailer
     * @return bool
     */
    public static function send($params, $mailer = null)
    {
        Base::getLog()->info(__METHOD__ . ' send mail - start', [
            'params' => $params,
            'mailer' => $mailer,
        ]);

        if ( ! $mailer)
        {
            $mailer = current(self::$mailerList);
        }
        else
        {
            if (is_string($mailer))
            {
                $mailer = Arr::get(self::$mailerList, $mailer);
            }
            elseif ($mailer instanceof Swift_Mailer)
            {
                // 这种情况跳过其他处理
            }
        }
        if ( ! $mailer)
        {
            Base::getLog()->warning(__METHOD__ . ' not match mailers');
            return false;
        }

        $message = Swift_Message::newInstance(
            Arr::get($params, 'subject'),
            Arr::get($params, 'body'),
            Arr::get($params, 'contentType', 'text/html'), // 默认是html
            Arr::get($params, 'charset')
        );

        // 数组格式[address => name]
        if ($from = Arr::get($params, 'from'))
        {
            $message->setFrom($from);
        }

        // 数组格式[address => name]
        if ($to = Arr::get($params, 'to'))
        {
            $message->setTo($to);
        }

        // 数组格式[address => name]
        if ($bcc = Arr::get($params, 'bcc'))
        {
            $message->setBcc($bcc);
        }

        // 数组格式[address => name]
        if ($cc = Arr::get($params, 'cc'))
        {
            $message->setCc($cc);
        }

        // 数组格式[address => name]
        if ($replyTo = Arr::get($params, 'replyTo'))
        {
            $message->setReplyTo($replyTo);
        }

        if ($boundary = Arr::get($params, 'boundary'))
        {
            $message->setBoundary($boundary);
        }

        // 时间格式为时间戳
        if ($date = Arr::get($params, 'date'))
        {
            $message->setDate($date);
        }

        // 邮件的描述
        if ($description = Arr::get($params, 'description'))
        {
            $message->setDescription($description);
        }

        if ($format = Arr::get($params, 'format'))
        {
            $message->setFormat($format);
        }

        // 重要性，可选范围1-5
        if ($priority = Arr::get($params, 'priority'))
        {
            $message->setPriority($priority);
        }

        // 回执
        if ($readReceipt = Arr::get($params, 'readReceipt'))
        {
            $message->setReadReceiptTo($readReceipt);
        }

        if ($returnPath = Arr::get($params, 'returnPath'))
        {
            $message->setReturnPath($returnPath);
        }

        // 附件
        if ($attachments = (array) Arr::get($params, 'attachments'))
        {
            foreach ($attachments as $fileName => $filePath)
            {
                $attachment = Swift_Attachment::fromPath($filePath)->setFilename($fileName);
                $message->attach($attachment);
            }
        }

        $failures = [];
        //print_r($mailer);
        $result = $mailer->send($message, $failures);

        Base::getLog()->info(__METHOD__ . ' send mail - end', [
            'result'   => $result,
            'failures' => $failures,
        ]);
        return $result > 0;
    }
}
