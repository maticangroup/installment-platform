<?php

namespace App\Controller\Repository;

use Matican\ModelSerializer;
use Matican\Models\Repository\SpecGroupModel;
use Matican\Models\Repository\SpecKeyModel;
use Matican\Authentication\AuthUser;
use Matican\Permissions\ServerPermissions;
use Matican\Core\Entities\Repository;
use Matican\Core\Servers;
use Matican\ResponseStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Matican\Core\Transaction\Request as Req;

/**
 * @Route("/repository/spec-group", name="repository_spec_group")
 */
class SpecGroupController extends AbstractController
{
    /**
     * @Route("/create", name="_repository_spec_group_create")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function create(Request $request)
    {
        $canCreate = AuthUser::if_is_allowed(ServerPermissions::repository_specgroup_new);
        $canCreateSpecKey = AuthUser::if_is_allowed(ServerPermissions::repository_specgroup_add_spec_key);
        $canEdit = AuthUser::if_is_allowed(ServerPermissions::repository_specgroup_fetch);
        $canRemoveSpecKey = AuthUser::if_is_allowed(ServerPermissions::repository_specgroup_remove_spec_key);

        $inputs = $request->request->all();
        /**
         * @var $specGroupModel SpecGroupModel
         */
        $specGroupModel = ModelSerializer::parse($inputs, SpecGroupModel::class);

        /**
         * @var $specKeyModel SpecKeyModel
         */
        $specKeyModel = ModelSerializer::parse($inputs, SpecKeyModel::class);

        if ($canCreate) {
            if (!empty($inputs)) {
                $request = new Req(Servers::Repository, Repository::SpecGroup, 'new');
                $request->add_instance($specGroupModel);
                $response = $request->send();
                if ($response->getStatus() == ResponseStatus::successful) {
                    $this->addFlash('s', $response->getMessage());
                }
                $this->addFlash('f', $response->getMessage());
            }
        }


        $allSpecGroupsRequest = new Req(Servers::Repository, Repository::SpecGroup, 'all');
        $allSpecGroupsResponse = $allSpecGroupsRequest->send();

        /**
         * @var $specGroups SpecGroupModel[]
         */
        $specGroups = [];
        foreach ($allSpecGroupsResponse->getContent() as $specGroup) {
            $specGroups[] = ModelSerializer::parse($specGroup, SpecGroupModel::class);
        }


        return $this->render('repository/spec_group/create.html.twig', [
            'controller_name' => 'SpecGroupController',
            'specGroupModel' => $specGroupModel,
            'specKeyModel' => $specKeyModel,
            'specGroups' => $specGroups,
            'canCreate' => $canCreate,
            'canCreateSpecKey' => $canCreateSpecKey,
            'canEdit' => $canEdit,
            'canRemoveSpecKey' => $canRemoveSpecKey,
        ]);
    }

    /**
     * @Route("/add-spec-key", name="_repository_spec_group_add_spec_key")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function addSpecKey(Request $request)
    {
        $inputs = $request->request->all();
        /**
         * @var $specKeyModel SpecKeyModel
         */
        $specKeyModel = ModelSerializer::parse($inputs, SpecKeyModel::class);

        if (!empty($inputs)) {
            $request = new Req(Servers::Repository, Repository::SpecKey, 'new');
            $request->add_instance($specKeyModel);
            $response = $request->send();
//            dd($response);
            if ($response->getStatus() == ResponseStatus::successful) {
                $this->addFlash('s', $response->getMessage());
                return $this->redirect($this->generateUrl('repository_spec_group_repository_spec_group_create'));
            }
            $this->addFlash('f', $response->getMessage());
        }

        $allSpecGroupsRequest = new Req(Servers::Repository, Repository::SpecGroup, 'all');
        $allSpecGroupsResponse = $allSpecGroupsRequest->send();

        /**
         * @var $specGroups SpecGroupModel[]
         */
        $specGroups = [];
        foreach ($allSpecGroupsResponse->getContent() as $specGroup) {
            $specGroups[] = ModelSerializer::parse($specGroup, SpecGroupModel::class);
        }

        /**
         * @var $specGroupModel SpecGroupModel
         */
        $specGroupModel = ModelSerializer::parse($inputs, SpecGroupModel::class);


        return $this->render('repository/spec_group/create.html.twig', [
            'controller_name' => 'SpecGroupController',
            'specKeyModel' => $specKeyModel,
            'specGroups' => $specGroups,
            'specGroupModel' => $specGroupModel
        ]);
    }

    /**
     * @Route("/spec-group-edit/{id}", name="_repository_spec_group_edit")
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function editSpecGroup($id, Request $request)
    {
        $inputs = $request->request->all();

        /**
         * @var $specGroupModel SpecGroupModel
         */
        $specGroupModel = ModelSerializer::parse($inputs, SpecGroupModel::class);
        $specGroupModel->setSpecGroupID($id);
        $request = new Req(Servers::Repository, Repository::SpecGroup, 'fetch');
        $request->add_instance($specGroupModel);
        $response = $request->send();
        $specGroupModel = ModelSerializer::parse($response->getContent(), SpecGroupModel::class);

        if (!empty($inputs)) {
            $specGroupModel = ModelSerializer::parse($inputs, SpecGroupModel::class);
            $specGroupModel->setSpecGroupID($id);
            $request = new Req(Servers::Repository, Repository::SpecGroup, 'update');
            $request->add_instance($specGroupModel);
            $response = $request->send();
            if ($response->getStatus() == ResponseStatus::successful) {
                $this->addFlash('s', $response->getMessage());
                return $this->redirect($this->generateUrl('repository_spec_group_repository_spec_group_create'));

            } else {
                $this->addFlash('f', $response->getMessage());
            }
        }

        $allSpecGroupsRequest = new Req(Servers::Repository, Repository::SpecGroup, 'all');
        $allSpecGroupsResponse = $allSpecGroupsRequest->send();

        /**
         * @var $specGroups SpecGroupModel[]
         */
        $specGroups = [];
        foreach ($allSpecGroupsResponse->getContent() as $specGroup) {
            $specGroups[] = ModelSerializer::parse($specGroup, SpecGroupModel::class);
        }


        return $this->render('repository/spec_group/edit-spec-group.html.twig', [
            'controller_name' => 'SpecGroupController',
            'specGroupModel' => $specGroupModel,
            'specGroups' => $specGroups,

        ]);
    }


    /**
     * @Route("/spec-key-edit/{key_id}/{group_id}", name="_repository_spec_group_key_edit")
     * @param $key_id
     * @param $group_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function editSpecKey($key_id, $group_id, Request $request)
    {
        $inputs = $request->request->all();

        /**
         * @var $specKeyModel SpecKeyModel
         */
        $specKeyModel = ModelSerializer::parse($inputs, SpecKeyModel::class);
        $specKeyModel->setSpecKeyID($key_id);
        $specKeyModel->setSpecKeySpecGroupID($group_id);
//        dd($specKeyModel);
        $request = new Req(Servers::Repository, Repository::SpecKey, 'fetch');
        $request->add_instance($specKeyModel);
        $response = $request->send();
//        dd($response);
        $specKeyModel = ModelSerializer::parse($response->getContent(), SpecKeyModel::class);

        if (!empty($inputs)) {
            $specKeyModel = ModelSerializer::parse($inputs, SpecKeyModel::class);
            $specKeyModel->setSpecKeyID($key_id);
            $specKeyModel->setSpecKeySpecGroupID($specKeyModel->getSpecKeySpecGroupID());
            $request = new Req(Servers::Repository, Repository::SpecKey, 'update');
            $request->add_instance($specKeyModel);
            $response = $request->send();
            if ($response->getStatus() == ResponseStatus::successful) {
                $this->addFlash('s', $response->getMessage());
            }
            $this->addFlash('f', $response->getMessage());
        }

        $allSpecGroupsRequest = new Req(Servers::Repository, Repository::SpecGroup, 'all');
        $allSpecGroupsResponse = $allSpecGroupsRequest->send();

        /**
         * @var $specGroups SpecGroupModel[]
         */
        $specGroups = [];
        foreach ($allSpecGroupsResponse->getContent() as $specGroup) {
            $specGroups[] = ModelSerializer::parse($specGroup, SpecGroupModel::class);
        }


        return $this->render('repository/spec_group/edit-spec-key.html.twig', [
            'controller_name' => 'SpecGroupController',
            'specKeyModel' => $specKeyModel,
            'specGroups' => $specGroups,

        ]);
    }
}
