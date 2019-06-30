<?php

namespace App\Controller;

use App\Api\Dto\Config;
use App\Entity\ScheduledMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class GetConfigAction extends AbstractController
{
    /**
     * @Route(
     *     path="/api/config",
     *     name="api_get_config",
     *     methods={"GET"},
     *     defaults={
     *          "_api_respond"=true,
     *          "_api_normalization_context"={"api_sub_level"=true}
     *     }
     * )
     *
     * @return Config
     */
    public function __invoke(): Config
    {
        $registration = $this->getParameter('registration');
        $config = new Config();
        $config->registration['type'] = $registration['username'];
        $config->scheduleMessage['types'] = [ScheduledMessage::MESSAGE_TYPE_SMS, ScheduledMessage::MESSAGE_TYPE_EMAIL];
        $config->table = ['itemsPerPage' => 30];
        $config->title = getenv('APP_TITLE');
        $config->version = getenv('APP_VERSION');

        return $config;
    }
}
