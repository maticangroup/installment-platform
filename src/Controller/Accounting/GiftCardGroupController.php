<?php

namespace App\Controller\Accounting;

use Matican\Models\Accounting\GiftCardGroupAvailabilityStatus;
use Matican\Models\Accounting\GiftCardGroupModel;
use Matican\Models\Accounting\GiftCardGroupStatusModel;
use Matican\Models\Accounting\GiftCardModel;
use Matican\Models\Accounting\GiftCardStatusModel;
use Matican\ModelSerializer;
use Matican\Authentication\AuthUser;
use Matican\Permissions\ServerPermissions;
use Matican\Core\Entities\Accounting;
use Matican\Core\Servers;
use Matican\ResponseStatus;
use function Sodium\add;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Matican\Core\Transaction\Request as Req;

/**
 * @Route("/accounting/gift-card-group", name="accounting_gift_card_group")
 */
class GiftCardGroupController extends AbstractController
{
    /**
     * @Route("/list", name="_list")
     */
    public function fetchAll()
    {
        if (AuthUser::if_is_allowed(ServerPermissions::accounting_giftcardgroup_all)) {


            $request = new Req(Servers::Accounting, Accounting::GiftCardGroup, 'all');
            $response = $request->send();

            /**
             * @var $giftCardGroups GiftCardGroupModel[]
             */
            $giftCardGroups = [];
            if ($response->getContent()) {
                foreach ($response->getContent() as $giftCardGroup) {
                    $giftCardGroups[] = ModelSerializer::parse($giftCardGroup, GiftCardGroupModel::class);
                }
            }

            return $this->render('accounting/gift_card_group/list.html.twig', [
                'controller_name' => 'GiftCardGroupController',
                'giftCardGroups' => $giftCardGroups,
            ]);
        } else {
            return $this->render('accounting/gift_card_group/list.html.twig', [
                'controller_name' => 'GiftCardGroupController',
                'giftCardGroups' => [],
            ]);
        }
    }

    /**
     * @Route("/create", name="_create")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function create(Request $request)
    {
        if (AuthUser::if_is_allowed(ServerPermissions::accounting_giftcardgroup_new)) {

            $inputs = $request->request->all();
            /**
             * @var $giftCardGroupModel GiftCardGroupModel
             */
            $giftCardGroupModel = ModelSerializer::parse($inputs, GiftCardGroupModel::class);

            if (!empty($inputs)) {
                /**
                 * @var $giftCardGroupModel GiftCardGroupModel
                 */
                $giftCardGroupModel = ModelSerializer::parse($inputs, GiftCardGroupModel::class);
//            dd($giftCardGroupModel);
                $request = new Req(Servers::Accounting, Accounting::GiftCardGroup, 'new');
                $request->add_instance($giftCardGroupModel);
                $response = $request->send();
                if ($response->getStatus() == ResponseStatus::successful) {
                    $this->addFlash('s', $response->getMessage());
                    /**
                     * @var $giftCardGroupModel GiftCardGroupModel
                     */
                    $giftCardGroupModel = ModelSerializer::parse($response->getContent(), GiftCardGroupModel::class);
                    return $this->redirect($this->generateUrl('accounting_gift_card_group_edit', ['id' => $giftCardGroupModel->getGiftCardGroupId()]));
                } else {
                    $this->addFlash('f', $response->getMessage());
                }
            }


            return $this->render('accounting/gift_card_group/create.html.twig', [
                'controller_name' => 'GiftCardGroupController',
                'giftCardGroupModel' => $giftCardGroupModel,
            ]);
        } else {
            return $this->redirect($this->generateUrl('accounting_gift_card_group_list'));
        }
    }

    /**
     * @Route("/edit/{id}", name="_edit")
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public
    function edit($id, Request $request)
    {
        if (AuthUser::if_is_allowed(ServerPermissions::accounting_giftcardgroup_fetch)) {


            $inputs = $request->request->all();
            /**
             * @var $giftCardGroupModel GiftCardGroupModel
             */
            $giftCardGroupModel = ModelSerializer::parse($inputs, GiftCardGroupModel::class);
            $giftCardGroupModel->setGiftCardGroupId($id);
            $request = new Req(Servers::Accounting, Accounting::GiftCardGroup, 'fetch');
            $request->add_instance($giftCardGroupModel);
            $response = $request->send();
            /**
             * @var $giftCardGroupModel GiftCardGroupModel
             */
            $giftCardGroupModel = ModelSerializer::parse($response->getContent(), GiftCardGroupModel::class);

            /**
             * @var $giftCards GiftCardModel[]
             */
            $giftCards = [];
            if ($giftCardGroupModel->getGiftCards()) {
                foreach ($giftCardGroupModel->getGiftCards() as $giftCard) {
                    $giftCards[] = ModelSerializer::parse($giftCard, GiftCardModel::class);
                }
            }

//        if (!empty($inputs)) {
//            /**
//             * @var $giftCardGroupModel GiftCardGroupModel
//             */
//            $giftCardGroupModel = ModelSerializer::parse($inputs, GiftCardGroupModel::class);
//            $giftCardGroupModel->setGiftCardGroupId($id);
//            $request = new Req(Servers::Accounting, Accounting::GiftCardGroup, 'update');
//            $request->add_instance($giftCardGroupModel);
//            $response = $request->send();
//            if ($response->getStatus() == ResponseStatus::successful) {
//                $this->addFlash('s', $response->getMessage());
//                return $this->redirect($this->generateUrl('accounting_gift_card_group_edit', ['id' => $id]));
//            } else {
//                $this->addFlash('s', $response->getMessage());
//            }
//        }


            return $this->render('accounting/gift_card_group/edit.html.twig', [
                'controller_name' => 'GiftCardGroupController',
                'giftCardGroupModel' => $giftCardGroupModel,
                'giftCards' => $giftCards,
            ]);
        } else {
            return $this->redirect($this->generateUrl('accounting_gift_card_group_list'));
        }
    }

    /**
     * @Route("/read/{id}", name="_read")
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public
    function read($id, Request $request)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::accounting_giftcardgroup_fetch)) {
            return $this->redirect($this->generateUrl('accounting_gift_card_group_list'));
        }

        $inputs = $request->request->all();
        /**
         * @var $giftCardGroupModel GiftCardGroupModel
         */
        $giftCardGroupModel = ModelSerializer::parse($inputs, GiftCardGroupModel::class);
        $giftCardGroupModel->setGiftCardGroupId($id);
        $request = new Req(Servers::Accounting, Accounting::GiftCardGroup, 'fetch');
        $request->add_instance($giftCardGroupModel);
        $response = $request->send();
        /**
         * @var $giftCardGroupModel GiftCardGroupModel
         */
        $giftCardGroupModel = ModelSerializer::parse($response->getContent(), GiftCardGroupModel::class);

        /**
         * @var $giftCards GiftCardModel[]
         */
        $giftCards = [];
        if ($giftCardGroupModel->getGiftCards()) {
            foreach ($giftCardGroupModel->getGiftCards() as $giftCard) {
                $giftCards[] = ModelSerializer::parse($giftCard, GiftCardModel::class);
            }
        }
        return $this->render('accounting/gift_card_group/read.html.twig', [
            'controller_name' => 'GiftCardGroupController',
            'giftCardGroupModel' => $giftCardGroupModel,
            'giftCards' => $giftCards,
        ]);

    }

    /**
     * @Route("/confirm-gift-card-group/{gift_card_group_id}", name="_confirm_gift_card_group")
     * @param $gift_card_group_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public
    function confirmGiftCardGroup($gift_card_group_id)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::accounting_giftcardgroup_confirm)) {
            return $this->redirect($this->generateUrl('accounting_gift_card_group_edit', ['id' => $gift_card_group_id]));
        }
        $giftCardGroupModel = new GiftCardGroupModel();
        $giftCardGroupModel->setGiftCardGroupId($gift_card_group_id);
        $request = new Req(Servers::Accounting, Accounting::GiftCardGroup, 'confirm');
        $request->add_instance($giftCardGroupModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('s', $response->getMessage());
        }

        return $this->redirect($this->generateUrl('accounting_gift_card_group_edit', ['id' => $gift_card_group_id]));

    }

    /**
     * @Route("/reject-gift-card-group/{gift_card_group_id}", name="_reject_gift_card_group")
     * @param $gift_card_group_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public
    function rejectGiftCardGroup($gift_card_group_id)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::accounting_giftcardgroup_stop)) {
            return $this->redirect($this->generateUrl('accounting_gift_card_group_edit', ['id' => $gift_card_group_id]));

        }
        $giftCardGroupModel = new GiftCardGroupModel();
        $giftCardGroupModel->setGiftCardGroupId($gift_card_group_id);
        $request = new Req(Servers::Accounting, Accounting::GiftCardGroup, 'stop');
        $request->add_instance($giftCardGroupModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('s', $response->getMessage());
        }

        return $this->redirect($this->generateUrl('accounting_gift_card_group_edit', ['id' => $gift_card_group_id]));
    }


    /**
     * @Route("/gift-card-status/{gift_card_id}/{machine_name}/{gift_card_group_id}", name="_gift_card_status")
     * @param $gift_card_id
     * @param $machine_name
     * @param $gift_card_group_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public
    function changeGiftCardAvailability($gift_card_id, $machine_name, $gift_card_group_id)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::accounting_giftcardgroup_set_card_status)) {
            return $this->redirect($this->generateUrl('accounting_gift_card_group_edit', ['id' => $gift_card_group_id]));

        }
        $giftCardStatusModel = new GiftCardStatusModel();
        if ($machine_name == 'available') {
            $giftCardStatusModel->setGiftCardId($gift_card_id);
            $giftCardStatusModel->setGiftCardStatusMachineName('deactive');
        } else {
            $giftCardStatusModel->setGiftCardId($gift_card_id);
            $giftCardStatusModel->setGiftCardStatusMachineName('available');
        }
        $request = new Req(Servers::Accounting, Accounting::GiftCardGroup, 'set_card_status');
        $request->add_instance($giftCardStatusModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('s', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('accounting_gift_card_group_edit', ['id' => $gift_card_group_id]));
    }

    /**
     * @Route("/gift-card-group-status/{machine_name}/{gift_card_group_id}", name="_gift_card_group_status")
     * @param $machine_name
     * @param $gift_card_group_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public
    function changeAvailability($gift_card_group_id, $machine_name)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::accounting_giftcardgroup_set_card_status)) {
            return $this->redirect($this->generateUrl('accounting_gift_card_group_list'));
        }
        $giftCardGroupAvailabilityStatusModel = new GiftCardGroupAvailabilityStatus();
        if ($machine_name == 'active') {
            $giftCardGroupAvailabilityStatusModel->setGiftCardGroupId($gift_card_group_id);
            $giftCardGroupAvailabilityStatusModel->setGiftCardGroupAvailabilityMachineName('deactive');
        } else {
            $giftCardGroupAvailabilityStatusModel->setGiftCardGroupId($gift_card_group_id);
            $giftCardGroupAvailabilityStatusModel->setGiftCardGroupAvailabilityMachineName('active');
        }
        $request = new Req(Servers::Accounting, Accounting::GiftCardGroup, 'change_gift_card_group_status');
        $request->add_instance($giftCardGroupAvailabilityStatusModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('s', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('accounting_gift_card_group_list'));
    }


}
