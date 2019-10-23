<?php

namespace App\Controller\Accounting;

use Matican\Authentication\AuthUser;
use Matican\Core\Entities\Accounting;
use Matican\Core\Entities\Repository;
use Matican\Core\Servers;
use Matican\Models\Accounting\InstallmentRequestModel;
use Matican\Models\Repository\PersonModel;
use Matican\ModelSerializer;
use Matican\Permissions\ServerPermissions;
use Matican\ResponseStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Matican\Core\Transaction\Request as Req;


/**
 * @Route("/accounting/installment-request", name="accounting_installment_request")
 */
class InstallmentRequestController extends AbstractController
{
    /**
     * @Route("/list", name="_list")
     */
    public function fetchAll()
    {

        $canSeeAllUsers = AuthUser::if_is_allowed(ServerPermissions::accounting_installmentrequest_all);
        $canSeeUserRequests = AuthUser::if_is_allowed(ServerPermissions::accounting_installmentrequest_all_user_requests);
        $canNewRequest = AuthUser::if_is_allowed(ServerPermissions::accounting_installmentrequest_new);

        if ($canSeeAllUsers) {
            $request = new Req(Servers::Accounting, 'InstallmentRequest', 'all');
        } else {
            $request = new Req(Servers::Accounting, 'InstallmentRequest', 'all_user_requests');
        }
        $response = $request->send();


        /**
         * @var $installmentPayments InstallmentRequestModel[]
         */
        $installmentPayments = [];
        if ($response->getContent() != null) {
            foreach ($response->getContent() as $item) {
                $installmentPayments[] = ModelSerializer::parse($item, InstallmentRequestModel::class);
            }
        }

        $currentUser = AuthUser::current_user();

        if ($currentUser->getUserName()) {
            $userName = $currentUser->getUserName();
        } else {
            $userName = "";
        }

        $personModel = new PersonModel();

        return $this->render('accounting/installment_request/list.html.twig', [
            'controller_name' => 'InstallmentRequestController',
            'installmentPayments' => $installmentPayments,
            'userName' => $userName,
            'personModel' => $personModel,
            'canSeeAllUsers' => $canSeeAllUsers,
            'canSeeUserRequests' => $canSeeUserRequests,
            'canNewRequest' => $canNewRequest,
        ]);
    }

    /**
     * @Route("/create", name="_create")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function create(Request $request)
    {
        $inputs = $request->request->all();

        /**
         * @var $installmentPaymentModel InstallmentRequestModel
         */
        $installmentPaymentModel = ModelSerializer::parse($inputs, InstallmentRequestModel::class);


        if (!empty($inputs)) {
            /**
             * @var $couponGroupModel InstallmentRequestModel
             */
            $installmentPaymentModel = ModelSerializer::parse($inputs, InstallmentRequestModel::class);
            $request = new Req(Servers::Accounting, 'InstallmentRequest', 'new');
            $request->add_instance($installmentPaymentModel);
            $response = $request->send();

            if ($response->getStatus() == ResponseStatus::successful) {
                $this->addFlash('s', $response->getMessage());
                return $this->redirect($this->generateUrl('accounting_installment_request_list'));
            } else {
                $this->addFlash('f', $response->getMessage());
            }
        }

//        return $this->render('accounting/installment_request/list.html.twig', [
//            'controller_name' => 'InstallmentRequestController',
//            'installmentPaymentModel' => $installmentPaymentModel,
//        ]);
    }

    /**
     * @Route("/change-status/{installment_id}/{machine_name}", name="_change_status")
     * @param $installment_id
     * @param $machine_name
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function changeStatus($installment_id, $machine_name)
    {

        $installmentRequestModel = new InstallmentRequestModel();
        $installmentRequestModel->setRequestID($installment_id);
        $installmentRequestModel->setRequestStatus($machine_name);

        $request = new Req(Servers::Accounting, 'InstallmentRequest', 'change_status');
        $request->add_instance($installmentRequestModel);
        $response = $request->send();

        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }

        return $this->redirect($this->generateUrl('accounting_installment_request_list'));
    }


    /**
     * @Route("/personal-info", name="_personal_info")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function updatePersonalInfo(Request $request)
    {
        $inputs = $inputs = $request->request->all();

        if (!empty($inputs)) {
            /**
             * @var $personModel PersonModel
             */
            $personModel = ModelSerializer::parse($inputs, PersonModel::class);
            $request = new Req(Servers::Accounting, 'InstallmentRequest', 'update_user_info');
            $request->add_instance($personModel);
            $response = $request->send();

            if ($response->getStatus() == ResponseStatus::successful) {
                $this->addFlash('s', $response->getMessage());
                return $this->redirect($this->generateUrl('authentication_logout'));
            } else {
                $this->addFlash('f', $response->getMessage());
            }
            return $this->redirect($this->generateUrl('accounting_installment_request_list'));
        }

    }
}
