<?php

namespace AppBundle\Exception\Listener;

use AppBundle\Exception\Interfaces\NonLoggableExceptionInterface;
use \Symfony\Component\HttpKernel\EventListener\ExceptionListener as BaseExceptionListener;

class ExceptionListener extends BaseExceptionListener
{
    /**
     * {@inheritdoc}
     */
    protected function logException(\Exception $exception, $message)
    {
        if ($exception instanceof NonLoggableExceptionInterface) {
            return;
        }
        parent::logException($exception, $message);
    }
}