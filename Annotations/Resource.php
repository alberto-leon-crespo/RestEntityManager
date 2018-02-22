<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 12/02/18
 * Time: 23:56
 */

namespace ALC\RestEntityManagerBundle\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Annotation\Target("CLASS")
 */
class Resource
{
    private $value;

    public function __construct( $options )
    {
        $this->value = !empty( $options['value'] ) ? $options['value'] : $options['value'];
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}