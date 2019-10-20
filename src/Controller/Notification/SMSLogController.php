<?php

namespace App\Controller\Notification;

use Matican\ModelSerializer;
use Matican\Models\Notification\SMSModel;
use Matican\Core\Entities\Notifications;
use Matican\Core\Servers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Matican\Core\Transaction\Request as Req;


/**
 * @Route("/notification/sms-log", name="notification_sms_log")
 */
class SMSLogController extends AbstractController
{
    /**
     * @Route("/list", name="_list")
     */
    public function fetchAll()
    {
        $request = new Req(Servers::Notifications, Notifications::SMS, 'all');
        $response = $request->send();

        /**
         * @var $smsLogs SMSModel[]
         */
        $smsLogs = [];
        if ($response->getContent()) {
            foreach ($response->getContent() as $smsLog) {
                $smsLogs[] = ModelSerializer::parse($smsLog, SMSModel::class);
            }
        }

        return $this->render('notification/sms_log/list.html.twig', [
            'controller_name' => 'SMSLogController',
            'smsLogs' => $smsLogs,
        ]);
    }

    /**
     * @Route("/read/{id}", name="_read")
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function read($id, Request $request)
    {
        $inputs = $request->request->all();
        /**
         * @var $smsLogModel SMSModel
         */
        $smsLogModel = ModelSerializer::parse($inputs, SMSModel::class);
        $smsLogModel->setSmsLogId($id);
        $request = new Req(Servers::Notifications, Notifications::SMS, 'fetch');
        $request->add_instance($smsLogModel);
        $response = $request->send();
        /**
         * @var $smsLogModel SMSModel
         */
        $smsLogModel = ModelSerializer::parse($response->getContent(), SMSModel::class);

        return $this->render('notification/sms_log/read.html.twig', [
            'controller_name' => 'SMSLogController',
            'smsLogModel' => $smsLogModel,
        ]);
    }
}
