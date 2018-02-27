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

* alc_rest_entity_manager.handler: Es el gestor de entidades rest. Se encarga de leer la configuración
de las diferentes conexiones y cargarla en el el cliente rest.
* alc_rest_entity_manager.jms_event_subscriber: Se encarga de configurar el serializador para transformar los datos
al formato de destino.

