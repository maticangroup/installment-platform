<?php

namespace App\Controller\Notification;

use Matican\ModelSerializer;
use Matican\Models\Notification\MailModel;
use Matican\Core\Entities\Notifications;
use Matican\Core\Servers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Matican\Core\Transaction\Request as Req;

/**
 * @Route("/notification/mail-log", name="notification_mail_log")
 */
class MailLogController extends AbstractController
{
    /**
     * @Route("/list", name="_list")
     */
    public function fetchAll()
    {
        $request = new Req(Servers::Notifications, Notifications::Mail, 'all');
        $response = $request->send();

        /**
         * @var $mailLogs MailModel[]
         */
        $mailLogs = [];
        if ($response->getContent()) {
            foreach ($response->getContent() as $mailLog) {
                $mailLogs[] = ModelSerializer::parse($mailLog, MailModel::class);
            }
        }

        return $this->render('notification/mail_log/list.html.twig', [
            'controller_name' => 'MailLogController',
            'mailLogs' => $mailLogs,
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
         * @var $mailLogModel MailModel
         */
        $mailLogModel = ModelSerializer::parse($inputs, MailModel::class);
        $mailLogModel->setMailId($id);
        $request = new Req(Servers::Notifications, Notifications::Mail, 'fetch');
        $request->add_instance($mailLogModel);
        $response = $request->send();
        /**
         * @var $mailLogModel MailModel
         */
        $mailLogModel = ModelSerializer::parse($response->getContent(), MailModel::class);

        return $this->render('notification/mail_log/read.html.twig', [
            'controller_name' => 'MailLogController',
            'mailLogModel' => $mailLogModel
        ]);
    }
}
