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
class QueryOrderBag extends FOS\QueryParam
{
    /** @var array */
    public $queryParams = array();

    /** @var string */
    public $description="Order fields split by comma with leading + or - modifier for order direction";
}
