<?php

namespace App\Controller\Repository;

use App\Cache;
use Matican\ModelSerializer;
use Matican\Models\Repository\GuaranteeDurationModel;
use Matican\Models\Repository\GuaranteeModel;
use Matican\Models\Repository\GuaranteeProviderModel;
use Matican\Models\Repository\GuaranteeStatusModel;
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
 * @Route("/repository/guarantee", name="repository_guarantee")
 */
class GuaranteeController extends AbstractController
{
    /**
     * @Route("/create", name="_repository_guarantee_create")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function create(Request $request)
    {
        $canCreate = AuthUser::if_is_allowed(ServerPermissions::repository_guarantee_new);
        $canSeeAll = AuthUser::if_is_allowed(ServerPermissions::repository_guarantee_all);
        $canEdit = AuthUser::if_is_allowed(ServerPermissions::repository_guarantee_fetch);
        $canChangeStatus = AuthUser::if_is_allowed(ServerPermissions::repository_guarantee_change_guarantee_status);

        $inputs = $request->request->all();

        /**
         * @var $guaranteeModel GuaranteeModel
         */
        $guaranteeModel = ModelSerializer::parse($inputs, GuaranteeModel::class);

        /**
         * @var $guaranteeProviders GuaranteeProviderModel[]
         */
        $guaranteeProviders = [];

        /**
         * @var $guaranteeDurations GuaranteeDurationModel[]
         */
        $guaranteeDurations = [];

        if (!empty($inputs)) {
            if ($canCreate) {
                $request = new Req(Servers::Repository, Repository::Guarantee, 'new');
                $request->add_instance($guaranteeModel);
                $response = $request->send();
                if ($response->getStatus() == ResponseStatus::successful) {
                    $this->addFlash('s', $response->getMessage());
                } else {
                    $this->addFlash('f', $response->getMessage());
                }
            }
        }
        $allGuaranteeProviderRequest = new Req(Servers::Repository, Repository::Guarantee, 'get_providers');
        $guaranteeProviderResponse = $allGuaranteeProviderRequest->send();
        if ($guaranteeProviderResponse->getContent()) {
            foreach ($guaranteeProviderResponse->getContent() as $guaranteeProvider) {
                $guaranteeProviders[] = ModelSerializer::parse($guaranteeProvider, GuaranteeProviderModel::class);
            }
        }

        $guaranteeDurationRequest = new Req(Servers::Repository, Repository::Guarantee, 'get_durations');
        $guaranteeDurationResponse = $guaranteeDurationRequest->send();
        if ($guaranteeDurationResponse->getContent()) {
            foreach ($guaranteeDurationResponse->getContent() as $guaranteeDuration) {
                $guaranteeDurations[] = ModelSerializer::parse($guaranteeDuration, GuaranteeDurationModel::class);
            }
        }
        /**
         * @var $guarantees GuaranteeModel[]
         */
        $guarantees = [];
        if ($canSeeAll) {
            $guaranteeRequest = new Req(Servers::Repository, Repository::Guarantee, 'all');
            $guaranteeResponse = $guaranteeRequest->send();
            if ($guaranteeResponse->getContent()) {
                foreach ($guaranteeResponse->getContent() as $guarantee) {
                    $guarantees[] = ModelSerializer::parse($guarantee, GuaranteeModel::class);
                }
            }
        }
        Cache::cache_action(Servers::Repository, Repository::Guarantee, 'all');

//        dd($guaranteeDurations);
        return $this->render('repository/guarantee/create.html.twig', [
            'controller_name' => 'GuaranteeController',
            'guaranteeModel' => $guaranteeModel,
            'guaranteeProviders' => $guaranteeProviders,
            'guaranteeDurations' => $guaranteeDurations,
            'guarantees' => $guarantees,
            'canCreate' => $canCreate,
            'canSeeAll' => $canSeeAll,
            'canEdit' => $canEdit,
            'canChangeStatus' => $canChangeStatus,
        ]);
    }


    /**
     * @Route("/edit/{id}", name="_repository_guarantee_edit")
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function edit($id, Request $request)
    {
        $canEdit = AuthUser::if_is_allowed(ServerPermissions::repository_guarantee_fetch);
        $canUpdate = AuthUser::if_is_allowed(ServerPermissions::repository_guarantee_update);
        $canChangeStatus = AuthUser::if_is_allowed(ServerPermissions::repository_guarantee_change_guarantee_status);

        if ($canUpdate) {
            $inputs = $request->request->all();
            /**
             * @var $guaranteeModel GuaranteeModel
             */
            $guaranteeModel = ModelSerializer::parse($inputs, GuaranteeModel::class);
            $guaranteeModel->setGuaranteeID($id);
            $request = new Req(Servers::Repository, Repository::Guarantee, 'fetch');
            $request->add_instance($guaranteeModel);
            $response = $request->send();
            $guaranteeModel = ModelSerializer::parse($response->getContent(), GuaranteeModel::class);

            if (!empty($inputs)) {
                $guaranteeModel = ModelSerializer::parse($inputs, GuaranteeModel::class);
                $guaranteeModel->setGuaranteeID($id);
                $request = new Req(Servers::Repository, Repository::Guarantee, 'update');
                $request->add_instance($guaranteeModel);
                $response = $request->send();
                if ($response->getStatus() == ResponseStatus::successful) {
                    $this->addFlash('s', $response->getMessage());
                    return $this->redirect($this->generateUrl('repository_guarantee_repository_guarantee_create'));
                } else {
                    $this->addFlash('f', $response->getMessage());
                }
            }

            $guaranteeProviderRequest = new Req(Servers::Repository, Repository::Guarantee, 'get_providers');
            $guaranteeProviderResponse = $guaranteeProviderRequest->send();

            /**
             * @var $guaranteeProviders GuaranteeProviderModel[]
             */
            $guaranteeProviders = [];
            foreach ($guaranteeProviderResponse->getContent() as $guaranteeProvider) {
                $guaranteeProviders[] = ModelSerializer::parse($guaranteeProvider, GuaranteeProviderModel::class);
            }

            $guaranteeDurationRequest = new Req(Servers::Repository, Repository::Guarantee, 'get_durations');
            $guaranteeDurationResponse = $guaranteeDurationRequest->send();

            /**
             * @var $guaranteeDurations GuaranteeDurationModel[]
             */
            $guaranteeDurations = [];
            foreach ($guaranteeDurationResponse->getContent() as $guaranteeDuration) {
                $guaranteeDurations[] = ModelSerializer::parse($guaranteeDuration, GuaranteeDurationModel::class);
            }

            $guaranteeRequest = new Req(Servers::Repository, Repository::Guarantee, 'all');
            $guaranteeResponse = $guaranteeRequest->send();

            /**
             * @var $guarantees GuaranteeModel[]
             */
            $guarantees = [];
            foreach ($guaranteeResponse->getContent() as $guarantee) {
                $guarantees[] = ModelSerializer::parse($guarantee, GuaranteeModel::class);
            }
            Cache::cache_action(Servers::Repository, Repository::Guarantee, 'all');

            return $this->render('repository/guarantee/edit.html.twig', [
                'controller_name' => 'GuaranteeController',
                'guaranteeModel' => $guaranteeModel,
                'guaranteeProviders' => $guaranteeProviders,
                'guaranteeDurations' => $guaranteeDurations,
                'guarantees' => $guarantees,
                '$canChangeStatus' => $canChangeStatus,
            ]);
        } else {
            return $this->redirect($this->generateUrl('repository_guarantee_repository_guarantee_create'));
        }


    }


    /**
     * @Route("/status/{guarantee_id}/{machine_name}", name="_repository_guarantee_status")
     * @param $guarantee_id
     * @param $machine_name
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function changeGuaranteeAvailability($guarantee_id, $machine_name)
    {
        $guaranteeStatusModel = new GuaranteeStatusModel();
        if ($machine_name == 'active') {
            $guaranteeStatusModel->setGuaranteeId($guarantee_id);
            $guaranteeStatusModel->setGuaranteeStatusMachineName('deactive');
        } else {
            $guaranteeStatusModel->setGuaranteeId($guarantee_id);
            $guaranteeStatusModel->setGuaranteeStatusMachineName('active');
        }

        $request = new Req(Servers::Repository, Repository::Guarantee, 'change_guarantee_status');
        $request->add_instance($guaranteeStatusModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        Cache::cache_action(Servers::Repository, Repository::Guarantee, 'all');

        return $this->redirect($this->generateUrl('repository_guarantee_repository_guarantee_create'));
    }
}
