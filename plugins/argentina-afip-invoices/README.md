# Argentina AFIP invoices

Plugin para la obtención de CAE para las facturas de AFIP Argentina.
Actualmente se encuentra en su version inicial V1.0.0, por lo cual existen posibilidades de algunos fallos que no me haya percatado, por favor reportar cualquier inconveniente.

Se pueden manejar hasta 3 organizaciones en simultaneo.

## ¿Como funciona?
* Descargar e instalar el plugin.
* Descargar e instalar la plantilla de factura template-argentina-afip, la cual posee un modelo basico de factura para Argentina y setear este modelo como plantilla por defecto en la organización.
* Ejecutar el plugin desde el menu de UCRM donde aparecerá "Facturas Argentina AFIP", seleccionar la empresa deseada y luego click en "Obtener CAE"
* Si todo es correcto, el Plugin obtendra el valor CAE y lo actualizara en la factura, en caso de que la factura sea "Proforma", la misma sera convertida a Factura.

## Configuraciones a realizar previamente para un correcto funcionamiento.
## Ajustes de Plugin

| Nombre | Descripción |
| ----------- | ------------- |
| Fecha de inicio | Fecha a partir de la cual buscar facturas para obtener CAE. |
| Usar certificados de prueba, TESTING | Al activarse se utilizaran certificados de prueba en AFIP, obteniendo datos ficticios a efectos de probar el plugin |
| Empresa, Punto de venta, Fecha de inicio de Actividades | Bloque de datos donde se define la cantidad de empresas y datos de las mismas, observar ejemplo mas abajo ** |
|Plantilla para facturas AFIP|Nombre de la plantilla personalizada a utilizar para las facturas con CAE recibido, de no configurarse se utilizara la plantilla por default de UCRM|
| Archivos de certificado y PRIVATEKEY | Certificados digitales creados y autorizados por AFIP para la facturacion correcta de la empresa |

* Ejemplo de Empresa, Punto de Venta, Frecha de inicio de actividades:
Ingresar los datos requeridos separados por , y las empresas separadas por ; , por ejemplo empresa ABC, punto de venta 0001, Fecha inicio: 01/01/1990: ABC,0001,01/01/1990
Si se desea asignar mas de una empresa, dividir con ; adicionemos la empresa DEF, Punto de venta 0002, Fecha de inicio: 10/10/2000: ABC,0001,01/01/1990;DEF,0002,10/10/2000

Este nombre de empresa debe ser exactamente igual al nombre de la empresa en UCRM respetando mayusculas y minisculas y espacios, ya que de no ser asi, el plugin no encontrara la empresa para trabajar

## Ajustes de Organizacion
Datos necesarios en la organizacion para el correcto funcionamiento:
* Nombre (igual al configurado en el plugin)
* ID del impuesto (TAX id) = CUIT de la Organizacion
* Numero de registro (Registration number) = Numero de registro en IIBB, en algunas provincias es igual al numero de CUIT.

## Ajustes de Cliente.
IMPORTANTE!!!, ANTES DE CONFIGURAR CUALQUIER CLIENTE, DEBE DE EJECUTAR EL PLUGIN AL MENOS UNA VEZ, SEGURAMENTE RECIBIRA UN MENSAJE DE ERROR, PERO ES NECESARIA ESTA EJECUCION PARA LA CREACION DE ALGUNOS ATRIBUTOS PERSONALIZADOS.
Ademas de los datos convencionales de un cliente, necesitamos cargar datos adicionales en la seccion "Atributos personalizados":

| Nombre | Descripción |
| ----------- | ------------- |
| Requiere CAE? | Para que el cliente sea considerado para recibir CAE en sus facturas este atributo debe de tener valor 1 |
| Tipo de Factura? | Tipo de factura que recibira este cliente A, B o C de acuerdo a las condiciones fiscales tanto de la organizacion como del cliente |
| Cuando hacer Factura? | Obtendra el CAE para las facturas de este cliente cuando se encuentre la misma paga o impaga, los valores validos son PAID , PAGA, UNPAID, IMPAGA  |
| Numero de Documento? | Numero del documento a considerar para la obtencion de la factura CUIT para empresas o Monotributistas, DNI para consumidores finales |
| Tipo Documento? | Valores validos: DNI o CUIT |
| Tipo Cliente? | Valores validos: RI, RM, EX, CF siendo los mismos Responsable Inscripto, Responsable Monotributo, Exento, Consumidor Final |
|Enviar factura automaticamente?|Una vez obtenido el CAE enviar la factura automaticamente al cliente?, valores validos = 1 , si, SI|

## Creación de certificados de AFIP
En google se pueden encontrar varios tutoriales de como crear los certificados de AFIP y autorizarlos.

En la carpeta tools del proyecto en GIT-HUB, pueden encontrar un par de herramientas que los pueden ayudar a la generacion de la misma.
Asi como tambien pueden utilizar el archivo GenerarClavePrivada.bat modificando previamente el archivo certificado.txt con sus datos correctos.

## Como puedo contribuir?
* Los plugins son de codigo abierto con licencia MIT, lo que permite a cualquier contribuir con cualquier actualizacion al plugin existente.
* Al margen de ser de codigo abierto y libre uso, este codigo requirio muchisimo tiempo de trabajo y sudor frente al teclado, por lo cual si te fue util sentite libre de colaborar con lo que te parezca adecuado haciendo click y escanenado el codigo QR [aqui](https://drive.google.com/file/d/17cMo9HaJVNHIu3eEQsV-hmJLH9o0Azpw/view?usp=sharing)

## Problemas conocidos 
Debido a una limitacion de UCRM en el calculo de los valores para las facturas, al momento de generar una factura A, la misma no tiene discriminado la alicuota de IVA por cada itema.
Debido a una limitacion de UCRM en el calculo de los valores para las facturas, al momento de generar una factura B, los valores descriptos para cada item individual no incluyen IVA.
Para ambos casos anteriores ya se esta trabajando en busqueda de una solucion, se actualizara apenas Ubiquiti nos brinde soporte en la materia.

## Descargo de responsabilidad 
Este software se provee "como esta", sin ningun tipo de garantia. Lea mas en [Licencia](https://github.com/Ubiquiti-App/UCRM-plugins/blob/master/LICENSE)
