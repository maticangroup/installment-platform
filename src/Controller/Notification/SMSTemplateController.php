<?php

namespace App\Controller\Notification;

use Matican\ModelSerializer;
use Matican\Models\Notification\SMSTemplateModel;
use Matican\Models\Notification\TokenModel;
use Matican\Core\Entities\Notifications;
use Matican\Core\Servers;
use Matican\ResponseStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Matican\Core\Transaction\Request as Req;

/**
 * @Route("/notification/sms-template", name="notification_sms_template")
 */
class SMSTemplateController extends AbstractController
{
    /**
     * @Route("/list", name="_list")
     */
    public function fetchAll()
    {
        $request = new Req(Servers::Notifications, Notifications::SMSTemplate, 'all');
        $response = $request->send();

        /**
         * @var $smsTemplates SMSTemplateModel[]
         */
        $smsTemplates = [];
        if ($response->getContent()) {
            foreach ($response->getContent() as $smsTemplate) {
                $smsTemplates[] = ModelSerializer::parse($smsTemplate, SMSTemplateModel::class);
            }
        }

        return $this->render('notification/sms_template/list.html.twig', [
            'controller_name' => 'SMSTemplateController',
            'smsTemplates' => $smsTemplates,
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
         * @var $smsTemplateModel SMSTemplateModel
         */
        $smsTemplateModel = ModelSerializer::parse($inputs, SMSTemplateModel::class);
        $smsTemplateModel->setSmsTemplateId($id);
        $request = new Req(Servers::Notifications, Notifications::SMSTemplate, 'fetch');
        $request->add_instance($smsTemplateModel);
        $response = $request->send();
        /**
         * @var $smsTemplateModel SMSTemplateModel
         */
        $smsTemplateModel = ModelSerializer::parse($response->getContent(), SMSTemplateModel::class);

        /**
         * @var $smsTokens TokenModel[]
         */
        $smsTokens = [];
        if ($smsTemplateModel->getSmsTemplateTokens()) {
            foreach ($smsTemplateModel->getSmsTemplateTokens() as $smsToken) {
                $smsTokens[] = ModelSerializer::parse($smsToken, TokenModel::class);
            }
        }

        if (!empty($inputs)) {
            /**
             * @var $smsTemplateModel SMSTemplateModel
             */
            $smsTemplateModel = ModelSerializer::parse($inputs, SMSTemplateModel::class);
            $smsTemplateModel->setSmsTemplateId($id);
            $request = new Req(Servers::Notifications, Notifications::SMSTemplate, 'update');
            $request->add_instance($smsTemplateModel);
            $response = $request->send();
            if ($response->getStatus() == ResponseStatus::successful) {
                $this->addFlash('s', $response->getMessage());
                return $this->redirect($this->generateUrl('notification_sms_template_edit', ['id' => $id]));
            } else {
                $this->addFlash('f', $response->getMessage());
            }
        }


        return $this->render('notification/sms_template/edit.html.twig', [
            'controller_name' => 'SMSTemplateController',
            'smsTemplateModel' => $smsTemplateModel,
            'smsTokens' => $smsTokens,
        ]);
    }
}
