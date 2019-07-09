<?php

namespace App\Repository;

use App\Entity\EmailMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method EmailMessage|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmailMessage|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmailMessage[]    findAll()
 * @method EmailMessage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmailMessageRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, EmailMessage::class);

        $this->addSendToAll();
    }

    private function addSendToAll()
    {
        $query = 'CALL send_email_message_to_all(:template, :args, :time, :priority, :senderEmail)';
        $this->getClassMetadata()->addNamedNativeQuery(['name' => 'send_message_to_all', 'query' => $query, 'resultClass' => EmailMessage::class]);
    }

    public function sendMessageToAll(EmailMessage $message)
    {
        return $this->createNativeNamedQuery('send_message_to_all')
            ->setParameter('template', $message->getTemplate())
            ->setParameter('args', json_encode($message->getArguments()))
            ->setParameter('time', $message->getTime())
            ->setParameter('priority', $message->getPriority())
            ->setParameter('senderEmail', $message->getSenderEmail())
            ->execute();
    }

    /**
     * @param int $count
     * @param int $agoSecond
     * @param int $laterSecond
     * @return EmailMessage[]
     * @throws \Exception
     */
    public function pullQueue(int $count, int $agoSecond, int $laterSecond)
    {
        $qb = $this->createQueryBuilder('em');
        $qb->where($qb->expr()->between('em.time', ':fromDate', ':tillDate'))
            ->andWhere($qb->expr()->eq('em.status', ':status'))
            ->setParameter('fromDate', new \DateTime("-$agoSecond seconds"))
            ->setParameter('tillDate', new \DateTime("+$laterSecond seconds"))
            ->setParameter('status', EmailMessage::STATUS_PREPARE)
            ->setMaxResults($count);

        return $qb->getQuery()
            ->getResult();
    }
}
