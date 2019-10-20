<?php

namespace App\Controller\Delivery;

use Matican\Models\Delivery\DeliveryMethodModel;
use Matican\Models\Delivery\DeliveryMethodStatusModel;
use Matican\Models\Delivery\DeliveryMethodTypeModel;
use Matican\Models\Delivery\DeliveryPersonModel;
use Matican\Models\Delivery\DeliveryPersonStatusModel;
use Matican\Models\Delivery\QueueModel;
use Matican\Models\Delivery\QueueStatusModel;
use Matican\Models\Delivery\WeekDayModel;
use Matican\ModelSerializer;
use Matican\Models\Repository\PersonModel;
use Matican\Models\Repository\SizeModel;
use Matican\Authentication\AuthUser;

use Matican\Permissions\ServerPermissions;
use Matican\Core\Entities\Delivery;
use Matican\Core\Entities\Repository;
use Matican\Core\Servers;
use Matican\ResponseStatus;
use Matican\Models\Media\Image;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Matican\Core\Transaction\Request as Req;

/**
 * @Route("/delivery/delivery-method", name="delivery_method")
 */
class DeliveryMethodController extends AbstractController
{
    /**
     * @Route("/list", name="_list")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function fetchAll()
    {
        $canSeeAll = AuthUser::if_is_allowed(ServerPermissions::delivery_deliverymethod_all);
        $canCreate = AuthUser::if_is_allowed(ServerPermissions::delivery_deliverymethod_new);
        $canEdit = AuthUser::if_is_allowed(ServerPermissions::delivery_deliverymethod_update);
        $canRead = AuthUser::if_is_allowed(ServerPermissions::delivery_deliverymethod_fetch);
        $canChangeStatus = AuthUser::if_is_allowed(ServerPermissions::delivery_deliverymethod_change_delivery_method_status);

        if (!$canSeeAll) {
            return $this->redirect($this->generateUrl('default'));
        }

        $request = new Req(Servers::Delivery, Delivery::DeliveryMethod, 'all');
        $response = $request->send();

        /**
         * @var $deliveryMethods DeliveryMethodModel[]
         */
        $deliveryMethods = [];
        if ($response->getContent()) {
            foreach ($response->getContent() as $deliveryMethod) {
                $deliveryMethods[] = ModelSerializer::parse($deliveryMethod, DeliveryMethodModel::class);
            }
        }

        return $this->render('delivery/delivery_method/list.html.twig', [
            'controller_name' => 'DeliveryMethodController',
            'deliveryMethods' => $deliveryMethods,
            'canSeeAll' => $canSeeAll,
            'canEdit' => $canEdit,
            'canRead' => $canRead,
            'canChangeStatus' => $canChangeStatus,
            'canCreate' => $canCreate,
        ]);
    }

    /**
     * @Route("/create", name="_create")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function create(Request $request)
    {
        $canCreate = AuthUser::if_is_allowed(ServerPermissions::delivery_deliverymethod_new);
        $canEdit = AuthUser::if_is_allowed(ServerPermissions::delivery_deliverymethod_update);

        if (!$canCreate) {
            return $this->redirect($this->generateUrl('delivery_method_list'));
        }
        $inputs = $request->request->all();
        /**
         * @var $deliveryMethodModel DeliveryMethodModel
         */
        $deliveryMethodModel = ModelSerializer::parse($inputs, DeliveryMethodModel::class);

        if (!empty($inputs)) {
            $coreRequest = new Req(Servers::Delivery, Delivery::DeliveryMethod, 'new');
            $file = $request->files->get('deliveryMethodLogoUrl');

            if ($file) {
                $response = $coreRequest->uploadImage($file, $deliveryMethodModel);
            } else {
                $response = $coreRequest->uploadImage(null, $deliveryMethodModel);
            }
            if ($response->getStatus() == ResponseStatus::successful) {
                /**
                 * @var $deliveryMethodModel DeliveryMethodModel
                 */
                $deliveryMethodModel = ModelSerializer::parse($response->getContent(), DeliveryMethodModel::class);
                $this->addFlash('s', $response->getMessage());
                if ($canEdit) {
                    return $this->redirect($this->generateUrl('delivery_method_edit', ['id' => $deliveryMethodModel->getDeliveryMethodId()]));
                } else {
                    return $this->redirect($this->generateUrl('delivery_method_list'));
                }
            } else {
                $this->addFlash('f', $response->getMessage());
            }
        }

        $deliveryMethodTypesRequest = new Req(Servers::Delivery, Delivery::DeliveryMethod, 'get_all_types');
        $deliveryMethodTypesResponse = $deliveryMethodTypesRequest->send();

        /**
         * @var $deliveryMethodTypes DeliveryMethodTypeModel[]
         */
        $deliveryMethodTypes = [];
        if ($deliveryMethodTypesResponse->getContent()) {
            foreach ($deliveryMethodTypesResponse->getContent() as $type) {
                $deliveryMethodTypes[] = ModelSerializer::parse($type, DeliveryMethodTypeModel::class);
            }
        }

        return $this->render('delivery/delivery_method/create.html.twig', [
            'controller_name' => 'DeliveryMethodController',
            'deliveryMethodModel' => $deliveryMethodModel,
            'deliveryMethodTypes' => $deliveryMethodTypes,
            'canCreate' => $canCreate
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

        $canEdit = AuthUser::if_is_allowed(ServerPermissions::delivery_deliverymethod_update);
        $canAddSize = AuthUser::if_is_allowed(ServerPermissions::delivery_deliverymethod_add_allowed_size);
        $canRemoveSize = AuthUser::if_is_allowed(ServerPermissions::delivery_deliverymethod_remove_allowed_size);
        $canAddDeliveryPerson = AuthUser::if_is_allowed(ServerPermissions::delivery_deliverymethod_add_delivery_person);
        $canRemoveDeliveryPerson = AuthUser::if_is_allowed(ServerPermissions::delivery_deliverymethod_remove_delivery_person);
        $canAddQueue = AuthUser::if_is_allowed(ServerPermissions::delivery_deliverymethod_add_queue);
        $canRemoveQueue = AuthUser::if_is_allowed(ServerPermissions::delivery_deliverymethod_remove_queue);
        $canChangeQueueStatus = AuthUser::if_is_allowed(ServerPermissions::delivery_deliverymethod_change_queue_status);
        $canReadDeliveryPerson = AuthUser::if_is_allowed(ServerPermissions::delivery_deliveryperson_fetch);
        $canEditDeliveryPerson = AuthUser::if_is_allowed(ServerPermissions::delivery_deliveryperson_update);

        $file = $request->files->get('deliveryMethodLogoUrl');

        if (!$canEdit) {
            return $this->redirect($this->generateUrl('delivery_method_list'));
        }
        $inputs = $request->request->all();
        /**
         * @var $deliveryMethodModel DeliveryMethodModel
         */
        $deliveryMethodModel = ModelSerializer::parse($inputs, DeliveryMethodModel::class);
        $deliveryMethodModel->setDeliveryMethodId($id);
        $request = new Req(Servers::Delivery, Delivery::DeliveryMethod, 'fetch');
        $request->add_instance($deliveryMethodModel);
        $response = $request->send();
//        dd($response);
        /**
         * @var $deliveryMethodModel DeliveryMethodModel
         */
        $deliveryMethodModel = ModelSerializer::parse($response->getContent(), DeliveryMethodModel::class);
//        dd($deliveryMethodModel);

        $deliveryMethodTypesRequest = new Req(Servers::Delivery, Delivery::DeliveryMethod, 'get_all_types');
        $deliveryMethodTypesResponse = $deliveryMethodTypesRequest->send();

        /**
         * @var $deliveryMethodTypes DeliveryMethodTypeModel[]
         */
        $deliveryMethodTypes = [];
        if ($deliveryMethodTypesResponse->getContent()) {
            foreach ($deliveryMethodTypesResponse->getContent() as $type) {
                $deliveryMethodTypes[] = ModelSerializer::parse($type, DeliveryMethodTypeModel::class);
            }
        }

        $sizesRequest = new Req(Servers::Repository, Repository::Size, 'all');
        $sizesResponse = $sizesRequest->send();

        /**
         * @var $sizes SizeModel[]
         */
        $sizes = [];
        if ($sizesResponse->getContent()) {
            foreach ($sizesResponse->getContent() as $size) {
                $sizes[] = ModelSerializer::parse($size, SizeModel::class);
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


        /**
         * @var $selectedSizes SizeModel[]
         */
        $selectedSizes = [];
        if ($deliveryMethodModel->getDeliveryMethodSizes()) {
            foreach ($deliveryMethodModel->getDeliveryMethodSizes() as $selectedSize) {
                $selectedSizes[] = ModelSerializer::parse($selectedSize, SizeModel::class);
            }
        }

        /**
         * @var $deliveryPersons DeliveryPersonModel[]
         */
        $deliveryPersons = [];
        if ($deliveryMethodModel->getDeliveryMethodPersons()) {
            foreach ($deliveryMethodModel->getDeliveryMethodPersons() as $deliveryPerson) {
                $deliveryPersons[] = ModelSerializer::parse($deliveryPerson, DeliveryPersonModel::class);
            }
        }
//        dd($deliveryPersons);

        /**
         * @var $weekDays WeekDayModel[]
         */
        $weekDays = [];
        if ($deliveryMethodModel->getDeliveryMethodWeekDays()) {
            foreach ($deliveryMethodModel->getDeliveryMethodWeekDays() as $day) {
                $weekDays[] = ModelSerializer::parse($day, WeekDayModel::class);
            }
        }


        if (!empty($inputs)) {
            /**
             * @var $deliveryMethodModel DeliveryMethodModel
             */
            $deliveryMethodModel = ModelSerializer::parse($inputs, DeliveryMethodModel::class);
            $deliveryMethodModel->setDeliveryMethodId($id);
            $request = new Req(Servers::Delivery, Delivery::DeliveryMethod, 'update');
            if ($file) {
                $response = $request->uploadImage($file, $deliveryMethodModel);
            } else {
                $response = $request->uploadImage(null, $deliveryMethodModel);
            }
//            $request->add_instance($deliveryMethodModel);
//            $response = $request->send();
//            dd($response);
            if ($response->getStatus() == ResponseStatus::successful) {
                /**
                 * @var $deliveryMethodModel DeliveryMethodModel
                 */
                $deliveryMethodModel = ModelSerializer::parse($response->getContent(), DeliveryMethodModel::class);
                $this->addFlash('s', $response->getMessage());
                return $this->redirect($this->generateUrl('delivery_method_edit', ['id' => $deliveryMethodModel->getDeliveryMethodId()]));
            } else {
                $this->addFlash('f', $response->getMessage());
            }
        }


        return $this->render('delivery/delivery_method/edit.html.twig', [
            'controller_name' => 'DeliveryMethodController',
            'deliveryMethodModel' => $deliveryMethodModel,
            'deliveryMethodTypes' => $deliveryMethodTypes,
            'sizes' => $sizes,
            'selectedSizes' => $selectedSizes,
            'deliveryPersons' => $deliveryPersons,
            'persons' => $persons,
            'weekDays' => $weekDays,
            'canAddSize' => $canAddSize,
            'canAddDeliveryPerson' => $canAddDeliveryPerson,
            'canAddQueue' => $canAddQueue,
            'canEdit' => $canEdit,
            'canRemoveSize' => $canRemoveSize,
            'canRemoveDeliveryPerson' => $canRemoveDeliveryPerson,
            'canRemoveQueue' => $canRemoveQueue,
            'canChangeQueueStatus' => $canChangeQueueStatus,
            'canReadDeliveryPerson' => $canReadDeliveryPerson,
            'canEditDeliveryPerson' => $canEditDeliveryPerson
        ]);
    }

    /**
     * @Route("/read/{id}", name="_read")
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function read($id, Request $request)
    {

        $canRead = AuthUser::if_is_allowed(ServerPermissions::delivery_deliverymethod_fetch);

        if (!$canRead) {
            return $this->redirect($this->generateUrl('delivery_method_list'));
        }
        $inputs = $request->request->all();
        /**
         * @var $deliveryMethodModel DeliveryMethodModel
         */
        $deliveryMethodModel = ModelSerializer::parse($inputs, DeliveryMethodModel::class);
        $deliveryMethodModel->setDeliveryMethodId($id);
        $request = new Req(Servers::Delivery, Delivery::DeliveryMethod, 'fetch');
        $request->add_instance($deliveryMethodModel);
        $response = $request->send();
        /**
         * @var $deliveryMethodModel DeliveryMethodModel
         */
        $deliveryMethodModel = ModelSerializer::parse($response->getContent(), DeliveryMethodModel::class);


        /**
         * @var $selectedSizes SizeModel[]
         */
        $selectedSizes = [];
        if ($deliveryMethodModel->getDeliveryMethodSizes()) {
            foreach ($deliveryMethodModel->getDeliveryMethodSizes() as $selectedSize) {
                $selectedSizes[] = ModelSerializer::parse($selectedSize, SizeModel::class);
            }
        }

        /**
         * @var $deliveryPersons DeliveryPersonModel[]
         */
        $deliveryPersons = [];
        if ($deliveryMethodModel->getDeliveryMethodPersons()) {
            foreach ($deliveryMethodModel->getDeliveryMethodPersons() as $deliveryPerson) {
                $deliveryPersons[] = ModelSerializer::parse($deliveryPerson, DeliveryPersonModel::class);
            }
        }

        /**
         * @var $weekDays WeekDayModel[]
         */
        $weekDays = [];
        if ($deliveryMethodModel->getDeliveryMethodWeekDays()) {
            foreach ($deliveryMethodModel->getDeliveryMethodWeekDays() as $day) {
                $weekDays[] = ModelSerializer::parse($day, WeekDayModel::class);
            }
        }


        return $this->render('delivery/delivery_method/read.html.twig', [
            'controller_name' => 'DeliveryMethodController',
            'deliveryMethodModel' => $deliveryMethodModel,
            'selectedSizes' => $selectedSizes,
            'deliveryPersons' => $deliveryPersons,
            'weekDays' => $weekDays,
            'canRead' => $canRead
        ]);
    }

    /**
     * @Route("/add-size/{delivery_method_id}", name="_add_size")
     * @param $delivery_method_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function addSize($delivery_method_id, Request $request)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::delivery_deliverymethod_add_allowed_size)) {
            return $this->redirect($this->generateUrl('delivery_method_edit', ['id' => $delivery_method_id]));

        }
        $inputs = $request->request->all();
        /**
         * @var $sizeModel SizeModel
         */
        $sizeModel = ModelSerializer::parse($inputs, SizeModel::class);
        $sizeModel->setDeliveryMethodId($delivery_method_id);
        $request = new Req(Servers::Delivery, Delivery::DeliveryMethod, 'add_allowed_size');
        $request->add_instance($sizeModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('delivery_method_edit', ['id' => $delivery_method_id]));
    }


    /**
     * @Route("/remove-size/{delivery_method_id}/{size_id}", name="_remove_size")
     * @param $delivery_method_id
     * @param $size_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function removeSize($delivery_method_id, $size_id)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::delivery_deliverymethod_remove_allowed_size)) {
            return $this->redirect($this->generateUrl('delivery_method_edit', ['id' => $delivery_method_id]));

        }
        $sizeModel = new SizeModel();
        $sizeModel->setDeliveryMethodId($delivery_method_id);
        $sizeModel->setSizeID($size_id);
        $request = new Req(Servers::Delivery, Delivery::DeliveryMethod, 'remove_allowed_size');
        $request->add_instance($sizeModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('delivery_method_edit', ['id' => $delivery_method_id]));
    }


    /**
     * @Route("/add-delivery-person/{delivery_method_id}", name="_add_delivery_person")
     * @param $delivery_method_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function addDeliveryPerson($delivery_method_id, Request $request)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::delivery_deliverymethod_add_delivery_person)) {
            return $this->redirect($this->generateUrl('delivery_method_edit', ['id' => $delivery_method_id]));

        }
        $inputs = $request->request->all();
        /**
         * @var $deliveryPersonModel DeliveryPersonModel
         */
        $deliveryPersonModel = ModelSerializer::parse($inputs, DeliveryPersonModel::class);
        $deliveryPersonModel->setDeliveryPersonDeliveryMethodId($delivery_method_id);
//        dd($deliveryPersonModel);
        $request = new Req(Servers::Delivery, Delivery::DeliveryMethod, 'add_delivery_person');
        $request->add_instance($deliveryPersonModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('delivery_method_edit', ['id' => $delivery_method_id]));
    }


    /**
     * @Route("/remove-delivery-person/{delivery_method_id}/{delivery_person_id}", name="_remove_delivery_person")
     * @param $delivery_method_id
     * @param $delivery_person_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function removeDeliveryPerson($delivery_method_id, $delivery_person_id)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::delivery_deliverymethod_remove_delivery_person)) {
            return $this->redirect($this->generateUrl('delivery_method_edit', ['id' => $delivery_method_id]));

        }
        $deliveryPersonModel = new DeliveryPersonModel();
        $deliveryPersonModel->setDeliveryPersonDeliveryMethodId($delivery_method_id);
        $deliveryPersonModel->setDeliveryPersonId($delivery_person_id);
//        dd($deliveryPersonModel);
        $request = new Req(Servers::Delivery, Delivery::DeliveryMethod, 'remove_delivery_person');
        $request->add_instance($deliveryPersonModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('delivery_method_edit', ['id' => $delivery_method_id]));
    }


    /**
     * @Route("/add-queue/{delivery_method_id}/{week_day_id}", name="_add_queue")
     * @param $delivery_method_id
     * @param $week_day_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function addQueue($delivery_method_id, $week_day_id, Request $request)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::delivery_deliverymethod_add_queue)) {
            return $this->redirect($this->generateUrl('delivery_method_edit', ['id' => $delivery_method_id]));

        }
        $inputs = $request->request->all();
        /**
         * @var $queueModel QueueModel
         */
        $queueModel = ModelSerializer::parse($inputs, QueueModel::class);
        $queueModel->setDeliveryMethodId($delivery_method_id);
        $queueModel->setWeekDayId($week_day_id);
//        dd($queueModel);
        $request = new Req(Servers::Delivery, Delivery::DeliveryMethod, 'add_queue');
        $request->add_instance($queueModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('delivery_method_edit', ['id' => $delivery_method_id]));
    }


    /**
     * @Route("/remove-queue/{delivery_method_id}/{week_day_id}/{queue_id}", name="_remove_queue")
     * @param $delivery_method_id
     * @param $week_day_id
     * @param $queue_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function removeQueue($delivery_method_id, $week_day_id, $queue_id)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::delivery_deliverymethod_remove_queue)) {
            return $this->redirect($this->generateUrl('delivery_method_edit', ['id' => $delivery_method_id]));
        }
        $queueModel = new QueueModel();
        $queueModel->setDeliveryMethodId($delivery_method_id);
        $queueModel->setWeekDayId($week_day_id);
        $queueModel->setQueueId($queue_id);
        $request = new Req(Servers::Delivery, Delivery::DeliveryMethod, 'remove_queue');
        $request->add_instance($queueModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('delivery_method_edit', ['id' => $delivery_method_id]));
    }


    /**
     * @Route("/queue-status/{queue_id}/{machine_name}/{delivery_method_id}", name="_queue_status")
     * @param $queue_id
     * @param $machine_name
     * @param $delivery_method_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function changeQueueAvailability($queue_id, $machine_name, $delivery_method_id)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::delivery_deliverymethod_change_queue_status)) {
            return $this->redirect($this->generateUrl('delivery_method_edit', ['id' => $delivery_method_id]));
        }
        $queueStatusModel = new QueueStatusModel();
        if ($machine_name == 'active') {
            $queueStatusModel->setQueueId($queue_id);
            $queueStatusModel->setQueueStatusMachineName('deactive');
        } else {
            $queueStatusModel->setQueueId($queue_id);
            $queueStatusModel->setQueueStatusMachineName('active');
        }
//dd($queueStatusModel);
        $request = new Req(Servers::Delivery, Delivery::DeliveryMethod, 'change_queue_status');
        $request->add_instance($queueStatusModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('delivery_method_edit', ['id' => $delivery_method_id]));
    }


    /**
     * @Route("/status/{delivery_method_id}/{machine_name}", name="_status")
     * @param $delivery_method_id
     * @param $machine_name
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function changeAvailability($delivery_method_id, $machine_name)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::delivery_deliverymethod_change_delivery_method_status)) {
            return $this->redirect($this->generateUrl('delivery_method_list'));

        }
        $deliveryMethodStatusModel = new DeliveryMethodStatusModel();
        if ($machine_name == 'active') {
            $deliveryMethodStatusModel->setDeliveryMethodId($delivery_method_id);
            $deliveryMethodStatusModel->setDeliveryMethodStatusMachineName('deactive');
        } else {
            $deliveryMethodStatusModel->setDeliveryMethodId($delivery_method_id);
            $deliveryMethodStatusModel->setDeliveryMethodStatusMachineName('active');
        }

        $request = new Req(Servers::Delivery, Delivery::DeliveryMethod, 'change_delivery_method_status');
        $request->add_instance($deliveryMethodStatusModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('delivery_method_list'));
    }
}
