# Plan de desarrollo

## 1. Objetivo

Definir un plan ejecutable para construir el sistema de suscripciones a partir de los requerimientos formales ya documentados.

## 2. Alcance del plan

- Incluye: usuarios, administración, planes, suscripciones, descuentos, pagos e IVA.
- Incluye integración con Stripe y MercadoPago.
- Incluye pruebas funcionales e integración para salida a producción.

## 3. Supuestos de ejecución

- Duración total: 8 semanas.
- Cadencia: 4 sprints de 2 semanas.
- Equipo de referencia: 1 backend, 1 fullstack, 1 QA (parcial), 1 PM (parcial).

## 4. Backlog por épicas

### ÉPICA A: Base del producto y arquitectura

- HU-A1: Como equipo técnico, quiero estructura base del proyecto y módulos desacoplados para desarrollar por dominio.
  - Referencias: RNF-005, alcance general.
  - Estimación: 5 pts
- HU-A2: Como administrador, quiero autenticación y autorización por roles para operar de forma segura.
  - Referencias: RF-USR-002, RF-USR-006, RN-ADM-001.
  - Estimación: 8 pts
- HU-A3: Como operación, quiero auditoría de acciones críticas para seguimiento de cambios.
  - Referencias: RNF-002, RNF-ADM-001, RNF-PLA-001.
  - Estimación: 5 pts

### ÉPICA B: Planes e IVA

- HU-B1: Como administrador, quiero crear y editar planes con niveles para ofertar productos distintos.
  - Referencias: RF-PLA-001, RF-PLA-002, RF-PLA-003.
  - Estimación: 8 pts
- HU-B2: Como administrador, quiero configurar ciclos de cobro por plan para ofrecer flexibilidad comercial.
  - Referencias: RN-PLA-002, RF-PLA-004.
  - Estimación: 5 pts
- HU-B3: Como administrador, quiero definir IVA por plan para calcular cobros correctamente.
  - Referencias: RF-PLA-007, RF-PLA-008, RNF-PLA-003.
  - Estimación: 5 pts
- HU-B4: Como usuario, quiero ver desglose subtotal + IVA + total para entender el cobro.
  - Referencias: criterios RF-PLA-007 y RF-PAY-007.
  - Estimación: 3 pts

### ÉPICA C: Suscripciones

- HU-C1: Como usuario, quiero contratar una suscripción eligiendo ciclo permitido para iniciar servicio.
  - Referencias: RN-SUB-003, RF-SUB-001.
  - Estimación: 8 pts
- HU-C2: Como sistema, quiero renovar suscripciones automáticamente para mantener continuidad.
  - Referencias: RF-SUB-002.
  - Estimación: 5 pts
- HU-C3: Como usuario/admin, quiero cancelar o reactivar suscripciones según política.
  - Referencias: RF-SUB-003, RF-SUB-004.
  - Estimación: 5 pts
- HU-C4: Como usuario, quiero cambiar de plan/ciclo con ajuste e IVA para mantener consistencia de cobro.
  - Referencias: RF-SUB-005, RF-SUB-008, RN-SUB-005.
  - Estimación: 8 pts

### ÉPICA D: Pagos

- HU-D1: Como sistema, quiero cobrar alta inicial y renovaciones con proveedor de pago configurado.
  - Referencias: RF-PAY-001, RF-PAY-002, RF-PAY-003.
  - Estimación: 13 pts
- HU-D2: Como sistema, quiero gestionar reintentos y notificaciones ante fallos de pago.
  - Referencias: RN-PAY-003, RF-PAY-004, RF-PAY-005.
  - Estimación: 8 pts
- HU-D3: Como operación, quiero conciliar eventos de pago sin duplicados para evitar inconsistencias.
  - Referencias: RN-PAY-001, RN-PAY-004, RF-PAY-006.
  - Estimación: 8 pts
- HU-D4: Como contabilidad, quiero guardar desglose de IVA por transacción para auditoría.
  - Referencias: RF-PAY-007, RF-PAY-008, RNF-PAY-003.
  - Estimación: 5 pts

### ÉPICA E: Descuentos

- HU-E1: Como administrador, quiero crear campañas de descuento con reglas de vigencia y uso.
  - Referencias: RF-DES-001, RN-DES-002, RN-DES-004.
  - Estimación: 8 pts
- HU-E2: Como usuario, quiero aplicar un código válido en checkout para obtener beneficio.
  - Referencias: RF-DES-002, RF-DES-003, RN-DES-001.
  - Estimación: 5 pts
- HU-E3: Como operación, quiero ver historial de uso de descuentos por período.
  - Referencias: RF-DES-005, RNF-DES-002.
  - Estimación: 3 pts

### ÉPICA F: Operación, reportes y salida a producción

- HU-F1: Como administrador, quiero un tablero con métricas para monitoreo del negocio.
  - Referencias: RF-ADM-001.
  - Estimación: 5 pts
- HU-F2: Como operación, quiero monitorear pagos fallidos y estados de suscripción para soporte.
  - Referencias: RF-ADM-004, RF-ADM-006, RNF-SUB-002.
  - Estimación: 5 pts
- HU-F3: Como equipo, quiero pruebas de regresión y checklist de release para salida segura.
  - Referencias: RNF globales y criterios de aceptación por módulo.
  - Estimación: 8 pts

## 5. Plan por sprint (2 semanas)

### Sprint 1: Fundaciones

- Objetivo: dejar base técnica, seguridad y módulo de planes operativo.
- Historias sugeridas: HU-A1, HU-A2, HU-A3, HU-B1, HU-B2.
- Entregable: administración básica de planes y roles.

### Sprint 2: Suscripciones + IVA

- Objetivo: alta de suscripción con ciclos de cobro y cálculo de IVA.
- Historias sugeridas: HU-B3, HU-B4, HU-C1, HU-C2.
- Entregable: flujo de suscripción funcional con renovación programada.

### Sprint 3: Pagos integrados

- Objetivo: cobro real con proveedores, reintentos y conciliación.
- Historias sugeridas: HU-D1, HU-D2, HU-D3, HU-D4.
- Entregable: pagos en entorno de pruebas end-to-end.

### Sprint 4: Descuentos + operación + release

- Objetivo: cerrar operación comercial y calidad de salida.
- Historias sugeridas: HU-E1, HU-E2, HU-E3, HU-F1, HU-F2, HU-F3.
- Entregable: versión candidata a producción con checklist de liberación.

## 6. Definición de terminado (DoD)

- Cumple criterios de aceptación del RF asociado.
- Tiene pruebas automatizadas mínimas (feature/integración) según módulo.
- Incluye validación de permisos y auditoría cuando corresponda.
- Incluye manejo de errores de negocio y mensajes al usuario.
- Documentación actualizada en la carpeta docs.

## 7. Riesgos y mitigación

- Riesgo: complejidad de integración con pasarelas.
  - Mitigación: desarrollar primero en sandbox y con pruebas de eventos de pago.
- Riesgo: inconsistencias de cobro en cambios de plan/ciclo.
  - Mitigación: pruebas de prorrateo e IVA con casos límite.
- Riesgo: retrasos por alcance adicional no planificado.
  - Mitigación: controlar backlog con priorización MoSCoW por sprint.

## 8. Prioridad sugerida (MoSCoW)

- Must: Épicas A, B, C y D.
- Should: Épica E.
- Could: reportes avanzados adicionales.
- Won't (versión inicial): analítica avanzada y automatizaciones no críticas.

## 9. Ejecución con agentes de Copilot

- [Prompts para 4 agentes](copilot-agents-prompts.md)
- [Plantilla de PR checklist](pr-checklist-template.md)