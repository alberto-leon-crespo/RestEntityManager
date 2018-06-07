<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 6/06/18
 * Time: 23:36
 */

namespace ALC\RestEntityManager\Listeners;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestParameterNameOverrideListener
{
    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();

        var_dump( $request->query->keys() );
        die();
    }
}