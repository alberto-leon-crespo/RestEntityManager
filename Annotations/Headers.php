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
 * @Annotation\Target("CLASS")
 */
class Headers
{
    private $values;

    public function __construct( $options )
    {
        $this->values = !empty( $options['value'] ) ? $options['value'] : $options['value'];
    }

    /**
     * @return mixed
     */
    public function getValues()
    {
        return $this->values;
    }
}