<?php
namespace AppBundle\Controller\Annotations;

use FOS\RestBundle\Controller\Annotations as FOS;

/**
 * Represents a parameter that must be present in GET data.
 *
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 *
 */
class QueryParamBag extends FOS\QueryParam
{
    public $queryParams = array();

    /** @var bool */
    public $array = true;
}
