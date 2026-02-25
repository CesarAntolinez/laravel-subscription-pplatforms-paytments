# Prompts para 4 agentes de GitHub Copilot

## Cómo usar

- Crea 4 tareas separadas (una por agente).
- Ejecuta cada agente en su propia rama.
- Usa revisión por PR con los criterios de aceptación de cada bloque.

---

## Agente 1 — Fundaciones (Sprint 1)

### Objetivo

Implementar base técnica, seguridad inicial y administración de planes.

### Alcance

- HU-A1, HU-A2, HU-A3
- HU-B1, HU-B2

### Referencias

- [Plan de desarrollo](development-plan.md)
- [Usuarios](users.md)
- [Administración](admin.md)
- [Planes](plans.md)

### Prompt sugerido

Actúa como desarrollador senior Laravel en este repositorio. Implementa el Sprint 1 según docs/development-plan.md, incluyendo: arquitectura base por dominios, autenticación/autorización por roles, auditoría de cambios críticos y CRUD de planes con niveles y ciclos de facturación. Respeta los requerimientos de docs/users.md, docs/admin.md y docs/plans.md. No cambies alcance fuera de esas historias.

Entregables obligatorios:
1) Migraciones y modelos necesarios.
2) Controladores/servicios/policies/gates necesarios.
3) Rutas web/api necesarias.
4) Pruebas Feature para los flujos clave.
5) Actualización de docs afectadas.

Criterios de calidad:
- Código consistente con Laravel del proyecto.
- Sin romper funcionalidades existentes.
- Validaciones y manejo de errores claros.
- Incluye resumen final: archivos tocados, decisiones técnicas, pruebas ejecutadas.

### Definición de terminado

- CRUD de planes operativo con niveles y ciclos.
- Roles y permisos aplicados a pantallas/rutas admin.
- Auditoría básica de operaciones críticas.
- Pruebas principales pasando.

---

## Agente 2 — Suscripciones + IVA (Sprint 2)

### Objetivo

Implementar flujo de alta de suscripción, renovación y cálculo de IVA.

### Alcance

- HU-B3, HU-B4
- HU-C1, HU-C2

### Referencias

- [Planes](plans.md)
- [Suscripciones](subscriptions.md)
- [Pagos](payments.md)

### Prompt sugerido

Implementa Sprint 2 en este repositorio Laravel: suscripciones con selección de ciclo habilitado por plan, renovación automática y cálculo de IVA (incluido/excluido) con desglose subtotal+IVA+total. Usa como fuente docs/plans.md y docs/subscriptions.md. Mantén separación entre duración del plan y ciclo de cobro.

Entregables obligatorios:
1) Tablas/campos para suscripciones, calendario de cobro e IVA del plan.
2) Servicios para alta de suscripción y cálculo de importes con IVA.
3) Jobs/cron para renovaciones programadas.
4) Pruebas Feature e integración para alta y renovación.
5) Actualización de documentación técnica.

Criterios de calidad:
- Cálculo de IVA consistente en alta y renovación.
- Estados de suscripción correctos.
- Mensajes de error de negocio claros.

### Definición de terminado

- Alta de suscripción funcional con ciclo permitido.
- Renovación automática funcionando en entorno local.
- Desglose de cobro con IVA visible y persistido.

---

## Agente 3 — Pagos integrados (Sprint 3)

### Objetivo

Integrar cobro con Stripe/MercadoPago, reintentos y conciliación de eventos.

### Alcance

- HU-D1, HU-D2, HU-D3, HU-D4

### Referencias

- [Pagos](payments.md)
- [Suscripciones](subscriptions.md)

### Prompt sugerido

Implementa Sprint 3: integración de pagos con Stripe y MercadoPago usando estrategia por proveedor, cobro inicial y renovaciones, reintentos por fallos, notificaciones y conciliación de eventos para evitar cobros duplicados. Persistir desglose de IVA por transacción y mantener consistencia de estados de suscripción.

Entregables obligatorios:
1) Interfaces y estrategias por proveedor de pago.
2) Manejo de eventos de pago y conciliación segura.
3) Política de reintentos configurable.
4) Registro de transacciones con subtotal, IVA y total.
5) Pruebas de integración con stubs/mocks de proveedor.

Criterios de calidad:
- Sin dobles cobros por reintentos/eventos repetidos.
- Estado de suscripción alineado al resultado de pago.
- Logs útiles para soporte.

### Definición de terminado

- Cobros de alta y renovación funcionando en sandbox.
- Reintentos y conciliación validados por pruebas.
- Historial de transacciones completo con IVA.

---

## Agente 4 — Descuentos + Operación + Release (Sprint 4)

### Objetivo

Cerrar capacidades comerciales y preparar salida a producción.

### Alcance

- HU-E1, HU-E2, HU-E3
- HU-F1, HU-F2, HU-F3

### Referencias

- [Descuentos](discounts.md)
- [Administración](admin.md)
- [Plan de desarrollo](development-plan.md)

### Prompt sugerido

Implementa Sprint 4: módulo de descuentos (creación, validación, aplicación y reportes), tablero operativo admin con métricas clave y checklist de release. Mantén una sola promoción por suscripción y compatibilidad con cálculo de IVA/pagos ya implementados.

Entregables obligatorios:
1) CRUD de descuentos con reglas de vigencia y uso.
2) Aplicación de descuento en checkout respetando restricciones.
3) Dashboard operativo con métricas esenciales.
4) Reporte básico de uso de descuentos y pagos fallidos.
5) Checklist final de release y pruebas de regresión.

Criterios de calidad:
- Reglas de negocio de descuentos cumplidas.
- Métricas útiles para operación.
- Evidencia de pruebas antes de liberar.

### Definición de terminado

- Flujo comercial completo funcionando (plan + suscripción + pago + descuento).
- Panel admin listo para operación diaria.
- Release candidate documentada.

---

## Convención recomendada de ramas

- feat/sprint1-foundations
- feat/sprint2-subscriptions-iva
- feat/sprint3-payments
- feat/sprint4-discounts-ops-release

## Formato de entrega en cada PR

- Resumen funcional implementado.
- Historias/HU cubiertas.
- Archivos modificados.
- Pruebas ejecutadas y resultado.
- Pendientes y riesgos.

Usar esta plantilla para estandarizar la revisión:

- [Plantilla de PR checklist](pr-checklist-template.md)