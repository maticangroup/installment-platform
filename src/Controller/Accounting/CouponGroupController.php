<?php

namespace App\Controller\Accounting;

use Matican\Models\Accounting\CouponGroupModel;
use Matican\Models\Accounting\CouponGroupStatusModel;
use Matican\Models\Accounting\UsedCouponModel;
use Matican\ModelSerializer;
use Matican\Models\Repository\PersonModel;
use Matican\Authentication\AuthUser;
use Matican\Permissions\ServerPermissions;
use Matican\Core\Entities\Accounting;
use Matican\Core\Entities\Repository;
use Matican\Core\Servers;
use Matican\ResponseStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Matican\Core\Transaction\Request as Req;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/accounting/coupon-group", name="accounting_coupon_group")
 */
class CouponGroupController extends AbstractController
{

    /**
     * @Route("/list", name="_list")
     */
    public function fetchAll()
    {
        $canSeeAll = AuthUser::if_is_allowed(ServerPermissions::accounting_coupongroup_all);
        $canEdit = AuthUser::if_is_allowed(ServerPermissions::accounting_coupongroup_fetch);
        $canCreate = AuthUser::if_is_allowed(ServerPermissions::accounting_coupongroup_new);

        if ($canSeeAll) {

            $request = new Req(Servers::Accounting, Accounting::CouponGroup, 'all');
            $response = $request->send();

            /**
             * @var $couponGroups CouponGroupModel[]
             */
            $couponGroups = [];
            if ($response->getContent()) {
                foreach ($response->getContent() as $item) {
                    $couponGroups[] = ModelSerializer::parse($item, CouponGroupModel::class);
                }
            }
//        $couponGroups = $response->getContent();
            return $this->render('accounting/coupon_group/list.html.twig', [
                'controller_name' => 'CouponGroupController',
                'couponGroups' => $couponGroups,
                'canSeeAll' => $canSeeAll,
                'canEdit' => $canEdit,
                'canCreate' => $canCreate,
            ]);
        } else {
            return $this->redirect($this->generateUrl('accounting_coupon_group_create'));
        }
    }

    /**
     * @Route("/create", name="_create")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public
    function create(Request $request)
    {
        $canCreate = AuthUser::if_is_allowed(ServerPermissions::accounting_coupongroup_new);
        $canEdit = AuthUser::if_is_allowed(ServerPermissions::accounting_coupongroup_fetch);

        if ($canCreate) {


            $inputs = $request->request->all();
            /**
             * @var $couponGroupModel CouponGroupModel
             */
            $couponGroupModel = ModelSerializer::parse($inputs, CouponGroupModel::class);

            if (!empty($inputs)) {
                /**
                 * @var $couponGroupModel CouponGroupModel
                 */
                $couponGroupModel = ModelSerializer::parse($inputs, CouponGroupModel::class);
//            dd($couponGroupModel);
                $request = new Req(Servers::Accounting, Accounting::CouponGroup, 'new');
                $request->add_instance($couponGroupModel);
                $response = $request->send();
//            dd($response);
                if ($response->getStatus() == ResponseStatus::successful) {
                    $this->addFlash('s', $response->getMessage());
                    /**
                     * @var $couponGroupModel CouponGroupModel
                     */
                    $couponGroupModel = ModelSerializer::parse($response->getContent(), CouponGroupModel::class);
                    if ($canEdit) {
                        return $this->redirect($this->generateUrl('accounting_coupon_group_edit', ['id' => $couponGroupModel->getCouponGroupId()]));
                    } else {
                        return $this->redirect($this->generateUrl('accounting_coupon_group_list'));
                    }
                } else {
                    $this->addFlash('f', $response->getMessage());
                }
            }


            return $this->render('accounting/coupon_group/create.html.twig', [
                'controller_name' => 'CouponGroupController',
                'couponGroupModel' => $couponGroupModel,
                'canCreate' => $canCreate,
            ]);
        } else {
            return $this->redirect($this->generateUrl('accounting_coupon_group_list'));
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
        $canConfirm = AuthUser::if_is_allowed(ServerPermissions::accounting_coupongroup_confirm);
        $canAddCustomer = AuthUser::if_is_allowed(ServerPermissions::accounting_coupongroup_add_allowed_customer);
        $canRemoveCustomer = AuthUser::if_is_allowed(ServerPermissions::accounting_coupongroup_remove_allowed_customer);

        if (AuthUser::if_is_allowed(ServerPermissions::accounting_coupongroup_fetch)) {


            $inputs = $request->request->all();

            /**
             * @var $couponGroupModel CouponGroupModel
             */
            $couponGroupModel = ModelSerializer::parse($inputs, CouponGroupModel::class);
            $couponGroupModel->setCouponGroupId($id);
            $request = new Req(Servers::Accounting, Accounting::CouponGroup, 'fetch');
            $request->add_instance($couponGroupModel);
            $response = $request->send();
            /**
             * @var $couponGroupModel CouponGroupModel
             */
            $couponGroupModel = ModelSerializer::parse($response->getContent(), CouponGroupModel::class);


            /**
             * @var $usedCoupons UsedCouponModel[]
             */
            $usedPeopleCoupons = [];
            if ($couponGroupModel->getCouponGroupUsedPeople()) {
                foreach ($couponGroupModel->getCouponGroupUsedPeople() as $usedPeopleCoupon) {
                    $usedPeopleCoupons[] = ModelSerializer::parse($usedPeopleCoupon, UsedCouponModel::class);
                }
            }

            $allPersonsRequest = new Req(Servers::Repository, Repository::Person, 'all');
            $allPersonsResponse = $allPersonsRequest->send();

            /**
             * @var $persons PersonModel[]
             */
            $persons = [];
            if ($allPersonsResponse->getContent()) {
                foreach ($allPersonsResponse->getContent() as $person) {
                    $persons[] = ModelSerializer::parse($person, PersonModel::class);
                }
            }

            /**
             * @var $selectedPersons PersonModel[]
             */
            $selectedPersons = [];
            if ($couponGroupModel->getCouponGroupAllowedPeople()) {
                foreach ($couponGroupModel->getCouponGroupAllowedPeople() as $person) {
                    $selectedPersons[] = ModelSerializer::parse($person, PersonModel::class);
                }
            }

//        if (!empty($inputs)) {
//            /**
//             * @var $couponGroupModel CouponGroupModel
//             */
//            $couponGroupModel = ModelSerializer::parse($inputs, CouponGroupModel::class);
//            $couponGroupModel->setCouponGroupId($id);
//            $request = new Req(Servers::Accounting, Accounting::CouponGroup, 'update');
//            $request->add_instance($couponGroupModel);
//            $response = $request->send();
//            if ($response->getStatus() == ResponseStatus::successful) {
//                $this->addFlash('s', $response->getMessage());
//                /**
//                 * @var $couponGroupModel CouponGroupModel
//                 */
//                $couponGroupModel = ModelSerializer::parse($response->getContent(), CouponGroupModel::class);
//                return $this->redirect($this->generateUrl('', ['id' => $couponGroupModel->getCouponGroupId()]));
//            } else {
//                $this->addFlash('s', $response->getMessage());
//            }
//        }

            return $this->render('accounting/coupon_group/edit.html.twig', [
                'controller_name' => 'CouponGroupController',
                'couponGroupModel' => $couponGroupModel,
                'usedPeopleCoupons' => $usedPeopleCoupons,
                'persons' => $persons,
                'selectedPersons' => $selectedPersons,
                'canConfirm' => $canConfirm,
                'canAddCustomer' => $canAddCustomer,
                'canRemoveCustomer' => $canRemoveCustomer,
            ]);
        } else {
            return $this->redirect($this->generateUrl('accounting_coupon_group_list'));
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
        if (AuthUser::if_is_allowed(ServerPermissions::accounting_coupongroup_fetch)) {


            $inputs = $request->request->all();
            /**
             * @var $couponGroupModel CouponGroupModel
             */
            $couponGroupModel = ModelSerializer::parse($inputs, CouponGroupModel::class);
            $couponGroupModel->setCouponGroupId($id);
            $request = new Req(Servers::Accounting, Accounting::CouponGroup, 'fetch');
            $request->add_instance($couponGroupModel);
            $response = $request->send();
            /**
             * @var $couponGroupModel CouponGroupModel
             */
            $couponGroupModel = ModelSerializer::parse($response->getContent(), CouponGroupModel::class);
//            dd($couponGroupModel);

            /**
             * @var $usedCoupons UsedCouponModel[]
             */
            $usedPeopleCoupons = [];
            if ($couponGroupModel->getCouponGroupUsedPeople()) {
                foreach ($couponGroupModel->getCouponGroupUsedPeople() as $usedPeopleCoupon) {
                    $usedPeopleCoupons[] = ModelSerializer::parse($usedPeopleCoupon, UsedCouponModel::class);
                }
            }

            /**
             * @var $selectedPersons PersonModel[]
             */
            $selectedPersons = [];
            if ($couponGroupModel->getCouponGroupAllowedPeople()) {
                foreach ($couponGroupModel->getCouponGroupAllowedPeople() as $person) {
                    $selectedPersons[] = ModelSerializer::parse($person, PersonModel::class);
                }
            }


            return $this->render('accounting/coupon_group/read.html.twig', [
                'controller_name' => 'CouponGroupController',
                'couponGroupModel' => $couponGroupModel,
                'usedPeopleCoupons' => $usedPeopleCoupons,
                'selectedPersons' => $selectedPersons,
            ]);
        } else {
            return $this->redirect($this->generateUrl('accounting_coupon_group_list'));
        }
    }

    /**
     * @Route("/confirm-coupon-group/{coupon_group_id}", name="_confirm_coupon_group")
     * @param $coupon_group_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public
    function confirmCouponGroup($coupon_group_id)
    {
        if (AuthUser::if_is_allowed(ServerPermissions::accounting_coupongroup_confirm)) {

            $couponGroupModel = new CouponGroupModel();
            $couponGroupModel->setCouponGroupId($coupon_group_id);
            $request = new Req(Servers::Accounting, Accounting::CouponGroup, 'confirm');
            $request->add_instance($couponGroupModel);
            $response = $request->send();
            if ($response->getStatus() == ResponseStatus::successful) {
                $this->addFlash('s', $response->getMessage());
                return $this->redirect($this->generateUrl('accounting_coupon_group_edit', ['id' => $coupon_group_id]));
            } else {
                $this->addFlash('s', $response->getMessage());
            }

        } else {
            return $this->redirect($this->generateUrl('accounting_coupon_group_confirm_coupon_group'));
        }
    }

    /**
     * @Route("/reject-coupon-group/{coupon_group_id}", name="_reject_coupon_group")
     * @param $coupon_group_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public
    function rejectCouponGroup($coupon_group_id)
    {
        if (AuthUser::if_is_allowed(ServerPermissions::accounting_coupongroup_stop)) {

            $couponGroupModel = new CouponGroupModel();
            $couponGroupModel->setCouponGroupId($coupon_group_id);
            $request = new Req(Servers::Accounting, Accounting::CouponGroup, 'stop');
            $request->add_instance($couponGroupModel);
            $response = $request->send();
            if ($response->getStatus() == ResponseStatus::successful) {
                $this->addFlash('s', $response->getMessage());
                return $this->redirect($this->generateUrl('accounting_coupon_group_edit', ['id' => $coupon_group_id]));
            } else {
                $this->addFlash('s', $response->getMessage());
            }
        } else {
            return $this->redirect($this->generateUrl('accounting_coupon_group_confirm_coupon_group'));
        }
    }


    /**
     * @Route("/add-person/{coupon_group_id}", name="_add_person")
     * @param $coupon_group_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public
    function addPerson($coupon_group_id, Request $request)
    {
        if (AuthUser::if_is_allowed(ServerPermissions::accounting_coupongroup_add_allowed_customer)) {


            $inputs = $request->request->all();
            /**
             * @var $personModel PersonModel
             */
            $personModel = ModelSerializer::parse($inputs, PersonModel::class);
            $personModel->setCouponGroupId($coupon_group_id);
            $request = new Req(Servers::Accounting, Accounting::CouponGroup, 'add_allowed_customer');
            $request->add_instance($personModel);
            $response = $request->send();
//        dd($response);

            if ($response->getStatus() == ResponseStatus::successful) {
                $this->addFlash('s', $response->getMessage());
            } else {
                $this->addFlash('f', $response->getMessage());
            }
            return $this->redirect($this->generateUrl('accounting_coupon_group_edit', ['id' => $coupon_group_id]));
        } else {
            return $this->redirect($this->generateUrl('accounting_coupon_group_edit', ['id' => $coupon_group_id]));

        }
    }


    /**
     * @Route("/remove-person/{person_id}/{coupon_group_id}", name="_remove_person")
     * @param $person_id
     * @param $coupon_group_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public
    function removePerson($person_id, $coupon_group_id)
    {
        if (AuthUser::if_is_allowed(ServerPermissions::accounting_coupongroup_remove_allowed_customer)) {


            $personModel = new PersonModel();
            $personModel->setId($person_id);
            $personModel->setCouponGroupId($coupon_group_id);
            $request = new Req(Servers::Accounting, Accounting::CouponGroup, 'remove_allowed_customer');
            $request->add_instance($personModel);
            $response = $request->send();
            if ($response->getStatus() == ResponseStatus::successful) {
                $this->addFlash('s', $response->getMessage());
            } else {
                $this->addFlash('f', $response->getMessage());
            }
            return $this->redirect($this->generateUrl('accounting_coupon_group_edit', ['id' => $coupon_group_id]));
        } else {
            return $this->redirect($this->generateUrl('accounting_coupon_group_edit', ['id' => $coupon_group_id]));

        }
    }

    /**
     * @Route("/coupon-group-status/{coupon_group_id}/{machine_name}", name="_status")
     * @param $coupon_group_id
     * @param $machine_name
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public
    function changeAvailability($coupon_group_id, $machine_name)
    {
        if (false) {


            $couponGroupStatusModel = new CouponGroupStatusModel();
            if ($machine_name == 'active') {
                $couponGroupStatusModel->setCouponGroupId($coupon_group_id);
                $couponGroupStatusModel->setCouponGroupStatusMachineName('deactive');
            } else {
                $couponGroupStatusModel->setCouponGroupId($coupon_group_id);
                $couponGroupStatusModel->setCouponGroupStatusMachineName('active');
            }
            $request = new Req(Servers::Accounting, Accounting::CouponGroup, 'change_status');
            $request->add_instance($couponGroupStatusModel);
            $response = $request->send();
//        dd($response);
            if ($response->getStatus() == ResponseStatus::successful) {
                $this->addFlash('s', $response->getMessage());
            } else {
                $this->addFlash('f', $response->getMessage());
            }
            return $this->redirect($this->generateUrl('accounting_coupon_group_list'));
        } else {
            return $this->redirect($this->generateUrl('inventory_deed_inventory_deed_list'));
        }
    }

}
