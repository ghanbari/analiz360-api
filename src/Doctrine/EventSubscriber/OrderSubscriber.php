<?php

namespace App\Doctrine\EventSubscriber;

use App\Entity\DomainWatcher;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\Wallet;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zarinpal\Zarinpal;

class OrderSubscriber implements EventSubscriber
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var ParameterBagInterface
     */
    private $parameters;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * WalletSubscriber constructor.
     *
     * @param Security              $security
     * @param LoggerInterface       $logger
     * @param TranslatorInterface   $translator
     * @param ParameterBagInterface $parameters
     * @param RouterInterface       $router
     * @param RequestStack          $requestStack
     */
    public function __construct(Security $security, LoggerInterface $logger, TranslatorInterface $translator, ParameterBagInterface $parameters, RouterInterface $router, RequestStack $requestStack)
    {
        $this->translator = $translator;
        $this->security = $security;
        $this->parameters = $parameters;
        $this->router = $router;
        $this->logger = $logger;
        $this->requestStack = $requestStack;
    }

    public function getSubscribedEvents()
    {
        return ['prePersist', 'postPersist'];
    }

    public function prePersist(LifecycleEventArgs $event)
    {
        $order = $event->getEntity();

        if (!$order instanceof Order) {
            return;
        }

        $this->addTransaction($order, $event->getEntityManager());
        $this->preAddProductService($order, $event->getEntityManager());
    }

    public function postPersist(LifecycleEventArgs $event)
    {
        $order = $event->getEntity();

        if (!$order instanceof Order) {
            return;
        }

        $this->postAddProductService($order, $event->getEntityManager());
    }

    private function addTransaction(Order $order, EntityManager $entityManager)
    {
        if (Wallet::UNIT_RIALS === $order->getProduct()->getUnit()
            || Product::TYPE_LIZ_PACK === $order->getProduct()->getType()
            || in_array('ROLE_ADMIN', $order->getUser()->getRoles())
        ) {
            return;
        }

        $wallet = Wallet::createFromOrder($order);
        $entityManager->persist($wallet);
    }

    private function preAddProductService(Order $order, EntityManager $entityManager)
    {
        switch ($order->getProduct()->getType()) {
            case Product::TYPE_LIZ_PACK:
                break;
            case Product::TYPE_ALEXA_ADD_DOMAIN:
            case Product::TYPE_ALEXA_WATCH_DOMAIN:
                $this->addDomainWatcher($order, $entityManager);
                break;
            case Product::TYPE_ALEXA_INCREASE_FREE_WATCH_SIZE:
                // dont need any extra action
                break;
        }
    }

    private function postAddProductService(Order $order, EntityManager $entityManager)
    {
        switch ($order->getProduct()->getType()) {
            case Product::TYPE_LIZ_PACK:
                $this->openGateway($order, $entityManager);
                break;
            case Product::TYPE_ALEXA_ADD_DOMAIN:
            case Product::TYPE_ALEXA_WATCH_DOMAIN:
                break;
            case Product::TYPE_ALEXA_INCREASE_FREE_WATCH_SIZE:
                // dont need any extra action
                break;
        }
    }

    private function addDomainWatcher(Order $order, EntityManager $entityManager)
    {
        if (in_array('ROLE_ADMIN', $order->getUser()->getRoles())) {
            return;
        }

        $info = $order->getInfo();
        $domain = !isset($info['domain']) ? null
            : $entityManager->getRepository('App:Domain')->findOneByDomain($info['domain']);

        if (!$domain) {
            throw new BadRequestHttpException('Domain is not exists');
        }

        $watcher = new DomainWatcher($domain, $order->getUser(), $order->getProduct());
        $entityManager->persist($watcher);
    }

    private function openGateway(Order $order, EntityManager $entityManager)
    {
        $zarinpal = $this->parameters->get('zarinpal');
        $env = $this->parameters->get('kernel.environment');
        $email = $this->security->getUser()->getEmail();
        $phone = $this->security->getUser()->getPhone();

        $zarinpal = new Zarinpal($zarinpal['id']);
        if ('dev' === $env) {
            $zarinpal->enableSandbox();
        }

        $request = $this->requestStack->getMasterRequest();
        $callbackParams = ['orderId' => $order->getId()];
        if ($request->query->has('callback')) {
            $callbackParams['callback'] = $request->query->get('callback').'?order='.$order->getId();
        }
        $callback =
            $this->router->generate('verify_payment', $callbackParams, Router::ABSOLUTE_URL);

        $results = $zarinpal->request(
            $callback,
            $order->getProduct()->getPrice(),
            $order->getProduct()->getTitle(),
            $email,
            $phone
        );

        if (isset($results['Authority'])) {
            $results['Authority'];
            $results['gateway'] = $zarinpal->redirectUrl();
            $order->setInfo($results);
            $entityManager->flush($order);
        } else {
            $errors = [
                '-1' => 'Insufficient information',
                '-2' => 'IP or Merchant Code is not correct',
                '-3' => 'Amount should be greater than 1000 IRR',
                '-4' => 'The verification level should be above silver',
                '-11' => 'Couldn\'t find the requested payment',
                '-21' => 'No financial action found for this transaction',
                '-22' => 'Unsuccessful transaction',
                '-33' => 'Transaction amount is not equal to payed amount',
                '-54' => 'The payment request has been archived',
            ];

            $results['details'] = $errors[$results['error']];
            $this->logger->critical(sprintf('Payment gateway error: %s', $errors[$results['error']]));
            $order->setInfo($results);
            $order->setStatus(Order::STATUS_FAIL);
            $entityManager->flush($order);
            throw new ServiceUnavailableHttpException(0, $errors[$results['error']]);
        }
    }
}
