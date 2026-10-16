# Mikrotik Queue Sync

Synchronizes the speeds of UISP (UCRM) service plans with Mikrotik simple queues.
Sincroniza las velocidades de los planes de servicio de UISP (UCRM) con colas simples (simple queues) de Mikrotik.

## How it works / Cómo funciona

* For each UISP service, the plugin finds the Mikrotik simple queue with the service IP as target and updates max-limit, limit-at, burst and priority.
  Para cada servicio de UISP, el plugin busca la cola simple del Mikrotik que tiene la IP del servicio como target y actualiza max-limit, limit-at, ráfaga y prioridad.
* Service IPs come from the UISP devices linked to the service (UCRM without UNMS: the service IP range).
  Las IPs del servicio salen de los dispositivos de UISP vinculados al servicio (UCRM sin UNMS: el rango de IP del servicio).
* Traffic shaping overrides of the service are respected. An empty override speed uses the plan speed.
  Se respetan los overrides de velocidad del servicio. Un override vacío usa la velocidad del plan.
* Queues are renamed to `<client> - Service ID:<id>`, so they stay right when UISP changes the service ID.
  Las colas se renombran a `<cliente> - Service ID:<id>`, así se mantienen correctas cuando UISP cambia el ID del servicio.
* **Parent queues and dynamic queues (PPPoE, hotspot) are never modified**, even if their target is a service IP.
  **Las colas padre y las dinámicas (PPPoE, hotspot) nunca se modifican**, aunque su target sea la IP de un servicio.
* The plugin never removes queues. / El plugin nunca borra colas.

## Installation / Instalación

1. Download `mkt-queue-sync.zip` from the [latest release](https://github.com/Bonabrux/mkt-queue-sync/releases/latest) and upload it in UISP: System > Plugins.
   Descargue `mkt-queue-sync.zip` desde la [última versión](https://github.com/Bonabrux/mkt-queue-sync/releases/latest) y súbalo en UISP: Sistema > Complementos.
2. Configure and enable the plugin. / Configure y habilite el complemento.
3. Enable automatic execution in the plugin settings, or run it from the menu **Mikrotik Queue Sync > Sync / Sincronizar**.
   Active la ejecución automática en la configuración del complemento, o ejecútelo desde el menú **Mikrotik Queue Sync > Sync / Sincronizar**.
4. Check the plugin log for the result. / Revise el log del complemento para ver el resultado.

The Mikrotik user needs API access (`/ip service` api enabled, default port 8728).
El usuario del Mikrotik necesita acceso a la API (`/ip service` api habilitado, puerto por defecto 8728).

## Settings / Configuración

| Setting | Description | Descripción |
| --- | --- | --- |
| Mikrotik IP Address | Router IP (one router) | IP del router (un router) |
| Mikrotik API port | Empty = 8728 | Vacío = 8728 |
| Mikrotik username / password | User with API access | Usuario con acceso a la API |
| Burst limit % | Over max-limit, `UU/DD` (upload/download), `0/0` = no burst | Sobre el max-limit, `UU/DD` (subida/bajada), `0/0` = sin ráfaga |
| Burst time | Seconds, `UU/DD`, values 1-99, `1/1` if not used | Segundos, `UU/DD`, valores 1-99, `1/1` si no se usa |
| Limit-at % | Over max-limit, `UU/DD`, values 0-99 | Sobre el max-limit, `UU/DD`, valores 0-99 |
| Add queues? | Create a queue for services without one | Crear una cola para los servicios que no tienen |
| UNMS API token | Needed with UISP / UNMS v1+ (UNMS > Settings > Users) | Necesario con UISP / UNMS v1+ (UNMS > Settings > Users) |
| Mikrotik charset | Must match Winbox (e.g. Cyrillic = Windows-1251), empty = UTF-8 | Debe coincidir con Winbox (ej. cirílico = Windows-1251), vacío = UTF-8 |
| Debug mode | More detailed log | Log más detallado |

* Example: limit-at 20% upload and 50% download = `20/50`. / Ejemplo: limit-at 20% de subida y 50% de bajada = `20/50`.
* Priority: the service plan **Data usage limit** (1-8) is used as queue priority, 8 when empty.
  Prioridad: el **Data usage limit** del plan (1-8) se usa como prioridad de la cola, 8 si está vacío.
* Selective sync: create an address-list `sync_with_ucrm` on the router with the IPs or ranges to sync (`10.0.0.0/24`, `10.0.0.1-10.0.0.50`).
  Sincronización selectiva: cree en el router un address-list `sync_with_ucrm` con las IPs o rangos a sincronizar (`10.0.0.0/24`, `10.0.0.1-10.0.0.50`).

## Free version limits / Límites de la versión gratuita

* One Mikrotik router. / Un router Mikrotik.
* Up to **100 services** (the first 100 by service ID, the rest are left untouched and reported in the log).
  Hasta **100 servicios** (los primeros 100 por ID de servicio, el resto no se toca y se informa en el log).

## PRO version / Versión PRO

| Feature / Función | Free | PRO |
| --- | :---: | :---: |
| Queue sync (speeds, burst, limit-at, priority, overrides) / Sincronización de colas | ✔ | ✔ |
| Parent/dynamic queue protection / Protección de colas padre y dinámicas | ✔ | ✔ |
| Routers | 1 | Unlimited / Ilimitados |
| Services / Servicios | 100 | Unlimited / Ilimitados |
| Automatic sync on service edit (webhook) / Sincronización al editar un servicio (webhook) | | ✔ |
| Suspension: blocked users address-list, redirect to suspension page / Suspensión: address-list de bloqueados y redirección a página de suspensión | | ✔ |
| Address-lists by service plan and of active services / Address-lists por plan y de servicios activos | | ✔ |
| Remove queues of ended services / Borrar colas de servicios terminados | | ✔ |
| Duplicated queues cleanup / Limpieza de colas duplicadas | | ✔ |

Contact / Contacto: **plugins@franco.ar**

***If this plugin helped you and you think I deserve a coffee / Si este plugin le sirvió y cree que merezco un café: https://www.paypal.me/fgampel***

[Mikrotik simple queues](https://help.mikrotik.com/docs/display/ROS/Queues)
