# Release Checklist — Sprint 4 (Release Candidate)

## HU-F3: Regression checklist and release candidate

---

## 1. Functional validation

### Discounts (HU-E1, HU-E2, HU-E3)

- [x] CRUD de descuentos: crear, listar, mostrar, actualizar, eliminar lógicamente.
- [x] Validación de formato, tipo (porcentaje, fijo, prueba gratuita) y vigencia.
- [x] Endpoint `POST /api/discounts/validate` rechaza código inválido/vencido/agotado con motivo explícito.
- [x] Solo un descuento por suscripción (RN-DES-001).
- [x] Descuentos inactivos/pausados/eliminados no se aplican en checkout.
- [x] Cálculo correcto de descuento por porcentaje y monto fijo (RF-DES-003).
- [x] Registro de uso en `discount_usages` con usuario, plan, suscripción, monto y fecha (RF-DES-005).
- [x] Incremento del contador `used_count` tras cada aplicación.
- [x] Reporte de uso accesible en `GET /api/reports/discount-usage` con filtros por período y código.

### Checkout (HU-E2)

- [x] `POST /api/checkout/preview` devuelve subtotal + IVA + descuento + total.
- [x] `POST /api/checkout/confirm` requiere autenticación (Sanctum).
- [x] Checkout respeta modalidad de IVA incluido/excluido del plan.
- [x] Descuento de plan restringido se rechaza para plan incorrecto (RN-DES-004).
- [x] Se crea suscripción y pago al confirmar checkout.

### Dashboard operativo (HU-F1)

- [x] `GET /api/admin/dashboard` devuelve métricas por rango de fechas.
- [x] Métricas incluyen: suscripciones activas, ingresos (subtotal/IVA/total), pagos fallidos, descuentos aplicados, nuevos usuarios.

### Reportes (HU-E3, HU-F2)

- [x] `GET /api/reports/discount-usage` — historial paginado con resumen.
- [x] `GET /api/reports/failed-payments` — pagos fallidos paginados con resumen.

---

## 2. Pruebas automatizadas

| Suite | Tests | Estado |
|-------|-------|--------|
| `DiscountCrudTest` | 10 tests | ✅ Pasan |
| `CheckoutTest`     | 7 tests  | ✅ Pasan |
| `AdminDashboardTest` | 5 tests | ✅ Pasan |
| `ExampleTest`      | 1 test   | ✅ Pasan |
| **Total**          | **25**   | ✅ OK   |

Comando: `php vendor/phpunit/phpunit/phpunit --testsuite Feature`

---

## 3. Migraciones y esquema

- [x] `plans` — nombre único, precio, IVA, ciclos de facturación, prueba, estado.
- [x] `subscriptions` — usuario, plan, estado, ciclo, fechas clave, código de descuento.
- [x] `payments` — desglose subtotal/IVA/total, clave de idempotencia, estado, proveedor.
- [x] `discounts` — código único, tipo, valor, vigencia, límite de usos, estado, restricciones.
- [x] `discount_usages` — historial con FK a descuento, usuario, plan, suscripción, pago.

---

## 4. Reglas de negocio verificadas

| Regla | Descripción | Verificado |
|-------|-------------|-----------|
| RN-DES-001 | Solo un descuento por suscripción | ✅ |
| RN-DES-002 | Vigencia por fecha y/o límite de usos | ✅ |
| RN-DES-003 | Tipos: porcentaje, fijo, prueba gratuita | ✅ |
| RN-DES-004 | Restricción por plan | ✅ |
| RN-ADM-001 | Endpoints de checkout requieren auth | ✅ |
| RN-PAY-005 | IVA incluido/excluido respetado en checkout | ✅ |

---

## 5. Checklist de salida a producción

### Pre-deploy

- [ ] Revisar variables de entorno (`.env.production`): DB, APP_KEY, SANCTUM, proveedores de pago.
- [ ] Ejecutar migraciones en entorno staging: `php artisan migrate --force`.
- [ ] Ejecutar suite de pruebas completa contra DB de staging.
- [ ] Revisar logs de errores en staging.

### Deploy

- [ ] Backup de base de datos de producción.
- [ ] Ejecutar migraciones: `php artisan migrate --force`.
- [ ] Limpiar caché: `php artisan config:cache && php artisan route:cache`.
- [ ] Verificar health-check endpoint.

### Post-deploy

- [ ] Smoke test: crear descuento, validarlo, hacer checkout con descuento.
- [ ] Verificar dashboard devuelve datos correctos.
- [ ] Verificar reportes paginan correctamente.
- [ ] Monitorear logs de errores las primeras 24 h.

---

## 6. Puntos pendientes para versiones futuras

- Autenticación de endpoints admin con middleware de rol (RN-ADM-001).
- 2FA para cuentas administrativas (RNF-ADM-001).
- Integración real con Stripe y MercadoPago (webhooks, reintentos).
- Renovaciones automáticas via job/queue (HU-C2).
- Reportes exportables a CSV/Excel.
