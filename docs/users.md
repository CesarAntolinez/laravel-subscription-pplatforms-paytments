# Requerimientos formales: Usuarios

## 1. Alcance

Define autenticación, gestión de perfil, seguridad de cuenta y relación con administración de usuarios.

## 2. Reglas de negocio

- RN-USR-001 El correo electrónico debe ser único por cuenta.
- RN-USR-002 El acceso a funciones administrativas depende de rol y permisos.
- RN-USR-003 El usuario debe aceptar términos y política de privacidad en registro.

## 3. Requisitos funcionales

- RF-USR-001 Registro de usuario con nombre, correo y contraseña.
	- Criterio de aceptación: cuenta creada con estado pendiente o activo según política de verificación.
- RF-USR-002 Inicio de sesión con credenciales válidas y opcional 2FA.
	- Criterio de aceptación: credenciales inválidas no inician sesión y generan evento de seguridad.
- RF-USR-003 Recuperación de contraseña mediante token temporal.
	- Criterio de aceptación: token expira y solo permite una actualización válida de contraseña.
- RF-USR-004 Gestión de perfil (datos básicos y contraseña).
	- Criterio de aceptación: cambios de perfil se persisten y quedan auditados.
- RF-USR-005 Verificación de correo electrónico posterior al registro.
	- Criterio de aceptación: acciones sensibles pueden restringirse hasta verificar correo.
- RF-USR-006 Gestión de roles y permisos por administrador.
	- Criterio de aceptación: los cambios de permisos se reflejan de forma inmediata en los accesos del usuario.

## 4. Requisitos no funcionales del módulo

- RNF-USR-001 Contraseñas almacenadas con hash seguro y política de complejidad configurable.
- RNF-USR-002 Protección contra fuerza bruta mediante límite de intentos y bloqueo temporal.
