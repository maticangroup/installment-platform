<?php

namespace App\Controller\Authentication;

use Matican\Models\Authentication\RoleModel;
use Matican\Models\Authentication\UserModel;
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
 * @Route("/authentication/user", name="authentication_user")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/list", name="_list")
     */
    public function fetchAll()
    {

        $canSeeAll = AuthUser::if_is_allowed(ServerPermissions::authentication_user_all);
        $canChangeRole = AuthUser::if_is_allowed(ServerPermissions::authentication_user_set_role);
        $canSendPassword = AuthUser::if_is_allowed(ServerPermissions::authentication_user_send_password_to_user);

        if (!$canSeeAll) {
            return $this->redirect($this->generateUrl('default'));
        }

        $request = new Req(Servers::Authentication, Authentication::User, 'all');
        $response = $request->send();

        /**
         * @var $users UserModel[]
         */
        $users = [];
        if ($response->getContent()) {
            foreach ($response->getContent() as $user) {
                $users[] = ModelSerializer::parse($user, UserModel::class);
            }
        }

        $allRolesRequest = new Req(Servers::Authentication, Authentication::Role, 'all');
        $allRolesResponse = $allRolesRequest->send();

        /**
         * @var $roles RoleModel[]
         */
        $roles = [];
        if ($allRolesResponse->getContent()) {
            foreach ($allRolesResponse->getContent() as $role) {
                $roles[] = ModelSerializer::parse($role, RoleModel::class);
            }
        }
        if (!AuthUser::if_is_allowed(ServerPermissions::authentication_user_all)) {
            $users = [];
        }

        return $this->render('authentication/user/list.html.twig', [
            'controller_name' => 'UserController',
            'users' => $users,
            'roles' => $roles,
            'canSeeAll' => $canSeeAll,
            'canChangeRole' => $canChangeRole,
            'canSendPassword' => $canSendPassword,
        ]);
    }


    /**
     * @Route("/set-role/{user_id}", name="_set_role")
     * @param $user_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function saveRole($user_id, Request $request)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::authentication_user_set_role)) {
            return $this->redirect($this->generateUrl('authentication_user_list'));
        }
        $inputs = $request->request->all();
        /**
         * @var $roleModel RoleModel
         */
        $roleModel = ModelSerializer::parse($inputs, RoleModel::class);
        $roleModel->setUserId($user_id);
//        dd($roleModel);
        $request = new Req(Servers::Authentication, Authentication::User, 'set_role');
        $request->add_instance($roleModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('authentication_user_list'));
    }

    /**
     * @Route("/send-password/{user_id}", name="_send_password")
     * @param $user_id
     * @param Request $symfonyRequest
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function sendPassword($user_id, Request $symfonyRequest)
    {
        die("ssss");
        $userModel = new UserModel();
        $userModel->setUserId($user_id);
        $request = new Req(Servers::Authentication, Authentication::User, 'send_password_to_user');
        $request->add_instance($userModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('authentication_user_list') .
        ($symfonyRequest->query->has('sc')) ? "?" . $symfonyRequest->query->get('sc') : "");
    }
}
