<?php
namespace AppBundle\Controller\Annotations;

/**
 * Represents a parameter that must be present in GET data.
 *
 * @Annotation
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 *
 */
class QueryOrderParam extends QueryParam
{
    /** @var string */
    public $modifiers = array('-', '+');
}
