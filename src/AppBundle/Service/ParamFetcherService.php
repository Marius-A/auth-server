<?php
namespace AppBundle\Service;

use AppBundle\Controller\Annotations\QueryOrderBag;
use AppBundle\Controller\Annotations\QueryParam;
use AppBundle\Controller\Annotations\QueryParamBag;
use AppBundle\Controller\Annotations\RequestParam;
use AppBundle\Controller\Validator\Constraint\Role as RoleConstraint;
use AppBundle\Exception\ApiException;
use FOS\RestBundle\Controller\Annotations\ParamInterface;
use FOS\RestBundle\Request\ParamFetcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints\Regex;

class ParamFetcherService extends ParamFetcher
{
    /**
     * @param null|mixed $strict
     * @return array
     */
    public function allButNull($strict = null)
    {
        $params = $this->all($strict);

        $filtered = array();

        foreach ($params as $key => $value) {
            if (is_null($value)) {
                continue;
            }
            $filtered[$key] = $value;
        }

        return $filtered;
    }

    /**
     * {@inheritdoc}
     */
    public function get($name, $strict = null)
    {
        $params = $this->getParams();
        /** @var RequestParam $config */
        $config = $params[$name];

        try {
            $param = parent::get($name, $strict);
        } catch(BadRequestHttpException $e) {
            if (!empty($config->exceptionCode)) {
                $data = array($config->exceptionCode => $e->getMessage());
            } else {
                $data = array($e->getMessage());
            }
            throw new ApiException($data, Response::HTTP_BAD_REQUEST, $e);
        }

        if ($config instanceof QueryParamBag) {
            $config->array = false;
            $param = $this->cleanParamWithRequirements($config, $param, $strict);
        }

        return $param;
    }

    /**
     * @param ParamInterface $config
     * @param mixed $param
     * @param bool $strict
     * @param null $default
     * @return mixed|string
     */
    public function cleanParamWithRequirements(ParamInterface $config, $param, $strict, $default = null)
    {
        if ($config instanceof QueryParamBag) {
            return $this->validateQueryParamBag($config, $param, $strict);
        }

        if ($config instanceof QueryOrderBag) {
            return $this->validateQueryOrderParamBag($config, $param, $strict);
        }

        $param = $this->cleanParamWithRequirements($config, $param, $strict, $default);

        if ($config instanceof QueryParam) {
            $param = $this->validateQueryParam($config, $param, $strict);
        }

        return $param;
    }

    /**
     * @param QueryParamBag $config
     * @param $param
     * @param $strict
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function validateQueryParamBag(QueryParamBag $config, $param, $strict)
    {
        if (!is_array($param)) {
            return $param;
        }
        $validParams = array();
        foreach ($param as $key => $value) {
            $valid = false;
            foreach ($config->queryParams as $queryParam) {
                if ($queryParam instanceof QueryParam) {
                    if (preg_match($pattern = ':^' . preg_quote($queryParam->getName()) . (!empty($queryParam->modifiers) ? '[' . preg_quote(implode($queryParam->modifiers)) . ']' : '') .'{0,1}$:U', $key)) {
                        $validParams[$key] = $this->cleanParamWithRequirements($queryParam, $value, $strict);
                        $valid = true;
                    }
                }
            }
            if (!$valid) {
                throw new BadRequestHttpException(sprintf(
                    'Param %s is not supported in %s', $key, $config->getName()
                ));
            }

        }
        return $validParams;
    }


    protected function validateQueryOrderParamBag(QueryOrderBag $config, $param, $strict)
    {
        $queryParams = $config->queryParams;
        $regexp = array();
        foreach ($queryParams as $queryParam) {
            if (!$queryParam instanceof QueryParam) {
                throw new \RuntimeException(sprintf(
                    'Invalid param in QueryOrderBag %s.', $config->getName()
                ));
            }
            $regexp[] = '(^|,)' . preg_quote($queryParam->getName()) . (!empty($queryParam->modifiers) ? '[' . (implode('', $queryParam->modifiers)) . ']{0,1}' : '');
        }

        $regexp = '(' . implode('|', $regexp) . ')$';

        $config->requirements = new Regex(array(
            'pattern' => '#' . $regexp . '#xsu',
            'message' => sprintf(
                "%s parameter value '%s', does not match requirements",
                $config->getName(),
                $param
            ),
        ));

        $param = $this->cleanParamWithRequirements($config, $param, $strict);

        return $param;
    }

    /**
     * @param QueryParam $config
     * @param $param
     * @param $strict
     * @return string
     */
    public function validateQueryParam(QueryParam $config, $param, $strict)
    {
        if (!empty($config->roles)) {
            $config->requirements = new RoleConstraint(array('roles' => $config->roles));
            $param = $this->cleanParamWithRequirements($config, $param, $strict);
        }

        return $param;
    }
}
