# Sistema de Suscripciones

## 1. Propósito

Este documento define el alcance funcional y no funcional de un sistema de suscripciones con pagos recurrentes, enfocado en la gestión de usuarios, planes, descuentos y operación administrativa.

## 2. Alcance

El sistema incluye:

- Gestión de usuarios y autenticación.
- Gestión de planes y niveles de suscripción.
- Gestión de suscripciones y su ciclo de vida.
- Gestión de descuentos promocionales.
- Integración de pagos con Stripe y MercadoPago.
- Panel administrativo para operación y monitoreo.

## 3. Actores

- Usuario final: se registra, gestiona su perfil, se suscribe, paga y administra su suscripción.
- Administrador: gestiona usuarios, planes, descuentos, suscripciones y reportes operativos.
- Pasarela de pago: procesa cobros, renovaciones y eventos de pago.

## 4. Enfoque de solución

- Estrategia de cobro por proveedor de pago (Stripe o MercadoPago).
- Gestión modular de servicios para facilitar mantenimiento y evolución.
- Procesamiento por eventos para suscripciones y pagos.
- Separación de lógica de negocio y acceso a datos.

## 5. Tecnologías base

- Backend: Laravel (PHP)
- Base de datos: MySQL
- Frontend: Blade, Livewire 2, Alpine.js, Vite, Tailwind CSS
- Autenticación: Laravel Breeze
- Control de acceso: Gates y Policies
- Pruebas: PHPUnit

## 6. Supuestos y restricciones

- Moneda y zona horaria se configuran a nivel de aplicación.
- El sistema opera con al menos un proveedor de pago activo.
- Los requisitos regulatorios mínimos de privacidad y seguridad aplican en todo flujo de datos personales y pagos.

## 7. Requisitos no funcionales globales

- RNF-001 Seguridad: contraseñas hasheadas, control de acceso por roles y protección CSRF/XSS.
- RNF-002 Auditoría: historial de cambios críticos (suscripciones, pagos, descuentos).
- RNF-003 Disponibilidad: continuidad operativa para renovaciones automáticas y eventos de pago.
- RNF-004 Observabilidad: registro estructurado de errores y eventos relevantes.
- RNF-005 Mantenibilidad: módulos desacoplados por dominio (usuarios, planes, descuentos, suscripciones, pagos).

## 8. Anexos de requerimientos funcionales

- [Usuarios](users.md)
- [Administración](admin.md)
- [Planes](plans.md)
- [Suscripciones](subscriptions.md)
- [Descuentos](discounts.md)
- [Pagos](payments.md)

## 9. Plan de desarrollo

- [Plan de desarrollo](development-plan.md)