<?php

namespace App\Controller\Notification;

use Matican\ModelSerializer;
use Matican\Models\Notification\ClientMessageModel;
use Matican\Core\Entities\Notifications;
use Matican\Core\Servers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Matican\Core\Transaction\Request as Req;

/**
 * @Route("/notification/client-message", name="notification_client_message")
 */
class ClientMessageController extends AbstractController
{
    /**
     * @Route("/list", name="_list")
     */
    public function fetchAll()
    {
        $request = new Req(Servers::Notifications, Notifications::ClientMessage, 'all');
        $response = $request->send();

        /**
         * @var $clientMessages ClientMessageModel[]
         */
        $clientMessages = [];
        if ($response->getContent()) {
            foreach ($response->getContent() as $clientMessage) {
                $clientMessages[] = ModelSerializer::parse($clientMessage, ClientMessageModel::class);
            }
        }

        return $this->render('notification/client_message/list.html.twig', [
            'controller_name' => 'ClientMessageController',
            'clientMessages' => $clientMessages,
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
         * @var $clientMessageModel ClientMessageModel
         */
        $clientMessageModel = ModelSerializer::parse($inputs, ClientMessageModel::class);
        $clientMessageModel->setClientMessageId($id);
        $request = new Req(Servers::Notifications, Notifications::ClientMessage, 'fetch');
        $request->add_instance($clientMessageModel);
        $response = $request->send();
        /**
         * @var $clientMessageModel ClientMessageModel
         */
        $clientMessageModel = ModelSerializer::parse($response->getContent(), ClientMessageModel::class);


        return $this->render('notification/client_message/read.html.twig', [
            'controller_name' => 'ClientMessageController',
            'clientMessageModel' => $clientMessageModel,
        ]);
    }
}
