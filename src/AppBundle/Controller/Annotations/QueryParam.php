<?php
namespace AppBundle\Controller\Annotations;

use FOS\RestBundle\Controller\Annotations as FOS;

/**
 * Represents a parameter that must be present in GET data.
 *
 * @Annotation
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 *
 */
class QueryParam extends FOS\QueryParam
{
    /** @var array */
    public $modifiers = array();

    /** @var string */
    public $defaultModifier;

    /** @var array */
    public $roles = array();

    /** @var string */
    public $exceptionCode;
}
