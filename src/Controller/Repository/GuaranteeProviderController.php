<?php

namespace App\Controller\Repository;

use App\Cache;
use Matican\ModelSerializer;
use Matican\Models\Repository\GuaranteeProviderModel;
use Matican\Models\Repository\GuaranteeProviderStatusModel;
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
 * @Route("/repository/guarantee-provider", name="repository_guarantee_provider")
 */
class GuaranteeProviderController extends AbstractController
{

    /**
     * @Route("/create", name="_repository_guarantee_provider_create")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function create(Request $request)
    {
        $canCreate = AuthUser::if_is_allowed(ServerPermissions::repository_guarantee_add_provider);
        $canEdit = AuthUser::if_is_allowed(ServerPermissions::repository_guarantee_fetch_provider);
        $canSeeAll = AuthUser::if_is_allowed(ServerPermissions::repository_guarantee_get_providers);
        $canChangeStatus = AuthUser::if_is_allowed(ServerPermissions::repository_guarantee_change_provider_status);

        $inputs = $request->request->all();
        /**
         * @var $guaranteeProviderModel GuaranteeProviderModel
         */
        $guaranteeProviderModel = ModelSerializer::parse($inputs, GuaranteeProviderModel::class);

        if (!empty($inputs)) {
            if ($canCreate) {
                $request = new Req(Servers::Repository, Repository::Guarantee, 'add_provider');
                $request->add_instance($guaranteeProviderModel);
                $response = $request->send();
                if ($response->getStatus() == ResponseStatus::successful) {
                    $this->addFlash('s', $response->getMessage());
                } else {
                    $this->addFlash('f', $response->getMessage());
                }
            }
        }


        /**
         * @var $guaranteeProviders GuaranteeProviderModel[]
         */
        $guaranteeProviders = [];
        if ($canSeeAll) {
            $allGuaranteeProviderRequest = new Req(Servers::Repository, Repository::Guarantee, 'get_providers');
            $allGuaranteeProviderResponse = $allGuaranteeProviderRequest->send();
            if ($allGuaranteeProviderResponse->getContent()) {
                foreach ($allGuaranteeProviderResponse->getContent() as $guaranteeProvider) {
                    $guaranteeProviders[] = ModelSerializer::parse($guaranteeProvider, GuaranteeProviderModel::class);
                }
            }
        }
        Cache::cache_action(Servers::Repository, Repository::Guarantee, 'all');

        return $this->render('repository/guarantee_provider/create.html.twig', [
            'controller_name' => 'GuaranteeProviderController',
            'guaranteeProviderModel' => $guaranteeProviderModel,
            'guaranteeProviders' => $guaranteeProviders,
            'canCreate' => $canCreate,
            'canEdit' => $canEdit,
            'canSeeAll' => $canSeeAll,
            'canChangeStatus' => $canChangeStatus,

        ]);
    }


    /**
     * @Route("/edit/{id}", name="_repository_guarantee_provider_edit")
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function edit($id, Request $request)
    {
        $canUpdate = AuthUser::if_is_allowed(ServerPermissions::repository_guarantee_update_provider);
        if ($canUpdate) {
            $canChangeStatus = AuthUser::if_is_allowed(ServerPermissions::repository_guarantee_change_provider_status);

            $inputs = $request->request->all();

            /**
             * @var $guaranteeProviderModel GuaranteeProviderModel
             */
            $guaranteeProviderModel = ModelSerializer::parse($inputs, GuaranteeProviderModel::class);
            $guaranteeProviderModel->setGuaranteeProviderID($id);
            $request = new Req(Servers::Repository, Repository::Guarantee, 'fetch_provider');
            $request->add_instance($guaranteeProviderModel);
            $response = $request->send();
            $guaranteeProviderModel = ModelSerializer::parse($response->getContent(), GuaranteeProviderModel::class);

            if (!empty($inputs)) {
                $guaranteeProviderModel = ModelSerializer::parse($inputs, GuaranteeProviderModel::class);
                $guaranteeProviderModel->setGuaranteeProviderID($id);
                $request = new Req(Servers::Repository, Repository::Guarantee, 'update_provider');
                $request->add_instance($guaranteeProviderModel);
                $response = $request->send();
                if ($response->getStatus() == ResponseStatus::successful) {
                    $this->addFlash('s', $response->getMessage());
                } else {
                    $this->addFlash('f', $response->getMessage());
                }
            }

            $allGuaranteeProviderRequest = new Req(Servers::Repository, Repository::Guarantee, 'get_providers');
            $allGuaranteeProviderResponse = $allGuaranteeProviderRequest->send();

            /**
             * @var $guaranteeProviders GuaranteeProviderModel[]
             */
            $guaranteeProviders = [];
            foreach ($allGuaranteeProviderResponse->getContent() as $guaranteeProvider) {
                $guaranteeProviders[] = ModelSerializer::parse($guaranteeProvider, GuaranteeProviderModel::class);
            }

            Cache::cache_action(Servers::Repository, Repository::Guarantee, 'all');

            return $this->render('repository/guarantee_provider/edit.html.twig', [
                'controller_name' => 'GuaranteeProviderController',
                'guaranteeProviderModel' => $guaranteeProviderModel,
                'guaranteeProviders' => $guaranteeProviders,
                'canChangeStatus' => $canChangeStatus,

            ]);
        } else {
            return $this->redirect($this->generateUrl('repository_guarantee_provider_repository_guarantee_provider_create'));
        }

    }


    /**
     * @Route("/edit/{guarantee_provider_id}/{machine_name}", name="_repository_guarantee_provider_status")
     * @param $guarantee_provider_id
     * @param $machine_name
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function changeGuaranteeProviderAvailability($guarantee_provider_id, $machine_name)
    {
        $guaranteeProviderStatusModel = new GuaranteeProviderStatusModel();
        if ($machine_name == 'active') {
            $guaranteeProviderStatusModel->setGuaranteeProviderId($guarantee_provider_id);
            $guaranteeProviderStatusModel->setGuaranteeProviderStatusMachineName('deactive');
        } else {
            $guaranteeProviderStatusModel->setGuaranteeProviderId($guarantee_provider_id);
            $guaranteeProviderStatusModel->setGuaranteeProviderStatusMachineName('active');
        }

        $request = new Req(Servers::Repository, Repository::Guarantee, 'change_provider_status');
        $request->add_instance($guaranteeProviderStatusModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        Cache::cache_action(Servers::Repository, Repository::Guarantee, 'all');

        return $this->redirect($this->generateUrl('repository_guarantee_provider_repository_guarantee_provider_create'));
    }
}
