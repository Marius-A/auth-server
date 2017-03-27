<?php

namespace AppBundle\Service;

use AppBundle\Controller\Annotations\QueryOrderBag;
use AppBundle\Controller\Annotations\QueryOrderParam;
use AppBundle\Controller\Annotations\QueryParam;
use AppBundle\Controller\Annotations\QueryParamBag;
use AppBundle\Controller\Annotations\RequestParam;
use Nelmio\ApiDocBundle\Extractor\Handler\FosRestHandler;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Routing\Route;

class ApiDocAnnotationService extends FosRestHandler
{
    /**
     * @inheritdoc
     */
    public function handle(ApiDoc $apiDocAnnotation, array $annotations, Route $route, \ReflectionMethod $method)
    {
        $filterAnnotations = array();
        $docAnnotations = array();

        foreach ($annotations as $annotation) {
            if ($annotation instanceof RequestParam) {
                if (!empty($annotation->grantTypes) && is_array($annotation->grantTypes)) {
                    $strAllowedGrantTypes = ". Required on grant types: " . implode(", ",$annotation->grantTypes);
                    $annotation->description = trim($annotation->description, " \t.");
                    $annotation->description .= $strAllowedGrantTypes;
                }
            }

            $docAnnotation = $annotation;
            if ($annotation instanceof QueryOrderBag) {
                $docAnnotation = clone $annotation;
                $docAnnotation->requirements = array();
                $queryParams = $annotation->queryParams;
                foreach ($queryParams as $queryParam) {
                    if ($queryParam instanceof QueryOrderParam) {
                        $docAnnotation->requirements[] = $queryParam->getName() . '[' . implode($queryParam->modifiers) . ']';
                    }
                }
                $docAnnotation->requirements = implode(',', $docAnnotation->requirements);
            }

            if ($annotation instanceof QueryParamBag) {
                $filterAnnotations[$annotation->getName()] = $annotation;
            }

            $docAnnotations[] = $docAnnotation;
        }
        $fosRestDocAnnotation = clone $apiDocAnnotation;
        parent::handle($fosRestDocAnnotation, $docAnnotations, $route, $method);

        foreach ($fosRestDocAnnotation->getParameters() as $params => $value) {
            $apiDocAnnotation->addParameter($params, $value);
        }

        foreach ($fosRestDocAnnotation->getFilters() as $filter => $value) {
            if (array_key_exists($filter, $filterAnnotations)) {
                /** @var QueryParamBag $filterAnnotation */
                $filterAnnotation = $filterAnnotations[$filter];
                foreach ($filterAnnotation->queryParams as $queryParams) {
                    if ($queryParams instanceof QueryParam) {
                        /** @var QueryParam $requirement */
                        $modifiers = $queryParams->modifiers;
                        $defaultModifier = isset($modifiers[0]) ? $modifiers[0] : '';
                        if (!empty($queryParams->defaultModifier)) {
                            $defaultModifier = $queryParams->defaultModifier;
                        }
                        $newFilter = array(
                            "Allowed modifiers" => $modifiers,
                            "Default Modifier" => $defaultModifier,
                            "Requirement" => $queryParams->requirements,
                            "Description" => $queryParams->description
                        );
                        $apiDocAnnotation->addFilter('filters[' . $queryParams->getName() . ']', $newFilter);
                    }
                }
            } else {
                $apiDocAnnotation->addFilter($filter, $value);
            }

        }
    }
}
