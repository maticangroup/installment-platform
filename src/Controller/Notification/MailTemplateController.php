<?php

namespace App\Controller\Notification;

use Matican\ModelSerializer;
use Matican\Models\Notification\MailTemplateModel;
use Matican\Models\Notification\TokenModel;
use Matican\Core\Entities\Notifications;
use Matican\Core\Servers;
use Matican\ResponseStatus;
use function Sodium\add;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Matican\Core\Transaction\Request as Req;

/**
 * @Route("/notification/mail-template", name="notification_mail_template")
 */
class MailTemplateController extends AbstractController
{
    /**
     * @Route("/list", name="_list")
     */
    public function fetchAll()
    {

        $request = new Req(Servers::Notifications, Notifications::MailTemplate, 'all');
        $response = $request->send();

        /**
         * @var $mailTemplates MailTemplateModel[]
         */
        $mailTemplates = [];
        if ($response->getContent()) {
            foreach ($response->getContent() as $mailTemplate) {
                $mailTemplates[] = ModelSerializer::parse($mailTemplate, MailTemplateModel::class);
            }
        }

        return $this->render('notification/mail_template/list.html.twig', [
            'controller_name' => 'MailTemplateController',
            'mailTemplates' => $mailTemplates,
        ]);
    }

    /**
     * @Route("/edit/{id}", name="_edit")
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function edit($id, Request $request)
    {
        $inputs = $request->request->all();
        /**
         * @var $mailTemplateModel MailTemplateModel
         */
        $mailTemplateModel = ModelSerializer::parse($inputs, MailTemplateModel::class);
        $mailTemplateModel->setMailTemplateId($id);
        $request = new Req(Servers::Notifications, Notifications::MailTemplate, 'fetch');
        $request->add_instance($mailTemplateModel);
        $response = $request->send();
        /**
         * @var $mailTemplateModel MailTemplateModel
         */
        $mailTemplateModel = ModelSerializer::parse($response->getContent(), MailTemplateModel::class);

        /**
         * @var $mailTokens TokenModel[]
         */
        $mailTokens = [];
        if ($mailTemplateModel->getMailTemplateTokens()) {
            foreach ($mailTemplateModel->getMailTemplateTokens() as $token) {
                $mailTokens[] = ModelSerializer::parse($token, TokenModel::class);
            }
        }

        if (!empty($inputs)) {
            /**
             * @var $mailTemplateModel MailTemplateModel
             */
            $mailTemplateModel = ModelSerializer::parse($inputs, MailTemplateModel::class);
            $mailTemplateModel->setMailTemplateId($id);
            $request = new Req(Servers::Notifications, Notifications::MailTemplate, 'update');
            $request->add_instance($mailTemplateModel);
            $response = $request->send();
            if ($response->getStatus() == ResponseStatus::successful) {
                $this->addFlash('s', $response->getMessage());
                return $this->redirect($this->generateUrl('notification_mail_template_edit', ['id' => $id]));
            } else {
                $this->addFlash('f', $response->getMessage());
            }
        }


        return $this->render('notification/mail_template/edit.html.twig', [
            'controller_name' => 'MailTemplateController',
            'mailTemplateModel' => $mailTemplateModel,
            'mailTokens' => $mailTokens,
        ]);
    }
}
