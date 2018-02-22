<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 12/02/18
 * Time: 23:56
 */

namespace ALC\RestEntityManager\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Annotation\Target("METHOD")
 */
class Id
{
    private $value;

    public function __construct( $options )
    {
        $this->value = true;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}