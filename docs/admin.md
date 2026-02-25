# Requerimientos formales: Administración

## 1. Alcance

Define las capacidades operativas y de control del panel administrativo, separadas de la experiencia de usuario final.

## 2. Reglas de negocio

- RN-ADM-001 Solo usuarios con rol administrativo pueden acceder al panel.
- RN-ADM-002 Toda operación crítica debe generar registro de auditoría.
- RN-ADM-003 La interfaz administrativa no debe afectar disponibilidad del portal de usuario.

## 3. Requisitos funcionales

- RF-ADM-001 Visualizar tablero con métricas operativas (suscripciones activas, ingresos, descuentos).
	- Criterio de aceptación: tablero muestra datos agregados por rango de fechas.
- RF-ADM-002 Gestionar usuarios (listar, editar, bloquear/desbloquear, asignar roles).
	- Criterio de aceptación: cambios de estado y rol se aplican con control de permisos.
- RF-ADM-003 Gestionar planes y niveles comerciales.
	- Criterio de aceptación: crear, editar o desactivar planes impacta su disponibilidad para nuevas compras.
- RF-ADM-004 Gestionar suscripciones (crear, ajustar estado, cancelar, reactivar).
	- Criterio de aceptación: cada transición respeta estados válidos y queda auditada.
- RF-ADM-005 Gestionar descuentos y campañas promocionales.
	- Criterio de aceptación: configuración inválida no se publica.
- RF-ADM-006 Monitorear pagos y eventos de pasarela.
	- Criterio de aceptación: pagos fallidos y reintentos son visibles para soporte operativo.

## 4. Requisitos no funcionales del módulo

- RNF-ADM-001 Acceso reforzado con 2FA para cuentas administrativas.
- RNF-ADM-002 Disponibilidad de reportes con tiempos de carga adecuados para operación.
