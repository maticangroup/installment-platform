<?php

namespace App\Controller\Repository;

use App\Controller\General\LocationViewController;
use Matican\ModelSerializer;
use Matican\Models\Repository\LocationModel;
use Matican\Models\Repository\PersonModel;
use Matican\Models\Repository\ProvinceModel;
use Matican\Authentication\AuthUser;
use Matican\Permissions\ServerPermissions;
use Matican\Actions\Repository\PersonActions;
use Matican\Core\Entities\Repository;
use Matican\Core\Servers;
use Matican\Core\Transaction\Method;
use Matican\Core\Transaction\Request as Req;
use Matican\ResponseStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/repository/person", name="repository_person")
 */
class PersonController extends AbstractController
{
    /**
     * @Route("/list", name="_repository_person_list")
     */
    public function fetchAll()
    {
        $canSeeAll = AuthUser::if_is_allowed(ServerPermissions::repository_person_all);
        $canEdit = AuthUser::if_is_allowed(ServerPermissions::repository_person_fetch);

        if ($canSeeAll) {
            $request = new Req(Servers::Repository, Repository::Person, PersonActions::all);
            $response = $request->send();
            $persons = $response->getContent();
            /**
             * @var $results PersonModel[]
             */
            $results = [];
            foreach ($persons as $person) {
                $results[] = ModelSerializer::parse($person, PersonModel::class);
            }
            /**
             * @var $person PersonModel
             */
            return $this->render('repository/person/list.html.twig', [
                'controller_name' => 'PersonController',
                'persons' => $results,
                'canSeeAll' => $canSeeAll,
                'canEdit' => $canEdit,
            ]);
        } else {
            return $this->redirect($this->generateUrl('repository_person_repository_person_create'));
        }

    }

    /**
     * @Route("/create", name="_repository_person_create")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function create(Request $request)
    {
        $canCreate = AuthUser::if_is_allowed(ServerPermissions::repository_person_new);

        if ($canCreate) {
            $canEdit = AuthUser::if_is_allowed(ServerPermissions::repository_person_fetch);

            $inputs = $request->request->all();
            /**
             * @var $person PersonModel
             */
            $person = ModelSerializer::parse($inputs, PersonModel::class);


//            $years = [];
//            for ($i = 1950; $i <= 2019; $i++) {
//                $years[] = $i;
//            }
//
//            $months = [];
//            for ($j = 1; $j <= 12; $j++) {
//                $months[] = $j;
//            }
//
//            $days = [];
//            for ($k = 1; $k <= 31; $k++) {
//                $days[] = $k;
//            }

            if (!empty($inputs)) {
                /**
                 * @var $person PersonModel
                 */
                $person = ModelSerializer::parse($inputs, PersonModel::class);
//                dd($person);
                $request = new Req(Servers::Repository, Repository::Person, PersonActions::new);
                $request->add_instance($person);
                $response = $request->send();

                if ($response->getStatus() == ResponseStatus::successful) {
                    $this->addFlash('s', $response->getMessage());
                    /**
                     * @var $responsePerson PersonModel
                     */
                    $responsePerson = ModelSerializer::parse($response->getContent(), PersonModel::class);
                    if ($canEdit) {
                        return $this->redirect($this->generateUrl('repository_person_repository_person_edit', ['id' => $responsePerson->getId()]));
                    } else {
                        return $this->redirect($this->generateUrl('repository_person_repository_person_list'));
                    }
                } else {
                    $this->addFlash('f', $response->getMessage());
                }
            }


            return $this->render('repository/person/create.html.twig', [
                'controller_name' => 'PersonController',
//                'years' => $years,
//                'months' => $months,
//                'days' => $days,
                'person' => $person,
                'canCreate' => $canCreate,
            ]);
        } else {
            return $this->redirect($this->generateUrl('repository_person_repository_person_list'));
        }


    }

    /**
     * @Route("/quick_save", name="_repository_person_quick_save")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function quickSave(Request $request)
    {
        $inputs = $request->request->all();

        $person = new PersonModel();
        $person->setHumanName($inputs['person_name']);
        $person->setHumanFamily($inputs['person_family']);
        $person->setMobile($inputs['person_mobile']);

        $request = new Req(Servers::Repository, Repository::Person, PersonActions::quick_register);
        $request->setMethod(Method::POST);
        $request->add_instance($person);
        $response = $request->send();

        if ($response->getStatus() == ResponseStatus::record_added_successfully) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }

        $referer = $_SERVER['HTTP_REFERER'];
        return $this->redirect($referer);
    }

    /**
     * @Route("/edit/{id}", name="_repository_person_edit")
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function edit($id, Request $request)
    {
//        $canUpdate = AuthUser::if_is_allowed(ServerPermissions::repository_person_update);

        $inputs = $request->request->all();

//        $years = [];
//        for ($i = 1950; $i <= 2019; $i++) {
//            $years[] = $i;
//        }
//
//        $months = [];
//        for ($j = 1; $j <= 12; $j++) {
//            $months[] = $j;
//        }
//
//        $days = [];
//        for ($k = 1; $k <= 31; $k++) {
//            $days[] = $k;
//        }

        /**
         * @todo create person edit function
         */

        /**
         * @var $personModel PersonModel
         */
        $personModel = ModelSerializer::parse($inputs, PersonModel::class);
        $personModel->setId($id);
        $request = new Req(Servers::Repository, Repository::Person, 'fetch');
        $request->add_instance($personModel);
        $response = $request->send();
        /**
         * @var $personModel PersonModel
         */
        $personModel = ModelSerializer::parse($response->getContent(), PersonModel::class);


        $locationModel = new LocationModel();

        if (!empty($inputs)) {
            if (isset($inputs['provinceName'])) {
                /**
                 * @var $locationModel LocationModel
                 */
                $locationModel = ModelSerializer::parse($inputs, LocationModel::class);
                $locationModel->setPersonId($id);
                return $this->forward(LocationViewController::class . '::addLocation', [
                    'locationModel' => $locationModel,
                    'redirectCallBack' =>
                        $this->generateUrl('repository_person_repository_person_edit',
                            ['id' => $locationModel->getPersonId()]
                        )]);

            } else {
                $personModel = ModelSerializer::parse($inputs, PersonModel::class);
                $personModel->setId($id);
                $request = new Req(Servers::Repository, Repository::Person, 'update');
                $request->add_instance($personModel);
                $response = $request->send();
                if ($response->getStatus() == ResponseStatus::successful) {
                    $this->addFlash('s', $response->getMessage());
                    return $this->redirect($this->generateUrl('repository_person_repository_person_edit', ['id' => $id]));
                } else {
                    $this->addFlash('f', $response->getMessage());
                }
            }
        }

        /**
         * @var $locations LocationModel[]
         */
        $locations = [];
        if ($personModel->getLocations()) {
            foreach ($personModel->getLocations() as $location) {
                $locations[] = ModelSerializer::parse($location, LocationModel::class);
            }
        }


        $provincesRequest = new Req(Servers::Repository, Repository::Location, 'get_provinces');
        $provincesResponse = $provincesRequest->send();

        /**
         * @var $provinces ProvinceModel[]
         */
        $provinces = [];
        if ($provincesResponse->getContent()) {
            foreach ($provincesResponse->getContent() as $province) {
                $provinces[] = ModelSerializer::parse($province, ProvinceModel::class);
            }
        }

        $personsRequest = new Req(Servers::Repository, Repository::Person, 'all');
        $personsResponse = $personsRequest->send();

        /**
         * @var $persons PersonModel[]
         */
        $persons = [];
        if ($personsResponse->getContent()) {
            foreach ($personsResponse->getContent() as $person) {
                $persons[] = ModelSerializer::parse($person, PersonModel::class);
            }
        }


        return $this->render('repository/person/edit.html.twig', [
            'controller_name' => 'PersonController',
//            'years' => $years,
//            'months' => $months,
//            'days' => $days,
            'personModel' => $personModel,
            'locations' => $locations,
            'provinces' => $provinces,
            'persons' => $persons,
            'locationModel' => $locationModel,
        ]);

    }

    /**
     * @Route("/save-address/{person_id}", name="_repository_person_save_address")
     * @param $person_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function addAddress($person_id, Request $request)
    {

    }

    /**
     * @Route("/remove-address/{location_id}/{person_id}", name="_repository_person_remove_address")
     * @param $location_id
     * @param $person_id
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function removeAddress($location_id, $person_id)
    {
        $locationModel = new LocationModel();
        $locationModel->setLocationId($location_id);
        $locationModel->setPersonId($person_id);
        $request = new Req(Servers::Repository, Repository::Location, 'remove');
        $request->add_instance($locationModel);
        $response = $request->send();


        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }


        return $this->redirect($this->generateUrl('repository_person_repository_person_edit', ['id' => $person_id]));
    }
}
