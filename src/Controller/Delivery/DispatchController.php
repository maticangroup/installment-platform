<?php

namespace App\Controller\Delivery;

use Matican\Models\Delivery\AvailableQueuesModel;
use Matican\Models\Delivery\DeliveryMethodModel;
use Matican\Models\Delivery\DeliveryPersonModel;
use Matican\Models\Delivery\DispatchModel;
use Matican\Models\Delivery\QueueModel;
use Matican\Models\Delivery\WeekDayModel;
use Matican\ModelSerializer;
use Matican\Models\Repository\LocationModel;
use Matican\Models\Repository\PersonModel;
use Matican\Models\Sale\OrderModel;
use Matican\Authentication\AuthUser;
use Matican\Permissions\ServerPermissions;
use Matican\Core\Entities\Delivery;
use Matican\Core\Entities\Repository;
use Matican\Core\Entities\Sale;
use Matican\Core\Servers;
use Matican\ResponseStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Matican\Core\Transaction\Request as Req;

/**
 * @Route("/delivery/dispatch", name="delivery_dispatch")
 */
class DispatchController extends AbstractController
{
    /**
     * @Route("/list", name="_list")
     */
    public function fetchAll()
    {

        $canSeeAll = AuthUser::if_is_allowed(ServerPermissions::delivery_dispatch_all);
        $canEdit = AuthUser::if_is_allowed(ServerPermissions::delivery_dispatch_edit);

        if (!$canSeeAll) {
            return $this->redirect($this->generateUrl('default'));
        }
        $request = new Req(Servers::Delivery, Delivery::Dispatch, 'all');
        $response = $request->send();

        /**
         * @var $dispatches DispatchModel[]
         */
        $dispatches = [];
        if ($response->getContent()) {
            foreach ($response->getContent() as $dispatch) {
                $dispatches[] = ModelSerializer::parse($dispatch, DispatchModel::class);
            }
        }

        return $this->render('delivery/dispatch/list.html.twig', [
            'controller_name' => 'DispatchController',
            'dispatches' => $dispatches,
            'canSeeAll' => $canSeeAll,
            'canEdit' => $canEdit,
        ]);
    }

    /**
     * @Route("/create", name="_create")
     */
    public function create()
    {
        $canCreate = AuthUser::if_is_allowed(ServerPermissions::sale_order_all);

        if (!$canCreate) {
            return $this->redirect($this->generateUrl('delivery_dispatch_list'));
        }

        $ordersRequest = new Req(Servers::Sale, Sale::Order, 'all');
        $orderResponse = $ordersRequest->send();

        /**
         * @var $orders OrderModel[]
         */
        $orders = [];
        if ($orderResponse->getContent()) {
            foreach ($orderResponse->getContent() as $order) {
                if ($order->orderStatus->orderStatusMachineName == 'confirmed') {
                    $orders[] = ModelSerializer::parse($order, OrderModel::class);
                }
            }
        }


        return $this->render('delivery/dispatch/create.html.twig', [
            'controller_name' => 'DispatchController',
            'orders' => $orders,
            'canCreate' => $canCreate,
        ]);
    }

    /**
     * @Route("/add-dispatch/{order_id}", name="_add_dispatch")
     * @param Request $request
     * @param $order_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function createDispatch(Request $request, $order_id)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::delivery_dispatch_new)) {
            return $this->redirect($this->generateUrl('delivery_dispatch_list'));
        }
        $inputs = $request->request->all();
        /**
         * @var $dispatchModel DispatchModel
         */
        $dispatchModel = ModelSerializer::parse($inputs, DispatchModel::class);
        $dispatchModel->setDispatchOrderId($order_id);

        $request = new Req(Servers::Delivery, Delivery::Dispatch, 'new');
        $request->add_instance($dispatchModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
            /**
             * @var $dispatchModel DispatchModel
             */
            $dispatchModel = ModelSerializer::parse($response->getContent(), DispatchModel::class);
            return $this->redirect($this->generateUrl('delivery_dispatch_edit', ['id' => $dispatchModel->getDispatchId()]));
        } else {
            $this->addFlash('f', $response->getMessage());
            return $this->redirect($this->generateUrl('delivery_dispatch_create'));
        }
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

        $canAddDeliveryMethod = AuthUser::if_is_allowed(ServerPermissions::delivery_dispatch_set_delivery_method);
        $canAddLocation = AuthUser::if_is_allowed(ServerPermissions::delivery_dispatch_set_dispatch_location);
        $canAddQueue = AuthUser::if_is_allowed(ServerPermissions::delivery_dispatch_set_dispatch_queue);
        $canConfirmQueue = AuthUser::if_is_allowed(ServerPermissions::delivery_dispatch_finalize_dispatch);

        if (!AuthUser::if_is_allowed(ServerPermissions::delivery_dispatch_fetch)) {
            return $this->redirect($this->generateUrl('delivery_dispatch_list'));

        }
        $inputs = $request->request->all();
        /**
         * @var $dispatchModel DispatchModel
         */
        $dispatchModel = ModelSerializer::parse($inputs, DispatchModel::class);
        $dispatchModel->setDispatchId($id);
        $request = new Req(Servers::Delivery, Delivery::Dispatch, 'fetch');
        $request->add_instance($dispatchModel);
        $response = $request->send();
        $dispatchModel = ModelSerializer::parse($response->getContent(), DispatchModel::class);
        /**
         * @var $dispatchModel DispatchModel
         */
        $tempDispatchModel = new DispatchModel();
        $tempDispatchModel->setDispatchOrderId($dispatchModel->getDispatchOrderId());
        $deliveryMethodsRequest = new Req(Servers::Delivery, Delivery::DeliveryMethod, 'get_allowed_delivery_methods');
        $deliveryMethodsRequest->add_instance($tempDispatchModel);
        $deliveryMethodsResponse = $deliveryMethodsRequest->send();

        /**
         * @var $deliveryMethods DeliveryMethodModel[]
         */
        $deliveryMethods = [];
        if ($deliveryMethodsResponse->getContent()) {
            foreach ($deliveryMethodsResponse->getContent() as $deliveryMethod) {
                $deliveryMethods[] = ModelSerializer::parse($deliveryMethod, DeliveryMethodModel::class);
            }
        }

        $availableQueuesModel = new AvailableQueuesModel();
        $availableQueuesModel->setTodayDateTime();
        $availableQueuesModel->setDeliveryMethodId($dispatchModel->getDispatchDeliveryMethodId());
        $availableQueuesModel->setDispatchId($dispatchModel->getDispatchId());
        $availableQueuesModel->setDaysRange(7);
        /**
         * @var $availableQueues WeekDayModel[]
         */
        $availableQueues = [];
        if ($dispatchModel->getDispatchDeliveryMethodId()) {
            $availableQueuesRequest = new Req(Servers::Delivery, Delivery::DeliveryMethod, 'get_available_queues');
            $availableQueuesRequest->add_instance($availableQueuesModel);
            $availableQueuesResponse = $availableQueuesRequest->send();
            if ($availableQueuesResponse->getContent()) {
                /**
                 * @var $availableQueuesModel AvailableQueuesModel
                 */
                $availableQueuesModel = ModelSerializer::parse($availableQueuesResponse->getContent(), AvailableQueuesModel::class);
                if ($availableQueuesModel->getWeekDays()) {
                    foreach ($availableQueuesModel->getWeekDays() as $day) {
                        $availableQueues[] = ModelSerializer::parse($day, WeekDayModel::class);
                    }
                }
            }
        }

        /**
         * @var $ownerLocations LocationModel[]
         */
        $ownerLocations = [];
        /**
         * @var $orderModel OrderModel
         */
        $orderModel = ModelSerializer::parse($dispatchModel->getDispatchOrder(), OrderModel::class);
        $personModel = new PersonModel();
        $personModel->setId($orderModel->getOrderOwnerId());
        $ownerLocationsRequest = new Req(Servers::Repository, Repository::Person, 'get_all_addresses');
        $ownerLocationsRequest->add_instance($personModel);
        $ownerLocationsResponse = $ownerLocationsRequest->send();
        if ($ownerLocationsResponse->getContent()) {
            foreach ($ownerLocationsResponse->getContent() as $location) {
                $ownerLocations[] = ModelSerializer::parse($location, LocationModel::class);
            }
        }

        /**
         * @var $deliveryPersons DeliveryPersonModel[]
         */
        $deliveryPersons = [];
        if ($dispatchModel->getDispatchDeliveryMethodId()) {
            $deliveryMethodModel = new DeliveryMethodModel();
            $deliveryMethodModel->setDeliveryMethodId($dispatchModel->getDispatchDeliveryMethodId());
            $deliveryMethodModel->setDispatchId($dispatchModel->getDispatchId());
            $deliveryPersonsRequest = new Req(Servers::Delivery, Delivery::DeliveryPerson, 'get_delivery_method_people');
            $deliveryPersonsRequest->add_instance($deliveryMethodModel);
            $deliveryPersonsResponse = $deliveryPersonsRequest->send();
            if ($deliveryPersonsResponse->getContent()) {
                foreach ($deliveryPersonsResponse->getContent() as $deliveryPerson) {
                    $deliveryPersons[] = ModelSerializer::parse($deliveryPerson, DeliveryPersonModel::class);
                }
            }
        }


        return $this->render('delivery/dispatch/edit.html.twig', [
            'controller_name' => 'DispatchController',
            'dispatchModel' => $dispatchModel,
            'deliveryMethods' => $deliveryMethods,
            'availableQueues' => $availableQueues,
            'ownerLocations' => $ownerLocations,
            'deliveryPersons' => $deliveryPersons,
            'canAddDeliveryMethod' => $canAddDeliveryMethod,
            'canAddLocation' => $canAddLocation,
            'canAddQueue' => $canAddQueue,
            'canConfirmQueue' => $canConfirmQueue,
        ]);
    }

    /**
     * @Route("/add-delivery-method/{dispatch_id}", name="_add_delivery_method")
     * @param $dispatch_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function addDeliveryMethod($dispatch_id, Request $request)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::delivery_dispatch_set_delivery_method)) {
            return $this->redirect($this->generateUrl('delivery_dispatch_edit', ['id' => $dispatch_id]));

        }
        $inputs = $request->request->all();
        /**
         * @var $deliveryMethodModel DeliveryMethodModel
         */
        $deliveryMethodModel = ModelSerializer::parse($inputs, DeliveryMethodModel::class);
        $deliveryMethodModel->setDispatchId($dispatch_id);
        $request = new Req(Servers::Delivery, Delivery::Dispatch, 'set_delivery_method');
        $request->add_instance($deliveryMethodModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('delivery_dispatch_edit', ['id' => $dispatch_id]));
    }

    /**
     * @Route("/add-queue/{dispatch_id}", name="_add_queue")
     * @param $dispatch_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function addQueue($dispatch_id, Request $request)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::delivery_dispatch_set_queue)) {
            return $this->redirect($this->generateUrl('delivery_dispatch_edit', ['id' => $dispatch_id]));

        }
        $inputs = $request->request->all();
        /**
         * @var $queueModel QueueModel
         */
        $queueModel = ModelSerializer::parse($inputs, QueueModel::class);
        $queueModel->setDispatchId($dispatch_id);
//        dd($queueModel);
        $request = new Req(Servers::Delivery, Delivery::Dispatch, 'set_dispatch_queue');
        $request->add_instance($queueModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('delivery_dispatch_edit', ['id' => $dispatch_id]));
    }


    /**
     * @Route("/confirm-queue/{dispatch_id}", name="_confirm_queue")
     * @param $dispatch_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function confirmQueue($dispatch_id)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::delivery_dispatch_finalize_dispatch)) {
            return $this->redirect($this->generateUrl('delivery_dispatch_edit', ['id' => $dispatch_id]));
        }
        $dispatchModel = new DispatchModel();
        $dispatchModel->setDispatchId($dispatch_id);
        $request = new Req(Servers::Delivery, Delivery::Dispatch, 'finalize_dispatch');
        $request->add_instance($dispatchModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('delivery_dispatch_edit', ['id' => $dispatch_id]));
    }

    /**
     * @Route("/rethink-confirm-queue/{dispatch_id}", name="_rethink_confirm_queue")
     * @param $dispatch_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function rethinkConfirmQueue($dispatch_id)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::delivery_dispatch_rethink_dispatch)) {
            return $this->redirect($this->generateUrl('delivery_dispatch_edit', ['id' => $dispatch_id]));
        }
        $dispatchModel = new DispatchModel();
        $dispatchModel->setDispatchId($dispatch_id);
        $request = new Req(Servers::Delivery, Delivery::Dispatch, 'rethink_dispatch');
        $request->add_instance($dispatchModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('delivery_dispatch_edit', ['id' => $dispatch_id]));
    }


    /**
     * @Route("/add-location-to-dispatch/{dispatch_id}/{location_id}", name="_add_location_to_dispatch")
     * @param $dispatch_id
     * @param $location_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function addLocationToDispatch($dispatch_id, $location_id)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::delivery_dispatch_set_dispatch_location)) {
            return $this->redirect($this->generateUrl('delivery_dispatch_edit', ['id' => $dispatch_id]));
        }
        $locationModel = new LocationModel();
        $locationModel->setLocationId($location_id);
        $locationModel->setDispatchId($dispatch_id);
        $request = new Req(Servers::Delivery, Delivery::Dispatch, 'set_dispatch_location');
        $request->add_instance($locationModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('delivery_dispatch_edit', ['id' => $dispatch_id]));
    }


    /**
     * @Route("/add-delivery-person-to-dispatch/{dispatch_id}/{delivery_person_id}", name="_add_delivery_person_to_dispatch")
     * @param $dispatch_id
     * @param $delivery_person_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function addDeliveryPersonToDispatch($dispatch_id, $delivery_person_id)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::delivery_dispatch_set_delivery_person)) {
            return $this->redirect($this->generateUrl('delivery_dispatch_edit', ['id' => $dispatch_id]));
        }
        $deliveryPersonModel = new DeliveryPersonModel();
        $deliveryPersonModel->setDeliveryPersonId($delivery_person_id);
        $deliveryPersonModel->setDispatchId($dispatch_id);
//        dd($deliveryPersonModel);
        $request = new Req(Servers::Delivery, Delivery::Dispatch, 'set_delivery_person');
        $request->add_instance($deliveryPersonModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('delivery_dispatch_edit', ['id' => $dispatch_id]));
    }


}
