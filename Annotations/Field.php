<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 12/02/18
 * Time: 23:46
 */

namespace ALC\RestEntityManagerBundle\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Annotation\Target("METHOD")
 */
class Field
{
    private $target;
    private $type;

    public function __construct( $options )
    {
        $this->target = !empty( $options['target'] ) ? $options['target'] : $options['value'];
        $this->type = !empty( $options['type'] ) ? $options['type'] : 'string';
    }

    /**
     * @return mixed
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}