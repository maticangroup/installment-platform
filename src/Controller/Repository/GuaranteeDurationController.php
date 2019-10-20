<?php

namespace App\Controller\Repository;

use App\Cache;
use Matican\ModelSerializer;
use Matican\Models\Repository\GuaranteeDurationModel;
use Matican\Models\Repository\GuaranteeDurationStatusModel;
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
 * @Route("/repository/guarantee-duration", name="repository_guarantee_duration")
 */
class GuaranteeDurationController extends AbstractController
{
    /**
     * @Route("/create", name="_repository_guarantee_duration_create")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function create(Request $request)
    {

        $canCreate = AuthUser::if_is_allowed(ServerPermissions::repository_guarantee_add_duration);
        $canSeeAll = AuthUser::if_is_allowed(ServerPermissions::repository_guarantee_get_durations);
        $canEdit = AuthUser::if_is_allowed(ServerPermissions::repository_guarantee_fetch_duration);
        $canChangeStatus = AuthUser::if_is_allowed(ServerPermissions::repository_guarantee_change_duration_status);

        $inputs = $request->request->all();
        /**
         * @var $guaranteeDurationModel GuaranteeDurationModel
         */
        $guaranteeDurationModel = ModelSerializer::parse($inputs, GuaranteeDurationModel::class);

        if (!empty($inputs)) {
            if ($canCreate) {
                $request = new Req(Servers::Repository, Repository::Guarantee, 'add_duration');
                $request->add_instance($guaranteeDurationModel);
                $response = $request->send();
                if ($response->getStatus() == ResponseStatus::successful) {
                    $this->addFlash('s', $response->getMessage());
                }
                $this->addFlash('f', $response->getMessage());
            }
        }

        /**
         * @var $guaranteeDurations GuaranteeDurationModel[]
         */
        $guaranteeDurations = [];
        if ($canSeeAll) {
            $allGuaranteeDurationsRequest = new Req(Servers::Repository, Repository::Guarantee, 'get_durations');
            $allGuaranteeDurationsResponse = $allGuaranteeDurationsRequest->send();
            if ($allGuaranteeDurationsResponse->getContent()) {
                foreach ($allGuaranteeDurationsResponse->getContent() as $guaranteeDuration) {
                    $guaranteeDurations[] = ModelSerializer::parse($guaranteeDuration, GuaranteeDurationModel::class);
                }
            }
        }

        Cache::cache_action(Servers::Repository, Repository::Guarantee, 'all');

        return $this->render('repository/guarantee_duration/create.html.twig', [
            'controller_name' => 'GuaranteeDurationController',
            'guaranteeDurationModel' => $guaranteeDurationModel,
            'guaranteeDurations' => $guaranteeDurations,
            'canCreate' => $canCreate,
            'canSeeAll' => $canSeeAll,
            'canEdit' => $canEdit,
            'canChangeStatus' => $canChangeStatus,
        ]);
    }

    /**
     * @Route("/edit/{id}", name="_repository_guarantee_duration_edit")
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function edit($id, Request $request)
    {
        $canUpdate = AuthUser::if_is_allowed(ServerPermissions::repository_guarantee_update_duration);
        if ($canUpdate) {
            $canChangeStatus = AuthUser::if_is_allowed(ServerPermissions::repository_guarantee_change_duration_status);

            $inputs = $request->request->all();
            /**
             * @var $guaranteeDurationModel GuaranteeDurationModel
             */
            $guaranteeDurationModel = ModelSerializer::parse($inputs, GuaranteeDurationModel::class);
            $guaranteeDurationModel->setGuaranteeDurationID($id);
            $request = new Req(Servers::Repository, Repository::Guarantee, 'fetch_duration');
            $request->add_instance($guaranteeDurationModel);
            $response = $request->send();
            $guaranteeDurationModel = ModelSerializer::parse($response->getContent(), GuaranteeDurationModel::class);

            if (!empty($inputs)) {
                $guaranteeDurationModel = ModelSerializer::parse($inputs, GuaranteeDurationModel::class);
                $guaranteeDurationModel->setGuaranteeDurationID($id);
                $request = new Req(Servers::Repository, Repository::Guarantee, 'update_duration');
                $request->add_instance($guaranteeDurationModel);
                $response = $request->send();
                if ($response->getStatus() == ResponseStatus::successful) {
                    $this->addFlash('s', $response->getMessage());
                } else {
                    $this->addFlash('f', $response->getMessage());
                }

            }

            $allGuaranteeDurationsRequest = new Req(Servers::Repository, Repository::Guarantee, 'get_durations');
            $allGuaranteeDurationsResponse = $allGuaranteeDurationsRequest->send();

            /**
             * @var $guaranteeDurations GuaranteeDurationModel[]
             */
            $guaranteeDurations = [];
            foreach ($allGuaranteeDurationsResponse->getContent() as $guaranteeDuration) {
                $guaranteeDurations[] = ModelSerializer::parse($guaranteeDuration, GuaranteeDurationModel::class);
            }

            Cache::cache_action(Servers::Repository, Repository::Guarantee, 'all');

            return $this->render('repository/guarantee_duration/edit.html.twig', [
                'controller_name' => 'GuaranteeDurationController',
                'guaranteeDurationModel' => $guaranteeDurationModel,
                'guaranteeDurations' => $guaranteeDurations,
                'canChangeStatus' => $canChangeStatus,
            ]);
        } else {
            return $this->redirect($this->generateUrl('repository_guarantee_duration_repository_guarantee_duration_create'));
        }

    }


    /**
     * @Route("/edit/{guarantee_duration_id}/{machine_name}", name="_repository_guarantee_duration_status")
     * @param $guarantee_duration_id
     * @param $machine_name
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function changeGuaranteeDurationAvailability($guarantee_duration_id, $machine_name)
    {
        $guaranteeDurationStatusModel = new GuaranteeDurationStatusModel();
        if ($machine_name == 'active') {
            $guaranteeDurationStatusModel->setGuaranteeDurationId($guarantee_duration_id);
            $guaranteeDurationStatusModel->setGuaranteeDurationStatusMachineName('deactive');
        } else {
            $guaranteeDurationStatusModel->setGuaranteeDurationId($guarantee_duration_id);
            $guaranteeDurationStatusModel->setGuaranteeDurationStatusMachineName('active');
        }

        $request = new Req(Servers::Repository, Repository::Guarantee, 'change_duration_status');
        $request->add_instance($guaranteeDurationStatusModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        Cache::cache_action(Servers::Repository, Repository::Guarantee, 'all');

        return $this->redirect($this->generateUrl('repository_guarantee_duration_repository_guarantee_duration_create'));
    }
}
