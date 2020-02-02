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
use Matican\Models\Accounting\InstallmentRequestViewFormModel;
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
     * @param Request $httpRequest
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function fetchAll(Request $httpRequest)
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
         * @var $installmentPayments InstallmentRequestViewFormModel[]
         */
        $installmentPayments = [];
        if ($response->getContent() != null) {
            foreach ($response->getContent() as $key => $item) {


//                dd($item['birthDate']);

                $newRequestDate = date("Y-m-d", strtotime($item['requestCreateDate']));
                $persianRequestDate = PersianCalendar::mds_date("Y-m-d", strtotime($newRequestDate));

                $persianCreateAccountDate = PersianCalendar::mds_date("Y", strtotime($item['accountCreatedDate'] . '-1-1'));

                $persianBirthDate = PersianCalendar::mds_date("Y-m-d", strtotime($item['birthDate']));
                $finalPersianBirthDate = str_replace("-", "/", $persianBirthDate);


                $installmentPayments[] = ModelSerializer::parse($item, InstallmentRequestViewFormModel::class);
                $installmentPayments[$key]->setRequestCreateDate($persianRequestDate);
                $installmentPayments[$key]->setAccountCreatedDate($persianCreateAccountDate);
                $installmentPayments[$key]->setBirthDate($finalPersianBirthDate);

            }
        }

        $allRequests = count($installmentPayments);
        $allAcceptedRequests = 0;
        $allRejectedRequests = 0;
        $allWaitingRequests = 0;

        foreach ($installmentPayments as $installmentPayment) {
            if ($installmentPayment->getRequestStatus() == 'accepted') {
                $allAcceptedRequests++;
            }
            if ($installmentPayment->getRequestStatus() == 'rejected') {
                $allRejectedRequests++;
            }
            if ($installmentPayment->getRequestStatus() == 'waiting') {
                $allWaitingRequests++;
            }
        }

//        dd($installmentPayments);

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
        if ($genderResponse->getContent()) {
            foreach ($genderResponse->getContent() as $item) {
                $genders[] = ModelSerializer::parse($item, GenderStatusModel::class);
            }
        }


        $marriageRequest = new Req(Servers::Repository, Repository::Person, 'get_all_marriage_statuses');
        $marriageResponse = $marriageRequest->send();
        $marriages = [];
        if ($marriageResponse->getContent()) {
            foreach ($marriageResponse->getContent() as $item) {
                $marriages[] = ModelSerializer::parse($item, MarriageStatusModel::class);
            }
        }


        $jobStatusRequest = new Req(Servers::Repository, Repository::Person, 'get_all_job_statuses');
        $jobStatusResponse = $jobStatusRequest->send();
        $jobStatuses = [];
        if ($jobStatusResponse->getContent()) {
            foreach ($jobStatusResponse->getContent() as $item) {
                $jobStatuses[] = ModelSerializer::parse($item, JobStatusModel::class);
            }
        }


        $buyTypeRequest = new Req(Servers::Accounting, 'InstallmentRequest', 'get_all_buy_types');
        $buyTypeResponse = $buyTypeRequest->send();
        $buyTypes = [];
        if ($buyTypeResponse->getContent()) {
            foreach ($buyTypeResponse->getContent() as $item) {
                $buyTypes[] = ModelSerializer::parse($item, BuyTypeModel::class);
            }
        }


        $chequeTypeRequest = new Req(Servers::Accounting, 'InstallmentRequest', 'get_all_cheque_types');
        $chequeTypeResponse = $chequeTypeRequest->send();
        $chequeTypes = [];
        if ($chequeTypeResponse->getContent()) {
            foreach ($chequeTypeResponse->getContent() as $item) {
                $chequeTypes[] = ModelSerializer::parse($item, ChequeTypeModel::class);
            }
        }


        $bankRequest = new Req(Servers::Accounting, 'InstallmentRequest', 'get_all_banks');
        $bankResponse = $bankRequest->send();
        $banks = [];
        if ($bankResponse->getContent()) {
            foreach ($bankResponse->getContent() as $item) {
                $banks[] = ModelSerializer::parse($item, ChequeTypeModel::class);
            }
        }


        $currentDate = date('Y');
        $persianCurrentDate = PersianCalendar::mds_date("Y", strtotime($currentDate));
        $year = [];
        for ($i = $persianCurrentDate; $i >= 1350; $i--) {
            $year[] = $i;
        }
        $category_is_not_selected = true;
        $selectedCategory = null;
        $newRequestButtonLabel = "ثبت درخواست کالا";
        if ($httpRequest->query->has('sc') && $httpRequest->query->get('sc')) {
            $category_is_not_selected = false;
            $selectedCategory = $httpRequest->query->get('sc');
            if ($selectedCategory == 2) {
                $newRequestButtonLabel = "ثبت درخواست خودرو";
            }
        } else {
            $canSeeUserRequests = false;
        }

//        dd($installmentPayments);
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
            'category_is_not_selected' => $category_is_not_selected,
            'selected_category' => $selectedCategory,
            'new_request_button_label' => $newRequestButtonLabel
        ]);

    }

    /**
     * @Route("/create", name="_create")
     * @param Request $httpRequest
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function create(Request $httpRequest)
    {
        $inputs = $httpRequest->request->all();

        $installmentPaymentModel = new  InstallmentRequestFormModel();

        $currentUser = AuthUser::current_user();

        if ($currentUser->getUserName()) {
            $userName = $currentUser->getUserName();
        } else {
            $userName = "";
        }
        $selectedCategory = $httpRequest->query->get('sc');
        if (!empty($inputs)) {
            /**
             * @var $installmentPaymentModel InstallmentRequestFormModel
             */
            $installmentPaymentModel = ModelSerializer::parse($inputs, InstallmentRequestFormModel::class);
            $request = new Req(Servers::Accounting, 'InstallmentRequest', 'new_installment_request');

            $persianRequestDate = PersianCalendar::mds_to_gregorian($installmentPaymentModel->getAccountCreatedDate(), 0, 0);
            $installmentPaymentModel->setAccountCreatedDate($persianRequestDate[0]);

            if ($selectedCategory == 1) {
                $installmentPaymentModel->setRequestCategory('goods');
            } else {
                $installmentPaymentModel->setRequestCategory('automobile');
            }

            $request->add_instance($installmentPaymentModel);
            $response = $request->send();

            if ($response->getStatus() == ResponseStatus::successful) {
                $this->addFlash('s', $response->getMessage());
            } else {
                $this->addFlash('f', $response->getMessage());
            }
        }

        return $this->redirect($this->generateUrl('accounting_installment_request_list') . "?sc=" . $selectedCategory);

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

//            dd($personalInformation);

            $persianBirthDate = $personalInformation->getBirthDate();

            $persianBirthDate = str_replace("/", "-", $persianBirthDate);
            $exploded = explode("-", $persianBirthDate);

            $persianRequestDate = PersianCalendar::mds_to_gregorian($exploded[0], $exploded[1], $exploded[2]);
            $persianRequestDate = implode("-", $persianRequestDate);

            $personalInformation->setBirthDate($persianRequestDate);

            $request = new Req(Servers::Accounting, 'InstallmentRequest', 'update_user_info');
            $request->add_instance($personalInformation);
            $response = $request->send();


//            dd($response);

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
            }
        }
        return $this->redirect($this->generateUrl('accounting_installment_request_list'));
    }

    /**
     * @Route("/installment/{id}", name="_installment_info")
     * @param Request $httpRequest
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function installmentRequestSingle(Request $httpRequest, $id)
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
         * @var $installmentPayments InstallmentRequestViewFormModel[]
         */
        $fetchedPayments = $response->getContent();
        foreach ($fetchedPayments as $key => $fetchedPayment) {
            if ($fetchedPayment['requestId'] != $id) {
                unset($fetchedPayments[$key]);
            }
        }
        if ($fetchedPayments != null) {
            foreach ($fetchedPayments as $key => $item) {

                $newRequestDate = date("Y-m-d", strtotime($item['requestCreateDate']));
                $persianRequestDate = PersianCalendar::mds_date("Y-m-d", strtotime($newRequestDate));

                $persianCreateAccountDate = PersianCalendar::mds_date("Y", strtotime($item['accountCreatedDate'] . '-1-1'));

                $persianBirthDate = PersianCalendar::mds_date("Y-m-d", strtotime($item['birthDate']));
                $finalPersianBirthDate = str_replace("-", "/", $persianBirthDate);

                $installmentPayments = ModelSerializer::parse($item, InstallmentRequestViewFormModel::class);
                $installmentPayments->setRequestCreateDate($persianRequestDate);
                $installmentPayments->setAccountCreatedDate($persianCreateAccountDate);
                $installmentPayments->setBirthDate($finalPersianBirthDate);
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
        if ($genderResponse->getContent()) {
            foreach ($genderResponse->getContent() as $item) {
                $genders[] = ModelSerializer::parse($item, GenderStatusModel::class);
            }
        }


        $marriageRequest = new Req(Servers::Repository, Repository::Person, 'get_all_marriage_statuses');
        $marriageResponse = $marriageRequest->send();
        $marriages = [];
        if ($marriageResponse->getContent()) {
            foreach ($marriageResponse->getContent() as $item) {
                $marriages[] = ModelSerializer::parse($item, MarriageStatusModel::class);
            }
        }


        $jobStatusRequest = new Req(Servers::Repository, Repository::Person, 'get_all_job_statuses');
        $jobStatusResponse = $jobStatusRequest->send();
        $jobStatuses = [];
        if ($jobStatusResponse->getContent()) {
            foreach ($jobStatusResponse->getContent() as $item) {
                $jobStatuses[] = ModelSerializer::parse($item, JobStatusModel::class);
            }
        }


        $buyTypeRequest = new Req(Servers::Accounting, 'InstallmentRequest', 'get_all_buy_types');
        $buyTypeResponse = $buyTypeRequest->send();
        $buyTypes = [];
        if ($buyTypeResponse->getContent()) {
            foreach ($buyTypeResponse->getContent() as $item) {
                $buyTypes[] = ModelSerializer::parse($item, BuyTypeModel::class);
            }
        }


        $chequeTypeRequest = new Req(Servers::Accounting, 'InstallmentRequest', 'get_all_cheque_types');
        $chequeTypeResponse = $chequeTypeRequest->send();
        $chequeTypes = [];
        if ($chequeTypeResponse->getContent()) {
            foreach ($chequeTypeResponse->getContent() as $item) {
                $chequeTypes[] = ModelSerializer::parse($item, ChequeTypeModel::class);
            }
        }


        $bankRequest = new Req(Servers::Accounting, 'InstallmentRequest', 'get_all_banks');
        $bankResponse = $bankRequest->send();
        $banks = [];
        if ($bankResponse->getContent()) {
            foreach ($bankResponse->getContent() as $item) {
                $banks[] = ModelSerializer::parse($item, ChequeTypeModel::class);
            }
        }


        $currentDate = date('Y');
        $persianCurrentDate = PersianCalendar::mds_date("Y", strtotime($currentDate));
        $year = [];
        for ($i = $persianCurrentDate; $i >= 1350; $i--) {
            $year[] = $i;
        }
        $category_is_not_selected = true;
        $selectedCategory = null;
        $newRequestButtonLabel = "ثبت درخواست کالا";
        if ($httpRequest->query->has('sc') && $httpRequest->query->get('sc')) {
            $category_is_not_selected = false;
            $selectedCategory = $httpRequest->query->get('sc');
            if ($selectedCategory == 2) {
                $newRequestButtonLabel = "ثبت درخواست خودرو";
            }
        } else {
            $canSeeUserRequests = false;
        }
        return $this->render('accounting/installment_request/installment.html.twig', [
            'controller_name' => 'InstallmentRequestController',
            'installmentPayment' => $fetchedPayments[array_key_first($fetchedPayments)],
            'userName' => $userName,
            'personModel' => $personModel,
            'canSeeAllUsers' => $canSeeAllUsers,
            'canSeeUserRequests' => $canSeeUserRequests,
            'canNewRequest' => $canNewRequest,
            'personalInformation' => $personalInformation,
            'genders' => $genders,
            'marriages' => $marriages,
            'jobStatuses' => $jobStatuses,
            'buyTypes' => $buyTypes,
            'chequeTypes' => $chequeTypes,
            'years' => $year,
            'banks' => $banks,
            'category_is_not_selected' => $category_is_not_selected,
            'selected_category' => $selectedCategory,
            'new_request_button_label' => $newRequestButtonLabel
        ]);

    }


}
