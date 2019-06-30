<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\Wallet;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zarinpal\Zarinpal;

class verifyPaymentAction extends AbstractController
{
    /**
     * @param TranslatorInterface $translator
     * @param Request             $request
     * @param $orderId
     *
     * @return Response
     */
    public function __invoke(TranslatorInterface $translator, Request $request, $orderId): Response
    {
        $zarinpal = $this->getParameter('zarinpal');
        $env = $this->getParameter('kernel.environment');

        $zarinpal = new Zarinpal($zarinpal['id']);
        if ('dev' === $env) {
            $zarinpal->enableSandbox();
        }

        /** @var Order $order */
        $order = $this->getDoctrine()->getRepository('App:Order')->find($orderId);

        if (!$order) {
            return $this->render('payment/verify.html.twig', ['error' => $translator->trans('Order is not exists.')]);
        }

        if (Order::STATUS_FAIL === $order->getStatus()) {
            return $this->render('payment/verify.html.twig', ['error' => $translator->trans('Order was failed.')]);
        }

        $orderInfo = $order->getInfo();
        $authority = $request->query->get('Authority');
        if (Product::TYPE_LIZ_PACK !== $order->getProduct()->getType()) {
            return $this->render('payment/verify.html.twig', ['error' => $translator->trans('Order type is not payment type.')]);
        }

        if ($orderInfo['Authority'] !== $authority) {
            return $this->render('payment/verify.html.twig', ['error' => $translator->trans('Order Authority is not same with request Authority.')]);
        }

        $result = $zarinpal->verify($order->getProduct()->getPrice(), $authority);
        if ('success' === $result['Status']) {
            $this->addTransaction($order);

            $response = $this->render('payment/verify.html.twig');
        } elseif (in_array($result['Status'], ['canceled', 'error'])) {
            $order->setStatus('canceled' === $result['Status'] ? Order::STATUS_CANCEL : Order::STATUS_FAIL);
            $orderInfo['Status'] = $result['Status'];
            $orderInfo['error'] = $result['error'];
            $orderInfo['errorMessage'] = $this->getErrorMessage($result['error']);
            $orderInfo['errorInfo'] = $result['errorInfo'];
            $order->setInfo($orderInfo);

            $response = $this->render('payment/verify.html.twig', ['error' => $orderInfo['errorMessage']]);
        } elseif ('verified_before' === $result['Status']) {
            $walletRepo = $this->getDoctrine()->getRepository('App:Wallet');
            $wasApplied = $walletRepo->findBy(['order' => $order, 'unit' => Wallet::UNIT_LIZ, 'type' => Wallet::TYPE_INCOME]);
            if (!$wasApplied) {
                $this->addTransaction($order);
            }

            $response = $this->render('payment/verify.html.twig');
        }

        $this->getDoctrine()->getManager()->flush();

        return $response;
    }

    private function getErrorMessage($error)
    {
        $errors = [
            '-1' => 'اطلاعات کافی نیست',
            '-2' => 'آی پی یا کد فروشگاه درست نیست',
            '-3' => 'مقدار باید بیش از 1000 ﷼ باشد',
            '-4' => 'سطح تایید باید بالاتر از نقره باشد',
            '-11' => 'درخواست پرداخت یافت نشد',
            '-21' => 'هیچ اقدام مالی برای این معامله یافت نشد',
            '-22' => 'تراکنش ناموفق',
            '-33' => 'مقدار تراکنش برابر با مبلغ پرداخت شده نیست',
            '-54' => 'درخواست پرداخت آرشیو شده است',
        ];

        return array_key_exists($error, $errors) ? $errors[$error] : $error;
    }

    private function addTransaction(Order $order)
    {
        $service = $order->getProduct()->getService();
        $walletIncome = new Wallet(
            $order->getUser(),
            $service['lizAmount'],
            Wallet::TYPE_INCOME,
            $order->getProduct()->getTitle(),
            Wallet::UNIT_LIZ
        );

        $walletIncome->setOrder($order);
        $order->setStatus(Order::STATUS_SUCCESS);
        $walletOutcome = Wallet::createFromOrder($order);
        $this->getDoctrine()->getManager()->persist($walletIncome);
        $this->getDoctrine()->getManager()->persist($walletOutcome);
    }
}
