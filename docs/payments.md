# Requerimientos formales: Pagos

## 1. Alcance

Define procesamiento de pagos recurrentes y únicos relacionados con suscripciones, cálculo de IVA en el cobro e integración a Stripe y MercadoPago.

## 2. Reglas de negocio

- RN-PAY-001 Todo intento de cobro debe tener un identificador único para evitar cobros duplicados.
- RN-PAY-002 Solo se confirma suscripción activa tras confirmación válida de pasarela.
- RN-PAY-003 Pagos fallidos activan política de reintentos y notificaciones.
- RN-PAY-004 Los eventos de pago recibidos del proveedor deben validarse por firma y origen.
- RN-PAY-005 El cálculo de IVA debe respetar la configuración del plan (IVA incluido o excluido).

## 3. Requisitos funcionales

- RF-PAY-001 Procesar cobro inicial de suscripción.
  - Criterio de aceptación: si el pago se aprueba, la suscripción pasa a estado activo o en prueba según corresponda.
- RF-PAY-002 Procesar renovaciones automáticas por ciclo de facturación.
  - Criterio de aceptación: cada renovación registra transacción, fecha y resultado.
- RF-PAY-003 Integrar proveedores de pago mediante estrategia intercambiable.
  - Criterio de aceptación: el sistema permite seleccionar proveedor por configuración.
- RF-PAY-004 Gestionar notificaciones de pago exitoso, fallido y próximo vencimiento.
  - Criterio de aceptación: se emiten eventos/notificaciones según tipo y resultado de cobro.
- RF-PAY-005 Gestionar reintentos automáticos ante fallos transitorios.
  - Criterio de aceptación: se ejecutan reintentos conforme política y se detienen en éxito o agotamiento.
- RF-PAY-006 Registrar conciliación de eventos de pasarela.
  - Criterio de aceptación: eventos duplicados no generan doble cobro ni doble cambio de estado.
- RF-PAY-007 Calcular IVA en cada intento de cobro inicial y de renovación.
  - Criterio de aceptación: el comprobante de cobro muestra subtotal, IVA y total de forma consistente con la configuración del plan.
- RF-PAY-008 Persistir desglose de IVA por transacción para auditoría.
  - Criterio de aceptación: cada pago almacena porcentaje de IVA, base imponible, monto de IVA y total cobrado.

## 4. Requisitos no funcionales del módulo

- RNF-PAY-001 Cumplimiento de buenas prácticas de seguridad para datos de pago y secretos.
- RNF-PAY-002 Disponibilidad de bitácora de pagos para auditoría y soporte.
- RNF-PAY-003 Los cálculos de IVA deben ser consistentes y verificables para conciliación contable.

## 5. Datos mínimos (transacción con IVA)

- RF-PAY-007
  - transaccion_id
  - suscripcion_id
  - proveedor_pago
  - moneda
  - subtotal
  - iva_porcentaje_aplicado
  - monto_iva
  - total_cobrado
  - modalidad_iva (incluido o excluido)
- RF-PAY-008
  - base_imponible
  - idempotency_key
  - fecha_hora_cobro