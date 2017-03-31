<?php

namespace AppBundle\Command;


use AppBundle\Security\Encoder\MainPasswordEncoder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PasswordHasherCommand extends ContainerAwareCommand
{
    const PASSWORD_OPTION_KEY = 'password';

    protected function configure()
    {
        $this->setName('app:password-hash')->setDescription('Get hash password');
        $this->addOption(self::PASSWORD_OPTION_KEY, null, InputOption::VALUE_REQUIRED, 'Password');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var MainPasswordEncoder $encoder */
        $encoder = $this->getContainer()->get('main.security.encoder');

        print_r($encoder->encodePassword(self::PASSWORD_OPTION_KEY, null));
    }

}