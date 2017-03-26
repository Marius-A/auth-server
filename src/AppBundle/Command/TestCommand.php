<?php

namespace AppBundle\Command;

use AppBundle\Entity\Role;
use AppBundle\Entity\User;
use AppBundle\Repository\RoleRepository;
use AppBundle\Service\EntityService;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:test')->setDescription('Test app functionalities');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = $this->getContainer()->get('doctrine')->getManager();
        $user = $manager->getRepository('AppBundle:User')->find(437);
        $user->setUsername('new user');

        $originalUsername = $this->getContainer()
            ->get(EntityService::SERVICE_NAME)
            ->getPropertyOriginalValue('username', $user);
    }
}
