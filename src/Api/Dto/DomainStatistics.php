<?php

namespace App\Api\Dto;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use App\Controller\GetDomainStatisticsAction;
use App\Entity\Domain;

/**
 * @ApiResource(
 *     collectionOperations={},
 *     itemOperations={
 *          "get"={
 *              "controller"=GetDomainStatisticsAction::class,
 *              "path"="statistics/domains/{id}",
 *              "defaults"={"_api_receive"=false},
 *          },
 *     }
 * )
 *
 * @ApiFilter(DateFilter::class, properties={"date"})
 * @ApiFilter(PropertyFilter::class, arguments={"parameterName": "properties", "overrideDefaultProperties": false})
 *
 * Class UsersStatistics
 */
final class DomainStatistics
{
    /**
     * @var string
     *
     * @ApiProperty(identifier=true)
     */
    public $domain;

    /**
     * @var int
     */
    public $globalRank;

    /**
     * @var array
     */
    public $globalRanks;

    /**
     * @var int
     */
    public $localRank;

    /**
     * @var array
     */
    public $localRanks;

    /**
     * @var array
     */
    public $bounceRates;

    /**
     * @var array
     */
    public $pageViews;

    /**
     * @var array
     */
    public $timeOnSites;

    /**
     * DomainStatistics constructor.
     *
     * @param Domain $domain
     */
    public function __construct(Domain $domain)
    {
        $this->domain = $domain->getDomain();
    }
}
