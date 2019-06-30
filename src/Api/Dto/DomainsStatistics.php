<?php

namespace App\Api\Dto;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use App\Controller\GetDomainsStatisticsAction;

/**
 * @ApiResource(
 *     input=false,
 *     collectionOperations={},
 *     itemOperations={
 *          "get"={
 *              "controller"=GetDomainsStatisticsAction::class,
 *              "path"="statistics/domains",
 *              "defaults"={"_api_receive"=false},
 *          },
 *     }
 * )
 *
 * @ApiFilter(PropertyFilter::class, arguments={"parameterName": "properties", "overrideDefaultProperties": false})
 *
 * Class UsersStatistics
 */
final class DomainsStatistics
{
    /**
     * @var \DateTime The request time
     *
     * @ApiProperty(identifier=true)
     */
    public $time;

    /**
     * @var int[] the top site
     */
    public $tops = ['ascent' => [], 'descent' => [], 'stable' => []];

    /**
     * DomainsStatistics constructor.
     */
    public function __construct()
    {
        $this->time = time();
    }
}
