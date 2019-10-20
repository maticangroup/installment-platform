<?php

namespace App\Controller\Notification;

use Matican\ModelSerializer;
use Matican\Models\Notification\InternalNotificationModel;
use Matican\Core\Entities\Notifications;
use Matican\Core\Servers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Matican\Core\Transaction\Request as Req;


/**
 * @Route("/notification", name="notification")
 */
class NotificationController extends AbstractController
{
    /**
     * @Route("/list", name="_list")
     */
    public function fetchAll()
    {
        $request = new Req(Servers::Notifications, Notifications::InternalNotification, 'all');
        $response = $request->send();

        /**
         * @var $notifications InternalNotificationModel[]
         */
        $notifications = [];
        if ($response->getContent()) {
            foreach ($response->getContent() as $notification) {
                $notifications[] = ModelSerializer::parse($notification, InternalNotificationModel::class);
            }
        }
        return $this->render('notification/notification/list.html.twig', [
            'controller_name' => 'NotificationController',
            'notifications' => $notifications,
        ]);
    }
}
