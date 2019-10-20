<?php

namespace App\Controller\Repository;

use App\Controller\General\LocationViewController;
use Matican\ModelSerializer;
use Matican\Models\Repository\CompanyAddEmployeeModel;
use Matican\Models\Repository\CompanyModel;
use Matican\Models\Repository\LocationModel;
use Matican\Models\Repository\PersonModel;
use Matican\Models\Repository\PhoneModel;
use Matican\Models\Repository\ProvinceModel;
use Matican\Authentication\AuthUser;
use Matican\Permissions\ServerPermissions;
use Matican\Actions\Repository\PersonActions;
use Matican\Core\Entities\Repository;
use Matican\Core\Servers;
use Matican\ResponseStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Matican\Core\Transaction\Request as Req;

/**
 * @Route("/repository/company", name="repository_company")
 */
class CompanyController extends AbstractController
{
    /**
     * @Route("/list", name="_repository_company_list")
     */
    public function fetchAll()
    {
        $canSeeAll = AuthUser::if_is_allowed(ServerPermissions::repository_company_all);
        $canCreate = AuthUser::if_is_allowed(ServerPermissions::repository_company_new);
        $canEdit = AuthUser::if_is_allowed(ServerPermissions::repository_company_fetch);

        /**
         * @var $results CompanyModel[]
         */
        $results = [];
        if ($canSeeAll) {
            $request = new Req(Servers::Repository, Repository::Company, 'all');
            $response = $request->send();

            if ($response->getContent()) {
                foreach ($response->getContent() as $company) {
                    $results[] = ModelSerializer::parse($company, CompanyModel::class);
                }
            }
        }

        return $this->render('repository/company/list.html.twig', [
            'controller_name' => 'CompanyController',
            'companies' => $results,
            'canSeeAll' => $canSeeAll,
            'canCreate' => $canCreate,
            'canEdit' => $canEdit,
        ]);
    }

    /**
     * @Route("/create", name="_repository_company_create")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function create(Request $request)
    {
        $canCreate = AuthUser::if_is_allowed(ServerPermissions::repository_company_new);

        if ($canCreate) {

            $canEdit = AuthUser::if_is_allowed(ServerPermissions::repository_company_fetch);

            $inputs = $request->request->all();
            /**
             * @var $company CompanyModel
             */
            $company = ModelSerializer::parse($inputs, CompanyModel::class);


            if (!empty($inputs)) {
                $request = new Req(Servers::Repository, Repository::Company, 'new');
                $request->add_instance($company);
                $response = $request->send();
                if ($response->getStatus() == ResponseStatus::successful) {
                    /**
                     * @var $newCompany CompanyModel
                     */
                    $newCompany = ModelSerializer::parse($response->getContent(), CompanyModel::class);
                    $this->addFlash('s', $response->getMessage());
                    if ($canEdit) {
                        return $this->redirect($this->generateUrl('repository_company_repository_company_edit', ['id' => $newCompany->getCompanyID()]));
                    } else {
                        return $this->redirect($this->generateUrl('repository_company_repository_company_list'));
                    }
                } else {
                    $this->addFlash('f', $response->getMessage());
                }
            }


            return $this->render('repository/company/create.html.twig', [
                'controller_name' => 'CompanyController',
                'company' => $company,
                'canCreate' => $canCreate,
            ]);
        } else {
            return $this->redirect($this->generateUrl('repository_company_repository_company_list'));
        }

    }

    /**
     * @Route("/edit/{id}", name="_repository_company_edit")
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function edit($id, Request $request)
    {
        $canUpdate = AuthUser::if_is_allowed(ServerPermissions::repository_company_update);
        $canAddEmployee = AuthUser::if_is_allowed(ServerPermissions::repository_company_add_employee);
        $canRemoveEmployee = AuthUser::if_is_allowed(ServerPermissions::repository_company_remove_employee);
        $canAddPhone = AuthUser::if_is_allowed(ServerPermissions::repository_company_add_phone);
        $canRemovePhone = AuthUser::if_is_allowed(ServerPermissions::repository_company_remove_phone);
        $canAddLocation = AuthUser::if_is_allowed(ServerPermissions::repository_company_add_location);
        $canRemoveLocation = AuthUser::if_is_allowed(ServerPermissions::repository_company_remove_location);

        $inputs = $request->request->all();
        /**
         * @var $companyModel CompanyModel
         */
        $companyModel = ModelSerializer::parse($inputs, CompanyModel::class);
        $companyModel->setCompanyID($id);
        $request = new Req(Servers::Repository, Repository::Company, 'fetch');
        $request->add_instance($companyModel);
        $response = $request->send();
        $companyModel = ModelSerializer::parse($response->getContent(), CompanyModel::class);

        /**
         * @var $employees PersonModel[]
         */
        $employees = [];

        /**
         * @var $personResults PersonModel[]
         */
        $personResults = [];
        $personsRequest = new Req(Servers::Repository, Repository::Person, PersonActions::all);
        $personsResponse = $personsRequest->send();
        if ($personsResponse->getContent()) {
            foreach ($personsResponse->getContent() as $person) {
                $personResults[] = ModelSerializer::parse($person, PersonModel::class);
            }
        }


        if ($companyModel->getCompanyEmployees()) {
            foreach ($companyModel->getCompanyEmployees() as $employee) {
                $employees[] = ModelSerializer::parse($employee, PersonModel::class);
            }
        }


        /**
         * @var $phones PhoneModel[]
         */
        $phones = [];
        if ($canAddPhone) {
            if ($companyModel->getCompanyPhones()) {
                foreach ($companyModel->getCompanyPhones() as $phone) {
                    $phones[] = ModelSerializer::parse($phone, PhoneModel::class);
                }
            }
        }

        /**
         * @var $provinces ProvinceModel[]
         */
        $provinces = [];

        $locationModel = new LocationModel();

        if (!empty($inputs)) {
            if (isset($inputs['provinceName'])) {
                if ($canAddLocation) {
                    /**
                     * @var $locationModel LocationModel
                     */
                    $locationModel = ModelSerializer::parse($inputs, LocationModel::class);
                    $locationModel->setCompanyId($id);
                    return $this->forward(LocationViewController::class . '::addLocation', [
                        'locationModel' => $locationModel,
                        'redirectCallBack' =>
                            $this->generateUrl('repository_company_repository_company_edit', ['id' => $id])]);
                }
            } else {
                if ($canUpdate) {
                    $companyModel = ModelSerializer::parse($inputs, CompanyModel::class);
                    $companyModel->setCompanyID($id);
                    $request = new Req(Servers::Repository, Repository::Company, 'update');
                    $request->add_instance($companyModel);
                    $response = $request->send();
                    if ($response->getStatus() == ResponseStatus::successful) {
                        $this->addFlash('s', $response->getMessage());
                    } else {
                        $this->addFlash('f', $response->getMessage());
                    }
                }
            }
        }


//        dd($companyModel->getCompanyAddresses());

        /**
         * @var $locations LocationModel[]
         */
        $locations = [];
        if ($companyModel->getCompanyAddresses()) {
            foreach ($companyModel->getCompanyAddresses() as $location) {
                $locations[] = ModelSerializer::parse($location, LocationModel::class);
            }
        }


        return $this->render('repository/company/edit.html.twig', [
            'controller_name' => 'CompanyController',
            'company' => $companyModel,
            'employees' => $employees,
            'persons' => $personResults,
            'phones' => $phones,
            'canUpdate' => $canUpdate,
            'canAddEmployee' => $canAddEmployee,
            'canRemoveEmployee' => $canRemoveEmployee,
            'canAddPhone' => $canAddPhone,
            'canRemovePhone' => $canRemovePhone,
            'canAddLocation' => $canAddLocation,
            'canRemoveLocation' => $canRemoveLocation,
            'provinces' => $provinces,
            'locations' => $locations,
            'locationModel' => $locationModel,
        ]);
    }

    /**
     * @Route("/read", name="_repository_company_read")
     */
    public function read()
    {
        return $this->render('repository/company/read.html.twig', [
            'controller_name' => 'CompanyController',
        ]);
    }

    /**
     * @Route("/add-employee/{company_id}", name="_repository_company_add_employee")
     * @param $company_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function addEmployee($company_id, Request $request)
    {
        $inputs = $request->request->all();
        /**
         * @var $employeeModel CompanyAddEmployeeModel
         */
        $employeeModel = ModelSerializer::parse($inputs, CompanyAddEmployeeModel::class);
        $employeeModel->setCompanyID($company_id);
        $request = new Req(Servers::Repository, Repository::Company, 'add_employee');
        $request->add_instance($employeeModel);
        $response = $request->send();
//        dd($response);

        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('repository_company_repository_company_edit', ['id' => $company_id]));
    }

    /**
     * @Route("/remove-employee/{company_id}/{employee_id}", name="_repository_company_remove_employee")
     * @param $company_id
     * @param $employee_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function removeEmployee($company_id, $employee_id)
    {
        $employeeModel = new CompanyAddEmployeeModel();
        $employeeModel->setCompanyID($company_id);
        $employeeModel->setEmployeeID($employee_id);
        $request = new Req(Servers::Repository, Repository::Company, 'remove_employee');
        $request->add_instance($employeeModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('repository_company_repository_company_edit', ['id' => $company_id]));
    }

    /**
     * @Route("/add-phone/{company_id}", name="_repository_company_add_phone")
     * @param $company_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function addPhone($company_id, Request $request)
    {
        $inputs = $request->request->all();
        /**
         * @var $employeeModel CompanyAddEmployeeModel
         */
        $phoneModel = ModelSerializer::parse($inputs, PhoneModel::class);
        $phoneModel->setCompanyID($company_id);
        $request = new Req(Servers::Repository, Repository::Company, 'add_phone');
        $request->add_instance($phoneModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('repository_company_repository_company_edit', ['id' => $company_id]));
    }

    /**
     * @Route("/remove-phone/{company_id}/{phone_id}", name="_repository_company_remove_phone")
     * @param $company_id
     * @param $phone_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function removePhone($company_id, $phone_id)
    {
        $phoneModel = new PhoneModel();
        $phoneModel->setCompanyID($company_id);
        $phoneModel->setId($phone_id);
        $request = new Req(Servers::Repository, Repository::Company, 'remove_phone');
        $request->add_instance($phoneModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('repository_company_repository_company_edit', ['id' => $company_id]));
    }


    /**
     * @Route("/remove-address/{location_id}/{company_id}", name="_repository_company_remove_address")
     * @param $location_id
     * @param $company_id
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function removeAddress($location_id, $company_id)
    {
        $locationModel = new LocationModel();
        $locationModel->setLocationId($location_id);
        $locationModel->setCompanyId($company_id);
        $request = new Req(Servers::Repository, Repository::Location, 'remove');
        $request->add_instance($locationModel);
        $response = $request->send();


        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }


        return $this->redirect($this->generateUrl('repository_company_repository_company_edit', ['id' => $company_id]));
    }
}
