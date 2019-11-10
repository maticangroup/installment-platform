<?php

namespace App\Controller\Accounting;

use App\PersianCalendar;
use Matican\Authentication\AuthUser;
use Matican\Core\Entities\Accounting;
use Matican\Core\Entities\Repository;
use Matican\Core\Servers;
use Matican\Models\Accounting\BuyTypeModel;
use Matican\Models\Accounting\ChequeTypeModel;
use Matican\Models\Accounting\GenderStatusModel;
use Matican\Models\Accounting\InstallmentPersonalInformation;
use Matican\Models\Accounting\InstallmentRequestFormModel;
use Matican\Models\Accounting\InstallmentRequestModel;
use Matican\Models\Accounting\MarriageStatusModel;
use Matican\Models\Repository\JobStatusModel;
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

        $personalInformation = new InstallmentPersonalInformation();

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
            foreach ($response->getContent() as $key => $item) {

                $newDate = date("Y-m-d", strtotime($item['requestCreateDateTime']));
                $persianDate = PersianCalendar::mds_date("Y-m-d", strtotime($newDate));

                $installmentPayments[] = ModelSerializer::parse($item, InstallmentRequestModel::class);
                $installmentPayments[$key]->setRequestCreateDateTime($persianDate);
            }
        }

        $allRequests = count($installmentPayments);
        $allAcceptedRequests = 0;
        $allRejectedRequests = 0;
        $allWaitingRequests = 0;

        foreach ($installmentPayments as $installmentPayment) {
            if ($installmentPayment->getRequestStatus()->machine_name == 'accepted') {
                $allAcceptedRequests++;
            }
            if ($installmentPayment->getRequestStatus()->machine_name == 'rejected') {
                $allRejectedRequests++;
            }
            if ($installmentPayment->getRequestStatus()->machine_name == 'waiting') {
                $allWaitingRequests++;
            }
        }

        $currentUser = AuthUser::current_user();

        if ($currentUser->getUserName()) {
            $userName = $currentUser->getUserName();
        } else {
            $userName = "";
        }

        $personModel = new PersonModel();

        $genderRequest = new Req(Servers::Repository, Repository::Person, 'get_all_genders');
        $genderResponse = $genderRequest->send();
        $genders = [];
        foreach ($genderResponse->getContent() as $item) {
            $genders[] = ModelSerializer::parse($item, GenderStatusModel::class);
        }


        $marriageRequest = new Req(Servers::Repository, Repository::Person, 'get_all_marriage_statuses');
        $marriageResponse = $marriageRequest->send();
        $marriages = [];
        foreach ($marriageResponse->getContent() as $item) {
            $marriages[] = ModelSerializer::parse($item, MarriageStatusModel::class);
        }

        $jobStatusRequest = new Req(Servers::Repository, Repository::Person, 'get_all_job_statuses');
        $jobStatusResponse = $jobStatusRequest->send();
        $jobStatuses = [];
        foreach ($jobStatusResponse->getContent() as $item) {
            $jobStatuses[] = ModelSerializer::parse($item, JobStatusModel::class);
        }

        $buyTypeRequest = new Req(Servers::Accounting, 'InstallmentRequest', 'get_all_buy_types');
        $buyTypeResponse = $buyTypeRequest->send();
        $buyTypes = [];
        foreach ($buyTypeResponse->getContent() as $item) {
            $buyTypes[] = ModelSerializer::parse($item, BuyTypeModel::class);
        }

        $chequeTypeRequest = new Req(Servers::Accounting, 'InstallmentRequest', 'get_all_cheque_types');
        $chequeTypeResponse = $chequeTypeRequest->send();
        $chequeTypes = [];
        foreach ($chequeTypeResponse->getContent() as $item) {
            $chequeTypes[] = ModelSerializer::parse($item, ChequeTypeModel::class);
        }

        $bankRequest = new Req(Servers::Accounting, 'InstallmentRequest', 'get_all_banks');
        $bankResponse = $bankRequest->send();
        $banks = [];
        foreach ($bankResponse->getContent() as $item) {
            $banks[] = ModelSerializer::parse($item, ChequeTypeModel::class);
        }

        $currentDate = date('Y');
        $persianCurrentDate = PersianCalendar::mds_date("Y", strtotime($currentDate));
        $year = [];
        for ($i = $persianCurrentDate; $i >= 1350; $i--) {
            $year[] = $i;
        }

        return $this->render('accounting/installment_request/list.html.twig', [
            'controller_name' => 'InstallmentRequestController',
            'installmentPayments' => $installmentPayments,
            'userName' => $userName,
            'personModel' => $personModel,
            'canSeeAllUsers' => $canSeeAllUsers,
            'canSeeUserRequests' => $canSeeUserRequests,
            'canNewRequest' => $canNewRequest,
            'allRequests' => $allRequests,
            'allAcceptedRequests' => $allAcceptedRequests,
            'allRejectedRequests' => $allRejectedRequests,
            'allWaitingRequests' => $allWaitingRequests,
            'personalInformation' => $personalInformation,
            'genders' => $genders,
            'marriages' => $marriages,
            'jobStatuses' => $jobStatuses,
            'buyTypes' => $buyTypes,
            'chequeTypes' => $chequeTypes,
            'years' => $year,
            'banks' => $banks,
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

        $installmentPaymentModel = new  InstallmentRequestFormModel();

        $currentUser = AuthUser::current_user();

        if ($currentUser->getUserName()) {
            $userName = $currentUser->getUserName();
        } else {
            $userName = "";
        }

        if (!empty($inputs)) {
            /**
             * @var $installmentPaymentModel InstallmentRequestFormModel
             */
            $installmentPaymentModel = ModelSerializer::parse($inputs, InstallmentRequestFormModel::class);
            $request = new Req(Servers::Accounting, 'InstallmentRequest', 'new_installment_request');
            $request->add_instance($installmentPaymentModel);
            $response = $request->send();

//            dd($response);

            if ($response->getStatus() == ResponseStatus::successful) {
                $this->addFlash('s', $response->getMessage());
            } else {
                $this->addFlash('f', $response->getMessage());
            }
        }

        return $this->redirect($this->generateUrl('accounting_installment_request_list'));

//        return $this->render('accounting/installment_request/list.html.twig', [
//            'installmentPaymentModel' => $installmentPaymentModel,
//            'userName' => $userName,
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
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function updatePersonalInfo(Request $request)
    {
        $inputs = $inputs = $request->request->all();


        if (!empty($inputs)) {
            /**
             * @var $personalInformation InstallmentPersonalInformation
             */
            $personalInformation = ModelSerializer::parse($inputs, InstallmentPersonalInformation::class);
            $request = new Req(Servers::Accounting, 'InstallmentRequest', 'update_user_info');
            $request->add_instance($personalInformation);
            $response = $request->send();

            $messages = json_decode($response->getMessage(), true);

            if ($response->getStatus() == ResponseStatus::successful) {
                $this->addFlash('s', $response->getMessage());
                return $this->redirect($this->generateUrl('authentication_logout'));
            } else {
                if ($messages) {
                    foreach ($messages as $message) {
                        $this->addFlash('f', $message);
                    }
                }
                return $this->redirect($this->generateUrl('accounting_installment_request_list'));
            }
        }

    }
}
