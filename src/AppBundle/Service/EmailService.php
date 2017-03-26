<?php

namespace AppBundle\Service;

use AppBundle\Exception\EmailException;
use AppBundle\Helper\Email;
use Gedmo\ReferenceIntegrity\Mapping\Validator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\EmailValidator;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Class EmailService
 * @package AppBundle\Service
 */
class EmailService
{
    const SERVICE_NAME = 'email.service';

    const DEFAULT_FUNCTION_METHOD = 'email.sendEmail';

    /**
     * @var \Swift_Mailer
     */
    protected $swiftMailerService;

    /** @var  \Twig_Environment $twig */
    protected $twig;

    /**
     * @var Email //todo
     */
    protected $email;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @param string $functionMethod
     * @param Email $email
     * @return mixed
     * @throws EmailException
     */
    public function sendMail($functionMethod = self::DEFAULT_FUNCTION_METHOD, $email)
    {
        $errors = $this->validator->validate($email);

        if ($errors->count() > 0) {
            /** @var ConstraintViolation $error */
            $errorList = array();
            foreach($errors as $error){
                $errorList[] = $error->getMessage();;
            }
            throw new EmailException($errorList);
        }
//todo
//        $params = $email->getParams();
//        $paramsData = $email->getParamsdata();
//
//        if (!empty($params)) {
//            $emailCall = array_merge($emailCall, $params);
//        }
//
//        if (!empty($paramsData)) {
//            $emailCall['params']['data'] = array_merge($emailCall['params']['data'], $paramsData);
//        };
//
//
//        $response = $this->swiftMailerService->createEisApiRequest(
//            $this->cnsLink . $this->cnsJsonLink,
//            json_encode($emailCall),
//            'POST',
//            array(
//                'Accept' => 'application/json',
//                'Content-Type' => 'application/json',
//            )
//        );
//
//        $respBody = json_decode($response->getBody(true));
//        if (isset($respBody->error->code)) {
//            throw new EmailException(array('email_not_sent' => 'Could not send email'), Response::HTTP_BAD_REQUEST);
//        }
    }

    /**
     * @param Validator $validator
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;
    }

    /**
     * @return \Twig_Environment
     */
    public function getTwig()
    {
        return $this->twig;
    }

    /**
     * @param \Twig_Environment $twig
     */
    public function setTwig($twig)
    {
        $this->twig = $twig;
    }

    /**
     * @param \Swift_Mailer $swiftMailerService
     */
    public function setSwiftMailerService($swiftMailerService)
    {
        $this->swiftMailerService = $swiftMailerService;
    }

}
