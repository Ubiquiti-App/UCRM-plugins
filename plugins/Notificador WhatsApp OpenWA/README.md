Notificador WhatsApp Core para UISP (CRM)

Este complemento integral conecta UISP (CRM) con la API de OpenWA Core, automatizando la comunicación con los clientes mediante notificaciones instantáneas de WhatsApp. El sistema procesa de forma inteligente los webhooks de UISP, maneja archivos PDF en Base64 y cuenta con un sistema avanzado de control de flujo (Rate-Limit) para evitar el spam.

🚀 Características Principales
Soporte Multievento: Cobertura total del ciclo de vida del cliente:

Facturación: Nuevas facturas (con envío de PDF adjunto), recordatorios de pago próximo a vencer y avisos de facturas vencidas.

Pagos: Confirmación inmediata de pagos recibidos con detalle de monto y método.

Servicios: Alertas de interrupciones (outage), suspensiones, reactivaciones, posposiciones y creación de nuevos servicios.

Clientes: Mensaje automatizado de bienvenida para nuevos registros.

Envío Nativo de Documentos: Descarga y codificación de facturas PDF al vuelo para enviarlas como documentos nativos de WhatsApp sin requerir almacenamiento temporal en disco.

Sistema Anti-Spam (Rate-Limit): Lógica avanzada que evalúa el historial de alertas mediante un archivo local (alert_log.json) para restringir envíos duplicados en períodos de 30 minutos a 24 horas, dependiendo de la criticidad del evento.

Estructura OpenWA Core: Optimizado bajo los estándares de las versiones más recientes de la API (/send-document y /send-text), utilizando estructuras planas de JSON (Flat JSON).

🛠 Requisitos del Entorno
Servidor UISP (UCRM): Versión compatible con instalación de plugins personalizados.

OpenWA Core / WAHA: Contenedor Docker configurado, activo y con una sesión vinculada.

PHP: Extensiones cURL y JSON habilitadas en el entorno del servidor UISP.

Permisos de Escritura: El plugin requiere permisos de escritura en la carpeta data/ para gestionar el archivo alert_log.json.

📦 Instalación y Despliegue
Asegúrate de que todos los archivos (incluyendo public.php y la carpeta data/) estén en la raíz del directorio del plugin.

Comprime el directorio completo en un archivo .zip.

Ingresa al panel administrativo de UISP > Sistema > Plugins.

Haz clic en Subir Plugin y selecciona tu archivo .zip.

Activa el plugin en la interfaz.

⚙️ Configuración (Panel UISP)
Al instalar, configura los siguientes parámetros obligatorios en la sección de ajustes del plugin:

URL de la API: La ruta base de tu contenedor OpenWA (Ej: http://144.xxx.xxx.xx:2785/api).

API Key: Clave de autenticación configurada en las variables de entorno de tu Docker.

Session ID: El identificador exacto de la sesión activa de WhatsApp (Ej: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx).

App Key de UISP: Generada automáticamente por el sistema para autorizar las peticiones internas (descarga de PDFs y datos de clientes).

🔍 Solución de Problemas (Logs)
El plugin está diseñado para registrar sus acciones directamente en el visor de Webhooks de UISP.

Código 201/200: Envío exitoso.

Código 400: El payload enviado a la API de WhatsApp está mal formateado. Revisar variables de teléfono.

Código 404: La ruta del endpoint en OpenWA no coincide con la versión instalada.

Rate-limit Log: Si un mensaje no se envía por control de flujo, el log indicará: "Rate-limit: ya se notificó {evento} al cliente {ID}".
