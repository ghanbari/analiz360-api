<?php

namespace App\Api\Dto;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use App\Controller\GetUserStatisticsAction;

/**
 * @ApiResource(
 *     input=false,
 *     collectionOperations={},
 *     itemOperations={
 *          "get"={
 *              "controller"=GetUserStatisticsAction::class,
 *              "path"="statistics/users/{id}",
 *              "defaults"={"_api_receive"=false},
 *          },
 *     }
 * )
 *
 * @ApiFilter(PropertyFilter::class, arguments={"parameterName": "properties", "overrideDefaultProperties": false})
 *
 * Class UsersStatistics
 */
final class UserStatistics
{
    /**
     * UserStatistics constructor.
     *
     * @param int $userId
     */
    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    /**
     * @var int total count of users
     *
     * @ApiProperty(identifier=true)
     */
    public $userId;

    /**
     * @var int total count of users
     */
    public $purchaseCount;

    /**
     * @var int[] The percentage of men & women
     */
    public $purchaseTotal;

    /**
     * @var int[] The percentage of men & women
     */
    public $lastPurchaseAt;
}
