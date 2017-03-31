<?php

namespace AppBundle\Controller;

use AppBundle\Handler\HandlerInterface;
use AppBundle\Service\RoleService;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\FOSRestController;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use Symfony\Component\Security\Acl\Util\ClassUtils;

abstract class RestController extends FOSRestController
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @return HandlerInterface
     */
    abstract protected function getHandler();

    /**
     * @return array
     */
    protected function getRequiredRoles()
    {
        $security = $this->get("security.extra.metadata_factory");
        $metadata = $security->getMetadataForClass(ClassUtils::getRealClass($this));

        $params = explode('::', $this->get('request_stack')->getCurrentRequest()->attributes->get('_controller'));

        #TOKEN ROLES
        $tokenRoles = RoleService::getRolesNames($this->container->get('security.token_storage')->getToken()->getRoles());
        #ACTION ROLES
        $actionRoles = array();
        if (isset($params[1]) && isset($metadata->methodMetadata[$params[1]]->roles)) {
            $actionRoles = $metadata->methodMetadata[$params[1]]->roles;
        }
        #ROLES
        $roles = array_intersect($actionRoles,$tokenRoles);

        return $roles;
    }

    /**
     * @return Context
     */
    protected function getContext()
    {
        if (null === $this->context) {
            $this->context = new Context();
            $roles = $this->getRequiredRoles();
            $this->context->setSerializeNull(true)->addGroups($roles);
        }

        return $this->context;
    }

    /**
     * @return SerializationContext
     */
    protected function getSerializationContext()
    {
        $context = $this->getContext();
        $serializationContext = SerializationContext::create();
        $serializationContext->setSerializeNull($context->getSerializeNull());
        $groups = $context->getGroups();
        if (!empty($groups)) {
            $serializationContext->setGroups($groups);
        }
        $version = $context->getVersion();
        if (!is_null($version)) {
            $serializationContext->setVersion($version);
        }
        $attributes = $context->getAttributes();
        foreach ($attributes as $key => $value) {
            $serializationContext->setAttribute($key, $value);
        }

        return $serializationContext;
    }

    /**
     * @return DeserializationContext
     */
    protected function getDeserializationContext()
    {
        $context = $this->getContext();
        $deserializationContext = DeserializationContext::create();
        $deserializationContext->setSerializeNull($context->getSerializeNull());
        $groups = $context->getGroups();
        if (!empty($groups)) {
            $deserializationContext->setGroups($groups);
        }
        $version = $context->getVersion();
        if (!is_null($version)) {
            $deserializationContext->setVersion($version);
        }
        $attributes = $context->getAttributes();
        foreach ($attributes as $key => $value) {
            $deserializationContext->setAttribute($key, $value);
        }

        return $deserializationContext;
    }


    /**
     * @param null $data
     * @param null $statusCode
     * @param array $headers
     * @return \FOS\RestBundle\View\View
     */
    protected function view($data = null, $statusCode = null, array $headers = array())
    {
        $view = parent::view($data, $statusCode, $headers);
        $view->setContext($this->getContext());
        return $view;
    }
}
