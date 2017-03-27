<?php

namespace AppBundle\Command;

use AppBundle\Entity\Role;
use AppBundle\Service\RoleService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AddRoleCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:role:add')->setDescription('Add role');
        $this->addOption('role', null, InputOption::VALUE_REQUIRED, 'Role to be added');
        $this->addOption('name', null, InputOption::VALUE_REQUIRED, 'Name of the role to be added');
        $this->addOption('parent', null, InputOption::VALUE_REQUIRED, 'Parent of the role to be added');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = $this->getContainer()->get('doctrine')->getManager();

        $role = $input->getOption('role');
        if (empty($role)) {
            throw new \Exception("Role cannot be empty");
        }

        $name = $input->getOption('name');
        if (empty($name)) {
            throw new \Exception("Role name cannot be empty");
        }

        $parent = $input->getOption('parent');
        if (empty($parent)) {
            throw new \Exception("Parent role cannot be empty");
        }

        $role = new Role($role);
        $role->setName($name);
        $role->setStatus(1);

        $parent = $manager->getRepository('AppBundle:Role')->findOneBy(array('role' => $parent));
//        if (empty($parent)) {
//            throw new \Exception("Parent role was not found");
//        }

        $role->setParent($parent);

        $this->getContainer()->get(RoleService::SERVICE_NAME)->save($role);
    }
}
