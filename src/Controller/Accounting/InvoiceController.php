<?php

namespace App\Controller\Accounting;

use Matican\Models\Accounting\InvoiceGroupModel;
use Matican\Models\Accounting\InvoiceItemModel;
use Matican\Models\Accounting\InvoiceModel;
use Matican\Models\Accounting\PaymentMethodModel;
use Matican\Models\Accounting\PaymentModel;
use Matican\Models\Accounting\PaymentRequestModel;
use Matican\Models\Accounting\PaymentStatusModel;
use Matican\Models\Accounting\PaymentTypeModel;
use Matican\ModelSerializer;
use Matican\Models\Repository\CompanyModel;
use Matican\Models\Repository\PersonModel;
use Matican\Authentication\AuthUser;
use Matican\Permissions\ServerPermissions;
use Matican\Core\Entities\Accounting;
use Matican\Core\Entities\Repository;
use Matican\Core\Servers;
use Matican\ResponseStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Matican\Core\Transaction\Request as Req;

/**
 * @Route("/accounting/invoice", name="accounting_invoice")
 */
class InvoiceController extends AbstractController
{
    /**
     * @Route("/list", name="_list")
     */
    public function fetchAll()
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::accounting_invoice_all)) {
            return $this->redirect($this->generateUrl('accounting_invoice_create'));
        }

        $request = new Req(Servers::Accounting, Accounting::Invoice, 'all');
        $response = $request->send();
        /**
         * @var $invoices InvoiceModel[]
         */
        $invoices = [];
        if ($response->getContent()) {
//            dd($response);
            foreach ($response->getContent() as $invoice) {
                $invoices[] = ModelSerializer::parse($invoice, InvoiceModel::class);
            }
        }

//        dd($invoices);

        return $this->render('accounting/invoice/list.html.twig', [
            'controller_name' => 'InvoiceController',
            'invoices' => $invoices,
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
        if (!AuthUser::if_is_allowed(ServerPermissions::accounting_invoice_new)) {
            return $this->redirect($this->generateUrl('accounting_gift_card_group_list'));
        }
        $inputs = $request->request->all();
        /**
         * @var $invoiceModel InvoiceModel
         */
        $invoiceModel = ModelSerializer::parse($inputs, InvoiceModel::class);
        if (!empty($inputs)) {
//            dd($invoiceModel);
            $request = new Req(Servers::Accounting, Accounting::Invoice, 'new');
            $request->add_instance($invoiceModel);
            $response = $request->send();
//            dd($response);
            if ($response->getStatus() == ResponseStatus::successful) {
                /**
                 * @var $invoiceModel InvoiceModel
                 */
                $invoiceModel = ModelSerializer::parse($response->getContent(), InvoiceModel::class);
                $this->addFlash('s', $response->getMessage());
                return $this->redirect($this->generateUrl('accounting_invoice_edit', ['id' => $invoiceModel->getInvoiceId()]));
            } else {
                $this->addFlash('s', $response->getMessage());
            }
        }


        $allPersonsRequest = new Req(Servers::Repository, Repository::Person, 'all');
        $allPersonsResponse = $allPersonsRequest->send();

        /**
         * @var $persons PersonModel[]
         */
        $persons = [];
        if ($allPersonsResponse->getContent()) {
            foreach ($allPersonsResponse->getContent() as $person) {
                $persons[] = ModelSerializer::parse($person, PersonModel::class);
            }
        }

        $allCompaniesRequest = new Req(Servers::Repository, Repository::Company, 'all');
        $allCompaniesResponse = $allCompaniesRequest->send();

        /**
         * @var $companies CompanyModel[]
         */
        $companies = [];
        if ($allCompaniesResponse->getContent()) {
            foreach ($allCompaniesResponse->getContent() as $company) {
                $companies[] = ModelSerializer::parse($company, CompanyModel::class);
            }
        }

        $allInvoiceGroupsRequest = new Req(Servers::Accounting, Accounting::Invoice, 'get_all_invoice_groups');
        $allInvoiceGroupsResponse = $allInvoiceGroupsRequest->send();
        $invoiceGroups = $allInvoiceGroupsResponse->getContent();


        return $this->render('accounting/invoice/create.html.twig', [
            'controller_name' => 'InvoiceController',
            'invoiceModel' => $invoiceModel,
            'persons' => $persons,
            'companies' => $companies,
            'invoiceGroups' => $invoiceGroups,
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
        if (!AuthUser::if_is_allowed(ServerPermissions::accounting_invoice_fetch)) {
            return $this->redirect($this->generateUrl('accounting_invoice_list'));
        }
        $inputs = $request->request->all();

        /**
         * @var $invoiceModel InvoiceModel
         */
        $invoiceModel = ModelSerializer::parse($inputs, InvoiceModel::class);
        $invoiceModel->setInvoiceId($id);
        $request = new Req(Servers::Accounting, Accounting::Invoice, 'fetch');
        $request->add_instance($invoiceModel);
        $response = $request->send();
//        dd($response);
        /**
         * @var $invoiceModel InvoiceModel
         */
        $invoiceModel = ModelSerializer::parse($response->getContent(), InvoiceModel::class);

//        dd($invoiceModel);

//        /**
//         * @var $invoiceItems InvoiceItemModel[]
//         */
//        $invoiceItems = [];
//        if ($invoiceModel->getInvoiceItems()) {
//            foreach ($invoiceModel->getInvoiceItems() as $invoiceItem) {
//                $invoiceItems[] = ModelSerializer::parse($invoiceItem, InvoiceItemModel::class);
//            }
//        }


        return $this->render('accounting/invoice/edit.html.twig', [
            'controller_name' => 'InvoiceController',
            'invoiceModel' => $invoiceModel,
//            'invoiceItems' => $invoiceItems,
        ]);
    }


    /**
     * @Route("/add-invoice-item/{invoice_id}", name="_add_invoice_item")
     * @param $invoice_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function addInvoiceItem($invoice_id, Request $request)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::accounting_invoice_add_invoice_item)) {
            return $this->redirect($this->generateUrl('accounting_invoice_edit', ['id' => $invoice_id]));
        }
        $inputs = $request->request->all();

        /**
         * @var $invoiceItemModel InvoiceItemModel
         */
        $invoiceItemModel = ModelSerializer::parse($inputs, InvoiceItemModel::class);
        $invoiceItemModel->setInvoiceId($invoice_id);
        $currentPrice = str_replace(',', '', $inputs['invoiceItemCurrentPrice']);
        $discountPrice = str_replace(',', '', $inputs['invoiceItemDiscountPrice']);
        $invoiceItemModel->setInvoiceItemCurrentPrice($currentPrice);
        $invoiceItemModel->setInvoiceItemDiscountPrice($discountPrice);
        $request = new Req(Servers::Accounting, Accounting::Invoice, 'add_invoice_item');
//        dd($invoiceItemModel);
        $request->add_instance($invoiceItemModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('accounting_invoice_edit', ['id' => $invoice_id]));

    }

    /**
     * @Route("/remove-invoice-item/{invoice_id}/{invoice_item_id}", name="_remove_invoice_item")
     * @param $invoice_id
     * @param $invoice_item_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function removeInvoiceItem($invoice_id, $invoice_item_id)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::accounting_invoice_remove_invoice_item)) {
            return $this->redirect($this->generateUrl('accounting_invoice_edit', ['id' => $invoice_id]));
        }
        $invoiceItemModel = new InvoiceItemModel();
        $invoiceItemModel->setInvoiceId($invoice_id);
        $invoiceItemModel->setInvoiceItemId($invoice_item_id);
        $request = new Req(Servers::Accounting, Accounting::Invoice, 'remove_invoice_item');
        $request->add_instance($invoiceItemModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('accounting_invoice_edit', ['id' => $invoice_id]));
    }


    /**
     * @Route("/confirm-invoice/{invoice_id}", name="_confirm_invoice")
     * @param $invoice_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function confirmInvoice($invoice_id)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::accounting_giftcardgroup_fetch)) {
            return $this->redirect($this->generateUrl('accounting_invoice_edit', ['id' => $invoice_id]));
        }
        $invoiceModel = new InvoiceModel();
        $invoiceModel->setInvoiceId($invoice_id);
        $request = new Req(Servers::Accounting, Accounting::Invoice, 'finalize_invoice');
        $request->add_instance($invoiceModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('accounting_invoice_edit', ['id' => $invoice_id]));
    }

    /**
     * @Route("/reject-invoice/{invoice_id}", name="_reject_invoice")
     * @param $invoice_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function rejectInvoice($invoice_id)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::accounting_invoice_rethink_invoice)) {
            return $this->redirect($this->generateUrl('accounting_invoice_edit', ['id' => $invoice_id]));
        }
        $invoiceModel = new InvoiceModel();
        $invoiceModel->setInvoiceId($invoice_id);
        $request = new Req(Servers::Accounting, Accounting::Invoice, 'rethink_invoice');
        $request->add_instance($invoiceModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('s', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('accounting_invoice_edit', ['id' => $invoice_id]));
    }


    /**
     * @Route("/add-payment/{invoice_id}/{payment_request_id}", name="_add_payment")
     * @param $invoice_id
     * @param $payment_request_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function addPayment($invoice_id, $payment_request_id, Request $request)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::accounting_paymentrequest_add_payment)) {
            return $this->redirect($this->generateUrl('accounting_invoice_edit', ['id' => $invoice_id]));
        }
        $inputs = $request->request->all();

        if (!empty($inputs)) {
            /**
             * @var $paymentModel PaymentModel
             */
            $paymentModel = ModelSerializer::parse($inputs, PaymentModel::class);
            $from = explode('_', $inputs['from']);
            if ($from[0] == 'person') {
                $paymentModel->setInvoiceFromPersonId($from[1]);
            } elseif ($from[0] == 'company') {
                $paymentModel->setInvoiceFromCompanyId($from[1]);
            }

            $to = explode('_', $inputs['to']);
            if ($to[0] == 'person') {
                $paymentModel->setInvoiceToPersonId($to[1]);
            } elseif ($to[0] == 'company') {
                $paymentModel->setInvoiceToCompanyId($to[1]);
            }

            $paymentModel->setPaymentRequestId($payment_request_id);

//            dd($paymentModel);

            $request = new Req(Servers::Accounting, Accounting::PaymentRequest, 'add_payment');
            $request->add_instance($paymentModel);
            $response = $request->send();
//            dd($response);
            if ($response->getStatus() == ResponseStatus::successful) {
                $this->addFlash('s', $response->getMessage());
            } else {
                $this->addFlash('f', $response->getMessage());
            }
        }
        return $this->redirect($this->generateUrl('accounting_invoice_edit', ['id' => $invoice_id]));
    }


    /**
     * @Route("/confirm-payment/{invoice_id}/{payment_id}", name="_confirm_payment")
     * @param $invoice_id
     * @param $payment_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function confirmPayment($invoice_id, $payment_id)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::accounting_paymentrequest_confirm_payment)) {
            return $this->redirect($this->generateUrl('accounting_invoice_edit', ['id' => $invoice_id]));
        }
        $paymentStatusModel = new PaymentStatusModel();
        $paymentStatusModel->setPaymentId($payment_id);
        $request = new Req(Servers::Accounting, Accounting::PaymentRequest, 'confirm_payment');
        $request->add_instance($paymentStatusModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('accounting_invoice_edit', ['id' => $invoice_id]));
    }
}
