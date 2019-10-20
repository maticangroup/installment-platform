<?php

namespace App\Controller\Crm;

use Matican\Models\CRM\CustomerGroupModel;
use Matican\Models\CRM\CustomerGroupStatusModel;
use Matican\ModelSerializer;
use Matican\Models\Repository\PersonModel;
use Matican\Authentication\AuthUser;
use Matican\Permissions\ServerPermissions;
use Matican\Core\Entities\CRM;
use Matican\Core\Entities\Repository;
use Matican\Core\Servers;
use Matican\ResponseStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Matican\Core\Transaction\Request as Req;

/**
 * @Route("/crm/customer-group", name="crm_customer_group")
 */
class CustomerGroupController extends AbstractController
{

    /**
     * @Route("/create", name="_create")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function create(Request $request)
    {
        $canCreate = AuthUser::if_is_allowed(ServerPermissions::crm_customergroup_new);
        $canSeeAll = AuthUser::if_is_allowed(ServerPermissions::crm_customergroup_all);
        $canEdit = AuthUser::if_is_allowed(ServerPermissions::crm_customergroup_fetch);
        $canChangeStatus = AuthUser::if_is_allowed(ServerPermissions::crm_customergroup_set_status);

        $inputs = $request->request->all();
        /**
         * @var $customerGroupModel CustomerGroupModel
         */
        $customerGroupModel = ModelSerializer::parse($inputs, CustomerGroupModel::class);
        if ($canCreate) {
            if (!empty($inputs)) {

                $request = new Req(Servers::CRM, CRM::CustomerGroup, 'new');
                $request->add_instance($customerGroupModel);
                $response = $request->send();

                if ($response->getStatus() == ResponseStatus::successful) {
                    $this->addFlash('s', $response->getMessage());
                } else {
                    $this->addFlash('f', $response->getMessage());
                }
            }
        }

        /**
         * @var $customerGroups CustomerGroupModel[]
         */
        $customerGroups = [];
        if ($canSeeAll) {
            $allCustomerGroupsRequest = new Req(Servers::CRM, CRM::CustomerGroup, 'all');
            $allCustomerGroupsResponse = $allCustomerGroupsRequest->send();
            if ($allCustomerGroupsResponse->getContent()) {
                foreach ($allCustomerGroupsResponse->getContent() as $customerGroup) {
                    $customerGroups[] = ModelSerializer::parse($customerGroup, CustomerGroupModel::class);
                }
            }
        }

        /**
         * @todo Authorization should be handled in twig (DONE)
         */
        return $this->render('crm/customer_group/create.html.twig', [
            'controller_name' => 'CustomerGroupController',
            'customerGroupModel' => $customerGroupModel,
            'customerGroups' => $customerGroups,
            'canCreate' => $canCreate,
            'canSeeAll' => $canSeeAll,
            'canEdit' => $canEdit,
            'canChangeStatus' => $canChangeStatus,
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
        if (!AuthUser::if_is_allowed(ServerPermissions::crm_customergroup_fetch)) {
            return $this->redirect($this->generateUrl('crm_customer_group_create'));
        }

        $canUpdate = AuthUser::if_is_allowed(ServerPermissions::crm_customergroup_update);
        $canAddPerson = AuthUser::if_is_allowed(ServerPermissions::crm_customergroup_add_person);
        $canRemovePerson = AuthUser::if_is_allowed(ServerPermissions::crm_customergroup_remove_person);

        $inputs = $request->request->all();
        /**
         * @var $customerGroupModel CustomerGroupModel
         */
        $customerGroupModel = ModelSerializer::parse($inputs, CustomerGroupModel::class);
        $customerGroupModel->setCustomerGroupId($id);
        $request = new Req(Servers::CRM, CRM::CustomerGroup, 'fetch');
        $request->add_instance($customerGroupModel);
        $response = $request->send();

        /**
         * @var $customerGroupModel CustomerGroupModel
         */
        $customerGroupModel = ModelSerializer::parse($response->getContent(), CustomerGroupModel::class);


        /**
         * @var $persons PersonModel[]
         */
        $persons = [];
        $allPersonsRequest = new Req(Servers::Repository, Repository::Person, 'all');
        $allPersonsResponse = $allPersonsRequest->send();
        if ($allPersonsResponse->getContent()) {
            foreach ($allPersonsResponse->getContent() as $person) {
                $persons[] = ModelSerializer::parse($person, PersonModel::class);
            }
        }

        /**
         * @var $selectedPersons PersonModel[]
         */
        $selectedPersons = [];
        if ($customerGroupModel->getCustomerGroupPersons()) {
            foreach ($customerGroupModel->getCustomerGroupPersons() as $person) {
                $selectedPersons[] = ModelSerializer::parse($person, PersonModel::class);
            }
        }

        if ($canUpdate) {
            if (!empty($inputs)) {
                /**
                 * @var $customerGroupModel CustomerGroupModel
                 */
                $customerGroupModel = ModelSerializer::parse($inputs, CustomerGroupModel::class);
                $customerGroupModel->setCustomerGroupId($id);
                $request = new Req(Servers::CRM, CRM::CustomerGroup, 'update');
                $request->add_instance($customerGroupModel);
                $response = $request->send();
                if ($response->getStatus() == ResponseStatus::successful) {
                    $this->addFlash('s', $response->getMessage());
                    return $this->redirect($this->generateUrl('crm_customer_group_edit', ['id' => $id]));
                } else {
                    $this->addFlash('f', $response->getMessage());
                }
            }
        }


        return $this->render('crm/customer_group/edit.html.twig', [
            'controller_name' => 'CustomerGroupController',
            'customerGroupModel' => $customerGroupModel,
            'persons' => $persons,
            'selectedPersons' => $selectedPersons,
            'canUpdate' => $canUpdate,
            'canAddPerson' => $canAddPerson,
            'canRemovePerson' => $canRemovePerson
        ]);
    }


    /**
     * @Route("/add-customer/{customer_group_id}", name="_add_customer")
     * @param $customer_group_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function addCustomer($customer_group_id, Request $request)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::crm_customergroup_add_person)) {
            return $this->redirect($this->generateUrl('crm_customer_group_edit', ['id' => $customer_group_id]));
        }
        $inputs = $request->request->all();
        /**
         * @var $personModel PersonModel
         */
        $personModel = ModelSerializer::parse($inputs, PersonModel::class);
        $personModel->setCustomerGroupId($customer_group_id);

        $request = new Req(Servers::CRM, CRM::CustomerGroup, 'add_person');
        $request->add_instance($personModel);
        $response = $request->send();

        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('crm_customer_group_edit', ['id' => $customer_group_id]));
    }

    /**
     * @Route("/remove-customer/{person_id}/{customer_group_id}", name="_remove_customer")
     * @param $person_id
     * @param $customer_group_id
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function removeCustomer($person_id, $customer_group_id)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::crm_customergroup_remove_person)) {
            return $this->redirect($this->generateUrl('crm_customer_group_edit', ['id' => $customer_group_id]));
        }
        $personModel = new PersonModel();
        $personModel->setId($person_id);
        $personModel->setCustomerGroupId($customer_group_id);
        $request = new Req(Servers::CRM, CRM::CustomerGroup, 'remove_person');
        $request->add_instance($personModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('crm_customer_group_edit', ['id' => $customer_group_id]));
    }

    /**
     * @Route("/status/{customer_group_id}/{machine_name}", name="_status")
     * @param $customer_group_id
     * @param $machine_name
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function changeCustomerGroupAvailability($customer_group_id, $machine_name)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::crm_customergroup_set_status)) {
            return $this->redirect($this->generateUrl('crm_customer_group_create'));
        }
        $customerGroupStatusModel = new CustomerGroupStatusModel();
        if ($machine_name == 'active') {
            $customerGroupStatusModel->setCustomerGroupId($customer_group_id);
            $customerGroupStatusModel->setCustomerGroupStatusMachineName('deactive');
        } else {
            $customerGroupStatusModel->setCustomerGroupId($customer_group_id);
            $customerGroupStatusModel->setCustomerGroupStatusMachineName('active');
        }

        $request = new Req(Servers::CRM, CRM::CustomerGroup, 'set_status');
        $request->add_instance($customerGroupStatusModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('crm_customer_group_create'));
    }
}
