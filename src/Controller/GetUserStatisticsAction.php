<?php

namespace App\Controller;

use App\Api\Dto\UserStatistics;
use App\Entity\SalesReport;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class GetUserStatisticsAction extends AbstractController
{
    public function __invoke(Request $request): UserStatistics
    {
        $properties = $request->query->get('properties', []);
        $userId = $request->attributes->get('id');

        $statistic = new UserStatistics($userId);

        if (empty($properties) or in_array('lastPurchaseAt', $properties)) {
            $statistic->lastPurchaseAt = $this->getLastPurchaseAt($userId);
        }

        if (empty($properties) or in_array('purchaseCount', $properties)) {
            $statistic->purchaseCount = $this->getPurchaseCount($userId) ?? 0;
        }

        if (empty($properties) or in_array('purchaseTotal', $properties)) {
            $statistic->purchaseTotal = $this->getPurchaseTotal($userId) ?? 0;
        }

        return $statistic;
    }

    private function getLastPurchaseAt($userId)
    {
        /** @var SalesReport $lastPurchase */
        $lastPurchase = $this->getDoctrine()->getRepository('App:SalesReport')->getUserLastPurchase($userId);
        if ($lastPurchase) {
            return $lastPurchase->getCreatedAt()->format('Y-m-d H:i:s');
        }
    }

    private function getPurchaseCount($userId)
    {
        return $lastPurchase = $this->getDoctrine()->getRepository('App:SalesReport')->getUserPurchaseCount($userId);
    }

    private function getPurchaseTotal($userId)
    {
        return $lastPurchase = $this->getDoctrine()->getRepository('App:SalesReport')->getUserPurchaseTotal($userId);
    }
}
