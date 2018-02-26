<?php
/**
 * Created by PhpStorm.
 * User: aleon
 * Date: 26/02/2018
 * Time: 17:18
 */

namespace ALC\RestEntityManager\Annotations;


/**
 * @Annotation
 * @Annotation\Target("CLASS")
 */
class Repository
{
    private $repositoryClass;

    public function __construct( $options )
    {
        $this->repositoryClass = !empty( $options['value'] ) ? $options['value'] : $options['value'];
    }

    /**
     * @return mixed
     */
    public function getRepositoryClass()
    {
        return $this->repositoryClass;
    }
}