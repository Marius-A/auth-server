<?php
namespace AppBundle\Command;

use AppBundle\Entity\Client;
use AppBundle\Entity\User;
use AppBundle\Repository\ClientRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateUserOnClientCommand  extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('migrate:user_per_client')->setDescription('Add User per client');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $manager */
        $manager = $this->getContainer()->get('doctrine')->getManager();

        /** @var ClientRepository $clientRepo */
        $clients = $manager->getRepository('AppBundle:Client')->findBy(array('clientUser' => null));

        /** @var Client $client */
        foreach ($clients as $client) {
            $username = str_replace(' ', '_', $client->getName());
            $username = strtolower($username);

            $clientUser = new User();
            $clientUser->setEmail($username);
            $clientUser->setUsername($username);
            $clientUser->setStatus(true);

            $manager->persist($clientUser);
            $client->setClientUser($clientUser);
            $manager->flush();
        }
    }
}