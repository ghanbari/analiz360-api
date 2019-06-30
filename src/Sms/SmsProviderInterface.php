<?php

namespace App\Sms;

use App\Entity\SmsMessage;
use App\Entity\SmsOutbox;

interface SmsProviderInterface
{
    public function send(SmsMessage $message): ? SmsOutbox;

    public function sendBatch(array $messages);

    public function checkStatus(SmsOutbox $report);

    public function support($provider);
}
