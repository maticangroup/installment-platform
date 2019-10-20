<?php

namespace App\Controller\Authentication;

use Matican\Models\Authentication\PermissionModel;
use Matican\Models\Authentication\RoleModel;
use Matican\Models\Authentication\ServerPermissionModel;
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
 * @Route("/authentication/role", name="authentication_role")
 */
class RoleController extends AbstractController
{

    /**
     * @Route("/create", name="_create")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function create(Request $request)
    {
        $canCreate = AuthUser::if_is_allowed(ServerPermissions::authentication_role_new);
        $canSeeAll = AuthUser::if_is_allowed(ServerPermissions::authentication_role_all);
        $canUpdate = AuthUser::if_is_allowed(ServerPermissions::authentication_role_update);

        if (!$canCreate) {
            return $this->redirect($this->generateUrl('default'));
        }

        $inputs = $request->request->all();
        /**
         * @var $roleModel RoleModel
         */
        $roleModel = ModelSerializer::parse($inputs, RoleModel::class);

        if (!empty($inputs)) {
            $request = new Req(Servers::Authentication, Authentication::Role, 'new');
            $request->add_instance($roleModel);
            $response = $request->send();
            if ($response->getStatus() == ResponseStatus::successful) {
                $this->addFlash('s', $response->getMessage());
            } else {
                $this->addFlash('f', $response->getMessage());
            }
        }

        /**
         * @var $roles RoleModel[]
         */
        $roles = [];
        $allRolesRequest = new Req(Servers::Authentication, Authentication::Role, 'all');
        $allRolesResponse = $allRolesRequest->send();
        if ($allRolesResponse->getContent()) {
            foreach ($allRolesResponse->getContent() as $role) {
                $roles[] = ModelSerializer::parse($role, RoleModel::class);
            }
        }
        if (!AuthUser::if_is_allowed(ServerPermissions::authentication_role_all)) {
            $roleModel = [];
        }
        /**
         * @todo authorization here should be handled in twig
         */
        return $this->render('authentication/role/create.html.twig', [
            'controller_name' => 'RoleController',
            'roleModel' => $roleModel,
            'roles' => $roles,
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
        $canEdit = AuthUser::if_is_allowed(ServerPermissions::authentication_role_fetch);
        $canUpdate = AuthUser::if_is_allowed(ServerPermissions::authentication_role_update);
        $canAddPermission = AuthUser::if_is_allowed(ServerPermissions::authentication_role_grant_permission);
        $canRemovePermission = AuthUser::if_is_allowed(ServerPermissions::authentication_role_deny_permission);

        if (!$canEdit) {
            return $this->redirect($this->generateUrl('authentication_role_create'));
        }
        $inputs = $request->request->all();
        /**
         * @var $roleModel RoleModel
         */
        $roleModel = ModelSerializer::parse($inputs, RoleModel::class);
        $roleModel->setRoleId($id);
        $request = new Req(Servers::Authentication, Authentication::Role, 'fetch');
        $request->add_instance($roleModel);
        $response = $request->send();
        /**
         * @var $roleModel RoleModel
         */
        $roleModel = ModelSerializer::parse($response->getContent(), RoleModel::class);


        /**
         * @var $selectedPermissions PermissionModel[]
         */
        $selectedPermissions = [];
        if ($roleModel->getRolePermissions()) {
            foreach ($roleModel->getRolePermissions() as $permission) {
//                $selectedPermissions[] = ModelSerializer::parse($permission, PermissionModel::class);
                $selectedPermissions[] = json_decode(json_encode($permission), true);
            }
        }
        $selectedPermissionsActions = [];
        foreach ($selectedPermissions as $permission) {
            $selectedPermissionsActions[] = $permission['permissionMachineName'];
        }

        $allServerPermissionsRequest = new Req(Servers::Authentication, Authentication::PermissionGroup, 'all');
        $allServerPermissionsResponse = $allServerPermissionsRequest->send();

        $serverPermissions = $allServerPermissionsResponse->getContent();
        foreach ($serverPermissions as $key => $serverPermission) {
//            dd($serverPermission);
            $serverPermissions[$key] = json_decode(json_encode($serverPermission), true);
        }

        foreach ($serverPermissions as $serverKey => $serverPermission) {
            foreach ($serverPermission['serverPermissions'] as $permissionKey => $permission) {
                if (!in_array($permission['permissionMachineName'], $selectedPermissionsActions)) {
                    $serverPermissions[$serverKey]['serverPermissions'][$permissionKey]['permissionIsDisabled'] = true;
                } else {
                    $serverPermissions[$serverKey]['serverPermissions'][$permissionKey]['permissionIsDisabled'] = false;

                }

            }
        }


        if (!empty($inputs)) {
            /**
             * @var $roleModel RoleModel
             */
            $roleModel = ModelSerializer::parse($inputs, RoleModel::class);
            $roleModel->setRoleId($id);
            $request = new Req(Servers::Authentication, Authentication::Role, 'update');
            $request->add_instance($roleModel);
            $response = $request->send();
            if ($response->getStatus() == ResponseStatus::successful) {
                $this->addFlash('s', $response->getMessage());
                return $this->redirect($this->generateUrl('authentication_role_edit', ['id' => $id]));
            } else {
                $this->addFlash('f', $response->getMessage());
            }
        }


        return $this->render('authentication/role/edit.html.twig', [
            'controller_name' => 'RoleController',
            'roleModel' => $roleModel,
            'serverPermissions' => $serverPermissions,
            'selectedPermissions' => $selectedPermissions,
            'canEdit' => $canEdit,
            'canUpdate' => $canUpdate,
            'canAddPermission' => $canAddPermission,
            'canRemovePermission' => $canRemovePermission,
        ]);
    }


    /**
     * @Route("/add-permission/{role_id}/{permission_id}", name="_add_permission")
     * @param $role_id
     * @param $permission_id
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function addPermission($role_id, $permission_id)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::authentication_role_grant_permission)) {
            return $this->redirect($this->generateUrl('authentication_role_edit', ['id' => $role_id]));
        }
        $permissionModel = new PermissionModel();
        $permissionModel->setRoleId($role_id);
        $permissionModel->setPermissionId($permission_id);
//        dd($permissionModel);
        $request = new Req(Servers::Authentication, Authentication::Role, 'grant_permission');
        $request->add_instance($permissionModel);
//        dd($request);
        $response = $request->send();
//        dd($response);


        if ($response->getStatus() == ResponseStatus::successful) {
            $rolePermissionRequest = new Req(Servers::Authentication, Authentication::Role, 'get_roles_permissions');
            $response = $rolePermissionRequest->send();
            AuthUser::cachePermissions($response->getContent());
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('authentication_role_edit', ['id' => $role_id]));
    }


    /**
     * @Route("/remove-permission/{permission_id}/{role_id}", name="_remove_permission")
     * @param $permission_id
     * @param $role_id
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function removePermission($permission_id, $role_id)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::authentication_role_deny_permission)) {
            return $this->redirect($this->generateUrl('authentication_role_edit', ['id' => $role_id]));
        }
        $permissionModel = new PermissionModel();
        $permissionModel->setPermissionId($permission_id);
        $permissionModel->setRoleId($role_id);
        $request = new Req(Servers::Authentication, Authentication::Role, 'deny_permission');
        $request->add_instance($permissionModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $rolePermissionRequest = new Req(Servers::Authentication, Authentication::Role, 'get_roles_permissions');
            $response = $rolePermissionRequest->send();
            AuthUser::cachePermissions($response->getContent());
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('authentication_role_edit', ['id' => $role_id]));
    }
}
