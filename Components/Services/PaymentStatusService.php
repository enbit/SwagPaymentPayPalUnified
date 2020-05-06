<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPaymentPayPalUnified\Components\Services;

use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use Shopware\Models\Payment\Payment;
use SwagPaymentPayPalUnified\Components\Exception\OrderNotFoundException;
use SwagPaymentPayPalUnified\Components\PaymentStatus;

class PaymentStatusService
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    public function __construct(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
    }

    /**
     * @param string $parentPayment
     * @param int    $paymentStateId
     */
    public function updatePaymentStatus($parentPayment, $paymentStateId)
    {
        /** @var Order|null $orderModel */
        $orderModel = $this->modelManager->getRepository(Order::class)->findOneBy(['temporaryId' => $parentPayment]);

        if (!($orderModel instanceof Order)) {
            throw new OrderNotFoundException('temporaryId', $parentPayment);
        }

        /** @var Status|null $orderStatusModel */
        $orderStatusModel = $this->modelManager->getRepository(Status::class)->find($paymentStateId);

        $orderModel->setPaymentStatus($orderStatusModel);
        if ($paymentStateId === PaymentStatus::PAYMENT_STATUS_PAID
            || $paymentStateId === PaymentStatus::PAYMENT_STATUS_PARTIALLY_PAID
        ) {
            $orderModel->setClearedDate(new \DateTime());
        }

        $this->modelManager->flush($orderModel);
    }


    /**
     * @param string $transactionId
     * @param int    $paymentStateId
     */
    public function updatePaymentStatusOldOrders($transactionId, $paymentStateId)
    {
        /** @var Payment $payment */
        $payment = $this->modelManager->getRepository(Payment::class)->findOneBy(['name' => 'paypal']);
        /** @var Order|null $orderModel */
        $orderModel = $this->modelManager->getRepository(Order::class)->findOneBy([
            'transactionId' => $transactionId,
            'paymentId' => $payment->getId(),
        ]);

        if (!($orderModel instanceof Order)) {
            return false;
        }

        /** @var Status|null $orderStatusModel */
        $orderStatusModel = $this->modelManager->getRepository(Status::class)->find($paymentStateId);

        $orderModel->setPaymentStatus($orderStatusModel);
        if ($paymentStateId === PaymentStatus::PAYMENT_STATUS_PAID
            || $paymentStateId === PaymentStatus::PAYMENT_STATUS_PARTIALLY_PAID
        ) {
            $orderModel->setClearedDate(new \DateTime());
        }

        $this->modelManager->flush($orderModel);
        return true;
    }
}
