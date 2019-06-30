<?php

namespace App\Api\Dto;

/**
 * Class Config.
 */
final class Config
{
    public $registration = [
        'type' => '',
    ];

    public $scheduleMessage = [
        'types' => [],
    ];

    public $table = [
        'itemsPerPage' => 30,
    ];

    public $title = '';

    public $version = '';
}
