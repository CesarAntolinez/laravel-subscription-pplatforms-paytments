# Requerimientos formales: Planes

## 1. Alcance

Define la configuración de planes comerciales, niveles, precios, IVA y condiciones de renovación.

## 2. Reglas de negocio

- RN-PLA-001 Un plan debe tener nombre único, estado y al menos un precio activo.
- RN-PLA-002 Un plan puede tener uno o más ciclos de facturación habilitados.
- RN-PLA-003 La prueba gratuita, si existe, se define por duración en días.
- RN-PLA-004 La configuración del plan debe permitir definir si el precio incluye IVA o si el IVA se calcula de forma adicional.

## 3. Requisitos funcionales

- RF-PLA-001 Crear plan con nombre, descripción, estado e información comercial.
	- Criterio de aceptación: al guardar un plan válido, el sistema genera un identificador único y el plan queda disponible para uso.
- RF-PLA-002 Editar plan sin afectar historial de suscripciones existentes.
	- Criterio de aceptación: los cambios aplican a nuevas contrataciones; los historiales previos permanecen consultables.
- RF-PLA-003 Definir niveles del plan (por ejemplo: básico, estándar, premium).
	- Criterio de aceptación: cada nivel puede consultarse y seleccionarse en el flujo de suscripción.
- RF-PLA-004 Configurar ciclos de facturación por plan (mensual, trimestral, anual).
	- Criterio de aceptación: el usuario solo puede elegir ciclos habilitados para el plan.
- RF-PLA-005 Configurar prueba gratuita opcional por plan.
	- Criterio de aceptación: durante la prueba, el estado de suscripción refleja período de prueba vigente.
- RF-PLA-006 Activar o desactivar renovación automática a nivel de plan.
	- Criterio de aceptación: en renovaciones, el sistema respeta la bandera configurada.
- RF-PLA-007 Configurar porcentaje de IVA por plan.
	- Criterio de aceptación: al calcular el cobro, el sistema aplica el porcentaje de IVA y muestra subtotal, IVA y total.
- RF-PLA-008 Definir si el precio del plan es con IVA incluido o excluido.
	- Criterio de aceptación: el total facturado coincide con la modalidad de IVA configurada para el plan.

## 4. Requisitos no funcionales del módulo

- RNF-PLA-001 Cambios de configuración deben quedar auditados (quién, cuándo, qué cambió).
- RNF-PLA-002 Listado de planes debe responder con paginación y filtros básicos.
- RNF-PLA-003 Los cálculos de IVA deben ser precisos, consistentes y verificables entre checkout, facturación y reportes.

## 5. Datos mínimos (IVA)

- RF-PLA-007
	- iva_porcentaje
	- vigencia_desde
	- vigencia_hasta (opcional)
- RF-PLA-008
	- modalidad_iva (incluido o excluido)
	- moneda
	- precision_decimal
