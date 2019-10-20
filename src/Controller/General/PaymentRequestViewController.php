<?php

namespace App\Controller\General;

use Matican\Models\Accounting\InvoiceModel;
use Matican\Models\Accounting\PaymentMethodModel;
use Matican\Models\Accounting\PaymentModel;
use Matican\Models\Accounting\PaymentRequestModel;
use Matican\ModelSerializer;
use Matican\Core\Entities\Accounting;
use Matican\Core\Servers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Matican\Core\Transaction\Request as Req;

class PaymentRequestViewController extends AbstractController
{
    /**
     * @Route("/general/payment/request/view", name="general_payment_request_view")
     * @param $invoiceModel InvoiceModel
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index($invoiceModel)
    {

        /**
         * @var $paymentRequestModel PaymentRequestModel
         */
        $paymentRequestModel = ModelSerializer::parse($invoiceModel->getInvoicePaymentRequest(), PaymentRequestModel::class);


        /**
         * @var $payments PaymentModel[]
         */
        $payments = [];
        if ($paymentRequestModel->getPaymentRequestPayments()) {
            foreach ($paymentRequestModel->getPaymentRequestPayments() as $payment) {
                $payments[] = ModelSerializer::parse($payment, PaymentModel::class);
            }
        }

        $allPaymentMethodRequest = new Req(Servers::Accounting, Accounting::PaymentRequest, 'get_payment_methods');
        $allPaymentMethodResponse = $allPaymentMethodRequest->send();

        /**
         * @var $paymentMethods PaymentMethodModel[]
         */
        $paymentMethods = [];
        if ($allPaymentMethodResponse->getContent()) {
            foreach ($allPaymentMethodResponse->getContent() as $paymentMethod) {
                $paymentMethods[] = ModelSerializer::parse($paymentMethod, PaymentMethodModel::class);
            }
        }

        return $this->render('general/payment_request_view/index.html.twig', [
            'controller_name' => 'PaymentRequestViewController',
            'paymentRequestModel' => $paymentRequestModel,
            'payments' => $payments,
            'paymentMethods' => $paymentMethods,
            'invoiceModel' => $invoiceModel,
        ]);
    }
}
