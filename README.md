# Rest Entity Manager

Un gestor de entidades rest orientado al mapeo de datos con webservices.
El principal problema que te encuentras cuando usas librerias orientadas a crear un WebService
es que todas están planteadas para flujos estandar en los que conectas directamente con tu BBDD.
Muchas APIS modernas basadas en la web, funcionan de intermediarias con otras APIS corporativas
y no corporativas. Asi surgio "Rest Entity Manager".

# Funcionamiento basico de la libreria

Esta libreria consta de dos serializadores estandar. El primero gestiona la transformación de
datos entre las entidades y los diferentes servicios rest. El segundo serializador se usa para
codificar y decodificar los contenidos que envian y reciben los clientes y asi popular las entidades
de tu API.

Internamente el gestor de entidades lee la configuración de las diferentes conexiones y realiza las peticiones a los
diferentes webservices.

# Servicios

* *alc_rest_entity_manager.handler*: Es el gestor de entidades rest. Se encarga de leer la configuración
de las diferentes conexiones y cargarla en el el cliente rest.
* *alc_rest_entity_manager.jms_event_subscriber*: Se encarga de leer la configuracion de las entidades y
 configurar el mapeo hacia los diferentes WS rest.
* *fos_rest.serializer*: Es el serializador encargado de mapear la información que envian y reciben los clientes
en las diferentes entidades de la API.
* *alc_rest_entity_manager.logger*: Servicio encargado de monitorizar y escribir los logs de las peticiones rest
del manager.
* *alc_rest_entity_manager.metadata_class_reader*: Se encarga de leer la metainformación de las clases necesaria pra realizar los mapeos.
* *alc_rest_entity_manager.parameters_procesor*: Se encarga de procesar los parametros de filtrado segun la configuración definida en la conexion rest.
* *alc_rest_request_parameters_name_override*: Sobreescribe los parametros de la URL en el servicio "request" respetando la notacion de puntos. Ej: "poliza.id"

# Consideraciones previas

Este es un bundle pensado para funcionar junto con "FOSRestBundle", pero tambien se puede usar sin el.

# Instalación

## Paso 1: Descargar el bundle

Abre la consola de comandos, entra en el directorio de tu proyecto y ejecuta el siguiente comando para descargar la
ultima version estable de este bundle.

`$ composer require alberto-leon-crespo/rest-entity-manager`

## Paso 2: Activar el bundle

Activa el bundle añadiendo la siguiente linea al fichero `app/AppKernel.php`

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new ALC\RestEntityManager\ALCRestEntityManagerBundle(),
        );

        // ...
    }
}
```

# Configuracion

Para poder usar las entidadedes orientadas a rest es necesario configurar previamente una conexion rest.

```yml
# app/config/config.yml

alc_rest_entity_manager:
    default_manager: default # El nombre de la conexion que usara el manager por defecto.
    managers:
        default:
            name: 'default' # El nombre de la conexion
            host: 'https://jsonplaceholder.typicode.com/' # URL base del servicio rest que se va a consultar
            session_timeout: 7200 # Tiempo de expiración de la sesion que hay entre el cliente rest y el webservice de destino
            avanced:
                filtering:
                    ignored_parameters: [_fields,_sort,_page,_limit] #Parametros ignorados del filtrado, no se consideran parametros de filtrado
                    parameters_map:
                        maps:
                          - { origin: _fields, destination: selection } # El campo de origen se mapea al WS como el campo destino indicado
                          - { origin: _page, destination: _page } # El campo de origen se mapea al WS como el campo destino indicado
                          - { origin: _limit, destination: _limit } # El campo de origen se mapea al WS como el campo destino indicado
                          - { origin: _sort, interceptor: ALC\WebServiceBundle\Interceptors\SortParametersInterceptor::parseSortFields } # El campo de origen se transforma por medio de un ParameterInterceptor
            custom_params: # Bloque de parametros de configuración personalizables
                secret_api_key: example1234
            events_handlers: # Bloque de eventos sobre peticiones.
                tokens_inject_handler: # Nombre del manejador del evento
                    event: before # Evento a interceptar
                    interceptor: 'ALC\WebServiceBundle\Interceptors\TokenInjector::injectToken' # Interceptor de las peticiones
```

Si quieres usar el serializador junto con FOSRestBundle es necesario que sobreescribas el serializador estandar con la siguiente configuración.

```yml
# app/config/config.yml

fos_rest:
    service:
        serializer: alc_rest_entity_manager.serializer
```

## Anotaciones de configuración para las entidades.

* *ALC\RestEntityManager\Annotations\Resource*:

    Indica el recurso rest al que accedera la entidad. En este caso "users"

* *ALC\RestEntityManager\Annotations\Repository*:

    Indica el repositorio asociado a la entidad.

* *ALC\RestEntityManager\Annotations\Id*:

    Indica que la propiedad precedida por el comentario se trata del identificador unico del recurso.

* *ALC\RestEntityManager\Annotations\Field*:

    * *target*: El campo de destino del servicio rest.
    * *type*: Tipo de dato del campo.

        * Tipos soportados:

            Los tipos soportados son los mismos que acepta [JMSSerializer](https://jmsyst.com/libs/serializer/master/reference/annotations#type).

* *ALC\RestEntityManager\Annotations\Headers*:

    Permite especificar un array de cabeceras http y los valores que se aplicaran a las solicitudes rest de la entidad.

```php
<?php

namespace ALC\WebServiceBundle\Entity\Users;

use ALC\RestEntityManager\Annotations as Rest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Rest\Resource("users")
 * @Rest\Headers({"content-type": "application/json","cache-control": "no-cache"})
 * @Rest\Repository("ALC\WebServiceBundle\Entity\Users\UsersRepository")
 */
class Users
{
    /**
     * @Rest\Id()
     * @Rest\Field(target="id",type="integer")
     */
    private $idUsuario;

    /**
     * @Rest\Field(target="name",type="string")
     * @Assert\NotNull()
     * @Assert\NotBlank()
     */
    private $nombre;

    \\ Some class properties and methods
    \\ ....
}
```

# Acciones generales del manager rest

## Buscar por id

Equivalente a GET /users/:id

* id: identificador del recurso que se desea consultar.
* format: formato de salida de la información.
    * json
    * xml
    * object
* type: tipo de dato de salida si se indico en format `object`.

```php

<?php

$objUsersRepository = $this
    ->get('alc_rest_entity_manager.handler')
    ->getManager('default')
    ->getRepository('AppBundle:Users\Users');

$arrResponse = $objUsersRespository->find( $userId, 'object', 'ALC\\WebServiceBundle\\Entity\\Users\\Users' );

```

## Recuperar todos los registros de un listado

Equivalente a GET /users

* format: formato de salida de la información.
    * json
    * xml
    * object
* type: tipo de dato de salida si se indico en format `object`.

```php

<?php

$objUsersRepository = $this
    ->get('alc_rest_entity_manager.handler')
    ->getManager('default')
    ->getRepository('AppBundle:Users\Users');

$arrResponse = $objUsersRespository->findAll( 'object', 'ALC\\WebServiceBundle\\Entity\\Users\\Users' );

```

## Recuperar un listado filtrado

Equivalente a GET /users?nombre=Alberto

* filters: filtros a aplicar al listado.
* format: formato de salida de la información.
    * json
    * xml
    * object
* type: tipo de dato de salida si se indico en format `object`.

```php

<?php

$arrFilters = array(
    'nombre' => 'Jhon'
);

$objUsersRepository = $this
    ->get('alc_rest_entity_manager.handler')
    ->getManager('default')
    ->getRepository('AppBundle:Users\Users');

$arrResponse = $objUsersRespository->findBy( $arrFilters, 'object', 'ALC\\WebServiceBundle\\Entity\\Users\\Users' );

```

## Recuperar el primer registro de un listado filtrado

Equivalente a GET /users?nombre=Alberto

* filters: filtros a aplicar al listado.
* format: formato de salida de la información.
    * json
    * xml
    * object
* type: tipo de dato de salida si se indico en format `object`.

```php

<?php

$arrFilters = array(
    'nombre' => 'Jhon'
);

$objUsersRepository = $this
    ->get('alc_rest_entity_manager.handler')
    ->getManager('default')
    ->getRepository('AppBundle:Users\Users');

$arrResponse = $objUsersRespository->findOneBy( $arrFilters, 'object', 'ALC\\WebServiceBundle\\Entity\\Users\\Users' );

```

## Guardar los cambios

Equivalente a POST /users o PUT /users

Si el objeto de entidad tiene un valor asociado en el campo marcado como id, realizara un PUT, en caso contrario realizara un POST

* object: instancia de la entidad que se quiere persistir.
* format: formato de salida de la información.
    * json
    * xml
    * object
* type: tipo de dato de salida si se indico en format `object`.

```php

<?php

$objUser = new \AppBundle\Users();

$objUser->setNombre("Jhon");
$objUser->setApellido("Doe");

$em = $this
    ->get('alc_rest_entity_manager.handler')
    ->getManager('default');

$arrResponse = $em->persist( $objUser, 'object', 'ALC\\WebServiceBundle\\Entity\\Users\\Users' );

```

## Comprobar si existe un registro, si existe lo actualiza, en caso contrario lo crea.

Equivalente a POST /users o PUT /users

Si el objeto de entidad tiene un valor asociado en el campo marcado como id y ademas ese id existe, realizara un PUT, en caso contrario realizara un POST

* object: instancia de la entidad que se quiere persistir.
* format: formato de salida de la información.
    * json
    * xml
    * object
* type: tipo de dato de salida si se indico en format `object`.

```php

<?php

$objUser = new \AppBundle\Users();

$objUser->setNombre("Jhon");
$objUser->setApellido("Doe");

$em = $this
    ->get('alc_rest_entity_manager.handler')
    ->getManager('default');

$arrResponse = $em->merge( $objUser, 'object', 'ALC\\WebServiceBundle\\Entity\\Users\\Users' );

```

## Obtener la información de una entidad con los valores actualizados del webservice

* object: instancia de la entidad que se quiere refrescar.
* format: formato de salida de la información.
    * json
    * xml
    * object
* type: tipo de dato de salida si se indico en format `object`.

```php
<?php

$objUser = new \AppBundle\Users();

$objUser->setIdUsuario(2);

$em = $this
    ->get('alc_rest_entity_manager.handler')
    ->getManager('default');

$arrResponse = $em->refresh( $objUser, 'object', 'ALC\\WebServiceBundle\\Entity\\Users\\Users' );

```

# Acciones personalizadas del manager rest

Cuando por diferentes circustancias cualquiera de estas acciones estandar no funcional para tu caso de uso,
puedes realizar una llamada totalmente personalizada por medio de un repositorio o realizando la acción directamente en el controlador
como si se tratara de un cliente rest normal.

## Con un repositorio

Es importante definir la anotacion "Repository" en la entidad rest.

```php

<?php

// Repository

namespace AppBundle\Entity\Users;

use ALC\RestEntityManager\RestRepository;

class UsersRepository extends RestRepository
{
    public function listadoUsuariosWithVocalA(){

        $response = $this->get( 'users', array() );

        $arrUsers = $this->serializer->deserialize( $response->getBody()->getContents(), 'array<ALC\WebServiceBundle\Entity\Users\Users>', 'json' );

        foreach( $arrUsers as $key => $objUser ){

            if( mb_strpos( $objUser->getNombre(), 'a', null, 'utf8' ) === false ){

                unset( $arrUsers[$key] );

            }

        }

        return array_values( $arrUsers );

    }
}

// Controller

<?php

namespace ALC\WebServiceBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;

class UsersController extends FOSRestController
{
    public function getUsersAction(Request $objRequest){

        $objEntityManager = $this->get('alc_rest_entity_manager.handler')->getManager();

        /**
         * @var $objUsersRespository \ALC\RestEntityManager\Services\RestEntityHandler\RestEntityHandler|\ALC\WebServiceBundle\Entity\Users\UsersRepository
         */
        $objUsersRespository = $objEntityManager->getRepository('ALCWebServiceBundle:Users\Users');

        $arrUsersWithAVocal = $objEntityManager->listadoUsuariosWithVocalA();

        // If use FOSRestBundle
        return $arrUsersWithAVocal;

        // If dont use FOSRestBundle

        $strUsersSerialized = $this->get('alc_rest_entity_manager.serializer')->serialize( $arrUsersWithAVocal, 'json' );

        $objResponse = new Response( $strUsersSerialized, 200, ['Content-Type'=>'application/json'] );

        return $objResponse;
```

# Opciones avanzadas de filtrado

En ocasiones, los parametros que pasamos a la URL no son parametros de filtrado (ordenacion, paginado, etc..). 

Para ello solo hay que indicar que parametros seran ignorados del filtrado y cual es su equivalencia en el WS de destino.

## Equivalencia directa

En este caso la equivalencia con el WS de destino es directa entre el campo de origen y de destino

```yml
# app/config/config.yml
...
            name: 'default'
            host: 'https://jsonplaceholder.typicode.com/'
            session_timeout: 7200
            avanced:
                filtering:
                    ignored_parameters: [_fields,_sort,_page,_limit] # Parametros especiales que deben de ser ignorados en los filtrados.
                    parameters_map: # Mapeo de parametros especiales origen - destino
                        maps:
                          - { origin: page, destination: _page } # Campor de origen page, campo de destino _page
                          - { origin: limit, destination: _limit } # Campor de origen limit, campo de destino _limit
```

De esta manera si la URL introducida en tu WS es {{URL_BASE}}/usuarios?page=1&limit=5 se solicitaria de la siguiente manera al WS de destino {{URL_BASE}}/users?_page=1&_limit=5

## Equivalencia indirecta

Puedes encontrar algunos casos en los que la equivalencia con un parametro especial de destino, no es directa, es decir, un parametro puede equivaler a dos como en el siguiente ejemplo:

|Origen|Destino|
|:------|:-------|
|sort=+id|_sort=id&order=asc|

En estos casos puedes crear un interceptor de parametros de la siguiente manera:

```php
<?php
namespace ALC\WebServiceBundle\Interceptors;
use ALC\RestEntityManager\ParameterInterceptor;
class SortParametersInterceptor extends ParameterInterceptor
{
    public function parseSortFields($value)
    {
        $firstCharacter = $value[0];
        $fieldName = substr($value, 1);
        $order = null;
        if ($firstCharacter === '-') {
            $order = 'desc';
        } elseif ($firstCharacter === '+' || $firstCharacter === ' ') {
            $order = 'asc';
        }
        // Busca las equivalencias entre los parametros de entrada de tu WS y el WS de destino
        $arrFinalParams = $this->getMetadataClassReader()->matchEntityFieldsWithResourcesFieldsRecursive( [ $fieldName => $order ] );
        $arrayMatch = array();
        foreach( $arrFinalParams as $campoOrdenar => $metodoOrdenacion ){
            $arrayMatch['_sort'] = $campoOrdenar;
            $arrayMatch['_order'] = $metodoOrdenacion;
        };
        return $arrayMatch;
    }
}
```

Para darlo de alta solo tienes que dar de alta la siguiente configuración en la sección "parameters_map":

```yml
# app/config/config.yml
...
            name: 'default'
            host: 'https://jsonplaceholder.typicode.com/'
            session_timeout: 7200
            avanced:
                filtering:
                    ignored_parameters: [_fields,_sort,_page,_limit] # Parametros especiales que deben de ser ignorados en los filtrados.
                    parameters_map: # Mapeo de parametros especiales origen - destino
                        maps:
                           - { origin: _sort, interceptor: ALC\WebServiceBundle\Interceptors\SortParametersInterceptor::parseSortFields }
```


# Ejemplos

Puedes consultar el bundle de ejemplo [ALC\WebServiceBundle](https://github.com/alberto-leon-crespo/rest-entity-manager)
