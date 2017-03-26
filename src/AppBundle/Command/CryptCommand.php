<?php

namespace AppBundle\Command;

use AppBundle\Service\CryptService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CryptCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:crypt')->setDescription('Add role');
        $this->addOption('decrypt', 'd', InputOption::VALUE_NONE, 'Tell command to decrypt.');
        $this->addOption('message', 'm', InputOption::VALUE_REQUIRED, 'Text to be encrypted/decrypted.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $text = $input->getOption('message');
        if (empty($text)) {
            throw new \Exception("Text cannot be empty");
        }

        $decrypt = $input->getOption('decrypt');

        if ($decrypt) {
            echo $this->getContainer()->get(CryptService::SERVICE_NAME)->decrypt($text);
        } else {
            echo $this->getContainer()->get(CryptService::SERVICE_NAME)->encrypt($text);
        }
    }
}