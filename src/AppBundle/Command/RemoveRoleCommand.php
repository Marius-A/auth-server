<?php

namespace AppBundle\Command;

use AppBundle\Entity\Role;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveRoleCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:role:remove')->setDescription('Remove role');
        $this->addOption('role', null, InputOption::VALUE_REQUIRED, 'Role to be removed');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = $this->getContainer()->get('doctrine')->getManager();

        $role = $input->getOption('role');
        if (empty($role)) {
            throw new \Exception("Role cannot be empty");
        }

        $role = $manager->getRepository('AppBundle:Role')->findOneBy(array('role' => $role));
        if (empty($role)) {
            throw new \Exception("Role was not found");
        }

        $manager->getRepository('AppBundle:Role')->removeFromTree($role);
    }
}
