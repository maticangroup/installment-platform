<?php

namespace App\Controller\Authentication;

use Matican\Models\Authentication\ClientModel;
use Matican\Models\Authentication\RoleModel;
use Matican\ModelSerializer;
use Matican\Authentication\AuthUser;
use Matican\Permissions\ServerPermissions;
use Matican\Core\Entities\Authentication;
use Matican\Core\Servers;
use Matican\ResponseStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Matican\Core\Transaction\Request as Req;

/**
 * @Route("/authentication/client", name="authentication_client")
 */
class ClientController extends AbstractController
{

    /**
     * @Route("/create", name="_create")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function create(Request $request)
    {

        $canCreate = AuthUser::if_is_allowed(ServerPermissions::authentication_client_new);
        $canSeeAll = AuthUser::if_is_allowed(ServerPermissions::authentication_client_new);
        $canUpdate = AuthUser::if_is_allowed(ServerPermissions::authentication_client_update);

        if (!$canCreate) {
            return $this->redirect($this->generateUrl('default'));
        }

        $inputs = $request->request->all();
        /**
         * @var $clientModel ClientModel
         */
        $clientModel = ModelSerializer::parse($inputs, ClientModel::class);

        if (!empty($inputs)) {
            $request = new Req(Servers::Authentication, Authentication::Client, 'new');
            $request->add_instance($clientModel);
            $response = $request->send();
//            dd($response);
            if ($response->getStatus() == ResponseStatus::successful) {
                $this->addFlash('s', $response->getMessage());
            } else {
                $this->addFlash('f', $response->getMessage());
            }
        }

        /**
         * @var $clients ClientModel[]
         */
        $clients = [];
        $allClientsRequest = new Req(Servers::Authentication, Authentication::Client, 'all');
        $allClientsResponse = $allClientsRequest->send();
//        dd($allClientsResponse);
        if ($allClientsResponse->getContent()) {
            foreach ($allClientsResponse->getContent() as $client) {
                $clients[] = ModelSerializer::parse($client, ClientModel::class);
            }
        }

//        /**
//         * @var $roles RoleModel[]
//         */
//        $roles = [];
//        $allRolesRequest = new Req(Servers::AuthUser, AuthUser::Role, 'all');
//        $allRolesResponse = $allRolesRequest->send();
//        if ($allRolesResponse->getContent()) {
//            foreach ($allRolesResponse->getContent() as $role) {
//                $roles[] = ModelSerializer::parse($role, RoleModel::class);
//            }
//        }

        /**
         * @todo Authorization here should be handled in the twig
         */
        if (!AuthUser::if_is_allowed(ServerPermissions::authentication_client_all)) {
            $clients = [];
        }

        return $this->render('authentication/client/create.html.twig', [
            'controller_name' => 'ClientController',
            'clientModel' => $clientModel,
            'clients' => $clients,
//            'roles' => $roles,
            'canCreate' => $canCreate,
            'canSeeAll' => $canSeeAll,
            'canUpdate' => $canUpdate,
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
        $canSeeAll = AuthUser::if_is_allowed(ServerPermissions::authentication_client_new);
        $canUpdate = AuthUser::if_is_allowed(ServerPermissions::authentication_client_update);

        if (!$canUpdate) {
            return $this->redirect($this->generateUrl('authentication_client_create'));
        }
        $inputs = $request->request->all();
        /**
         * @var $clientModel ClientModel
         */
        $clientModel = ModelSerializer::parse($inputs, ClientModel::class);
        $clientModel->setClientId($id);
        $request = new Req(Servers::Authentication, Authentication::Client, 'fetch');
        $request->add_instance($clientModel);
        $response = $request->send();
        /**
         * @var $clientModel ClientModel
         */
        $clientModel = ModelSerializer::parse($response->getContent(), ClientModel::class);


        /**
         * @var $clients ClientModel[]
         */
        $clients = [];
        $allClientsRequest = new Req(Servers::Authentication, Authentication::Client, 'all');
        $allClientsResponse = $allClientsRequest->send();
        if ($allClientsResponse->getContent()) {
            foreach ($allClientsResponse->getContent() as $client) {
                $clients[] = ModelSerializer::parse($client, ClientModel::class);
            }
        }

//        /**
//         * @var $roles RoleModel[]
//         */
//        $roles = [];
//        $allRolesRequest = new Req(Servers::AuthUser, AuthUser::Role, 'all');
//        $allRolesResponse = $allRolesRequest->send();
//        if ($allRolesResponse->getContent()) {
//            foreach ($allRolesResponse->getContent() as $role) {
//                $roles[] = ModelSerializer::parse($role, RoleModel::class);
//            }
//        }

        if (!empty($inputs)) {
            /**
             * @var $clientModel ClientModel
             */
            $clientModel = ModelSerializer::parse($inputs, ClientModel::class);
            $clientModel->setClientId($id);
            $request = new Req(Servers::Authentication, Authentication::Client, 'update');
            $request->add_instance($clientModel);
            $response = $request->send();
            if ($response->getStatus() == ResponseStatus::successful) {
                $this->addFlash('s', $response->getMessage());
                return $this->redirect($this->generateUrl('authentication_client_edit', ['id' => $id]));
            } else {
                $this->addFlash('f', $response->getMessage());
            }
        }


        return $this->render('authentication/client/edit.html.twig', [
            'controller_name' => 'ClientController',
            'clientModel' => $clientModel,
            'clients' => $clients,
            'roles' => [],
            'canSeeAll' => $canSeeAll,
            'canUpdate' => $canUpdate,
        ]);
    }
}
