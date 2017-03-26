<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteTokenCacheIndexCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:delete-token-cache')->setDescription('Test app functionalities');
        $this->addOption('userId', null, InputOption::VALUE_REQUIRED, 'User id');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userId = (int)$input->getOption('userId');
        $user = $this->getContainer()->get('doctrine')->getManager()->getRepository('AppBundle:User')->find($userId);
        if (empty($user)) {
            throw new \Exception("User was not found");
        }
        $this->getContainer()->get('fos_oauth_server.server')->deleteUserTokenCacheIndex($user);
    }
}
