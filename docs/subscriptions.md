# Requerimientos formales: Suscripciones

## 1. Alcance

Define el ciclo de vida de suscripciones, cambios de plan, estados, renovaciones y relación con descuentos, IVA y pagos.

## 2. Reglas de negocio

- RN-SUB-001 Estados permitidos: activa, en prueba, suspendida, cancelada, vencida.
- RN-SUB-002 El usuario puede cancelar su suscripción; la reactivación depende de reglas comerciales.
- RN-SUB-003 Un plan anual puede tener modalidad de cobro anual, trimestral o mensual, según configuración.
- RN-SUB-004 En incumplimiento de pago aplica período de gracia configurable antes de suspensión.
- RN-SUB-005 Los cambios de plan deben recalcular importes e IVA según política de prorrateo vigente.

## 3. Requisitos funcionales

- RF-SUB-001 Crear suscripción para usuario y plan con ciclo de facturación seleccionado.
  - Criterio de aceptación: la suscripción inicia en estado consistente con pago/prueba.
- RF-SUB-002 Renovar suscripción automáticamente según calendario de cobro.
  - Criterio de aceptación: renovación exitosa actualiza próximo cobro y mantiene continuidad.
- RF-SUB-003 Cancelar suscripción por usuario o administrador con política de efectividad.
  - Criterio de aceptación: la cancelación respeta fecha de corte configurada.
- RF-SUB-004 Reactivar suscripción cancelada o suspendida cuando cumpla condiciones.
  - Criterio de aceptación: la reactivación conserva el historial previo de la suscripción.
- RF-SUB-005 Cambiar de plan (upgrade/downgrade) con ajuste de cobro.
  - Criterio de aceptación: el sistema calcula prorrateo o ajuste según política definida.
- RF-SUB-006 Gestionar métodos de pago asociados a la suscripción.
  - Criterio de aceptación: agregar/editar/eliminar método respeta validación de pasarela.
- RF-SUB-007 Consultar historial de suscripción y eventos asociados.
  - Criterio de aceptación: historial incluye fechas clave, estados y transacciones relacionadas.
- RF-SUB-008 Recalcular IVA al cambiar de plan o ciclo de facturación.
  - Criterio de aceptación: el ajuste económico refleja base, IVA y total con detalle del prorrateo aplicado.

## 4. Requisitos no funcionales del módulo

- RNF-SUB-001 Cambios de estado no deben duplicarse ante reintentos de eventos externos.
- RNF-SUB-002 Historial completo para soporte y resolución de incidencias.
- RNF-SUB-003 El historial de la suscripción debe incluir desglose de IVA de cada ajuste económico.

## 5. Datos mínimos (ajustes de IVA)

- RF-SUB-008
  - suscripcion_id
  - plan_origen_id
  - plan_destino_id
  - ciclo_facturacion_origen
  - ciclo_facturacion_destino
  - base_prorrateada
  - iva_porcentaje_aplicado
  - iva_prorrateado
  - total_ajuste
  - fecha_efectiva_cambio

## 6. Implementación técnica (Sprint 2)

### Modelos

- `Plan` — configuración de plan con campos `iva_percentage`, `iva_modality`, `trial_days`, `auto_renew`, `currency`.
- `PlanBillingCycle` — ciclos de facturación habilitados por plan (`monthly`, `quarterly`, `annual`) con precio.
- `Subscription` — estados: `active`, `trial`, `suspended`, `cancelled`, `expired`. Registra `starts_at`, `next_billing_at`, `trial_ends_at`.
- `Payment` — desglose `subtotal`, `iva_amount`, `total`, `iva_modality`, `idempotency_key` por transacción.

### Servicios

- `TaxCalculationService` — calcula `subtotal`, `iva_amount` y `total` según modalidad IVA incluido/excluido.
- `SubscriptionService` — alta de suscripción validando ciclo habilitado; renovación actualizando `next_billing_at` y registrando pago.

### Jobs y cron

- `ProcessSubscriptionRenewalsJob` — procesa suscripciones con `next_billing_at <= now()`. Programado cada hora en el Kernel de consola.

### Migraciones

- `2026_02_25_000001_create_plans_table`
- `2026_02_25_000002_create_plan_billing_cycles_table`
- `2026_02_25_000003_create_subscriptions_table`
- `2026_02_25_000004_create_payments_table`