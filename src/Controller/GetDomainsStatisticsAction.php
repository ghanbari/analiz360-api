<?php

namespace App\Controller;

use App\Api\Dto\DomainsStatistics;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class GetDomainsStatisticsAction extends AbstractController
{
    public function __invoke(Request $request): DomainsStatistics
    {
        $properties = $request->query->get('properties', []);

        $statistic = new DomainsStatistics();

        if (empty($properties) or in_array('tops', $properties)) {
            $statistic->tops = $this->getTops();
        }

        return $statistic;
    }

    private function getTops()
    {
        $tops['ascent'] = $this->getDoctrine()->getRepository('App:Domain')->getTopAscent();
        $tops['descent'] = $this->getDoctrine()->getRepository('App:Domain')->getTopDescent();
        $tops['stable'] = $this->getDoctrine()->getRepository('App:Domain')->getTopStable();

        return $tops;
    }
}
