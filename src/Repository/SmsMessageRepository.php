<?php

namespace App\Repository;

use App\Entity\SmsMessage;
use App\Entity\SmsOutbox;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method SmsMessage|null find($id, $lockMode = null, $lockVersion = null)
 * @method SmsMessage|null findOneBy(array $criteria, array $orderBy = null)
 * @method SmsMessage[]    findAll()
 * @method SmsMessage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SmsMessageRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, SmsMessage::class);

        $this->addPullQuery();
        $this->addSendToAll();
    }

    private function addSendToAll()
    {
        $query = 'CALL send_sms_message_to_all(:time, :message, :maxTryCount, :timeout, :priority, :provider)';
        $this->getClassMetadata()->addNamedNativeQuery(['name' => 'send_message_to_all', 'query' => $query, 'resultClass' => SmsMessage::class]);
    }

    /**
     * @param int $count
     * @param int $agoSecond
     * @param int $laterSecond
     *
     * @return SmsMessage[]
     *
     * @throws \Exception
     */
    public function pullQueue(int $count, int $agoSecond, int $laterSecond)
    {
        $pull = $this->createNativeNamedQuery('pull');
        $pull->setParameter('limit', $count);
        $pull->setParameter('fromDate', new \DateTime("-$agoSecond seconds"));
        $pull->setParameter('tillDate', new \DateTime("+$laterSecond seconds"));
        $pull->setParameter('status', [SmsOutbox::STATUS_DELIVERED, SmsOutbox::STATUS_BLOCKED]);

        return $pull->getResult();
    }

    public function sendMessageToAll(SmsMessage $message)
    {
        return $this->createNativeNamedQuery('send_message_to_all')
            ->setParameter('time', $message->getTime())
            ->setParameter('message', $message->getMessage())
            ->setParameter('maxTryCount', $message->getMaxTryCount())
            ->setParameter('timeout', $message->getTimeout())
            ->setParameter('priority', $message->getPriority())
            ->setParameter('provider', $message->getProvider())
            ->execute();
    }

    private function addPullQuery()
    {
        /**
         * (.
        sms_outbox. STATUS IS NULL
        OR sms_outbox. STATUS = 0
        OR (
        sms_outbox. STATUS NOT IN (:status)
        AND UNIX_TIMESTAMP() - UNIX_TIMESTAMP(sms_outbox.send_time) >= sms_message.timeout
        )
        )
        AND
         */
        $query = '
            SELECT
                *,
                count(message_id) AS try_count,
                max(send_time) AS send_time
            FROM
                (
                    SELECT
                        sms_message.*,
                        (sms_outbox.message_id) AS message_id,
                        sms_outbox.send_time AS send_time
                    FROM
                        sms_message
                    LEFT JOIN sms_outbox ON sms_message.id = sms_outbox.message_id
                    WHERE
                        (sms_message.time BETWEEN :fromDate AND :tillDate)
                        AND sms_message.status not in (:status)
                    ORDER BY
                        sms_message.priority DESC,
                        sms_outbox.send_time DESC
                ) AS res
            GROUP BY
                res.id
            HAVING
                (res.max_try_count IS NULL
                  OR try_count < res.max_try_count)
                AND (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(send_time) >= res.timeout)
            LIMIT :limit';

        $query = '
            SELECT 
                sms_message.*,
                MAX(send_time) AS send_time,
                COUNT(message_id) AS try_count
            FROM
                sms_message
            LEFT JOIN sms_outbox ON sms_message.id = sms_outbox.message_id
            WHERE
                (sms_message.time BETWEEN :fromDate AND :tillDate)
                AND sms_message.status not in (:status)
            GROUP BY id
            HAVING (max_try_count IS NULL OR try_count < max_try_count)
                AND (ISNULL(send_time) OR (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(send_time) >= timeout))
            ORDER BY sms_message.priority DESC , send_time DESC
            LIMIT :limit
        ';

        $this->getClassMetadata()->addNamedNativeQuery(['name' => 'pull', 'query' => $query, 'resultClass' => SmsMessage::class]);
    }
}
