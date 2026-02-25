# Requerimientos formales: Descuentos

## 1. Alcance

Define la creación, validación, aplicación y seguimiento de promociones en suscripciones.

## 2. Reglas de negocio

- RN-DES-001 Solo se permite un código de descuento por suscripción.
- RN-DES-002 Un descuento puede tener vigencia por fecha, límite de usos o ambos.
- RN-DES-003 Los tipos permitidos son porcentaje, monto fijo y prueba gratuita.
- RN-DES-004 Un descuento puede restringirse por plan, segmento de usuario o tipo de alta.

## 3. Requisitos funcionales

- RF-DES-001 Crear código de descuento con tipo, valor, vigencia y restricciones.
	- Criterio de aceptación: el código queda disponible solo si cumple validaciones de formato y fechas.
- RF-DES-002 Validar código al inicio de checkout y antes de confirmar pago.
	- Criterio de aceptación: un código inválido o vencido se rechaza con motivo explícito.
- RF-DES-003 Aplicar descuento al cálculo de cobro respetando límites del plan.
	- Criterio de aceptación: el monto final enviado a la pasarela coincide con el cálculo interno.
- RF-DES-004 Gestionar ciclo de vida del descuento (activar, pausar, eliminar lógicamente).
	- Criterio de aceptación: descuentos inactivos no pueden aplicarse en nuevas compras.
- RF-DES-005 Registrar historial del uso de cada código.
	- Criterio de aceptación: cada aplicación guarda usuario, plan, suscripción, monto y fecha.

## 4. Requisitos no funcionales del módulo

- RNF-DES-001 Validaciones de descuentos deben ejecutarse de forma consistente y auditable.
- RNF-DES-002 Reportes de uso deben permitir análisis por período y por campaña.
