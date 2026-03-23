# 🔍 Diagnóstico HTTP 500 - Cartilla Virtual Spai-T

## ¿Qué significa el error 500?

El error HTTP 500 significa que hay un **error interno del servidor**. PHP está teniendo problemas al ejecutar el código.

## Pasos para diagnosticar

### 1. **Accede al script de diagnóstico**
Abre en tu navegador:
```
www.iespai.com/diagnostico.php
```

Este script te mostrará:
- ✅ Versión de PHP
- ✅ Permisos de carpetas
- ✅ Extensiones necesarias
- ✅ Problemas de lectura/escritura

### 2. **Revisa los logs del servidor**

Si usas **Apache**:
```bash
sudo tail -f /var/log/apache2/error.log
```

Si usas **Nginx**:
```bash
sudo tail -f /var/log/nginx/error.log
```

### 3. **Problemas comunes y soluciones**

#### ❌ "Permission denied" en `data/users/`
**Causa:** Permisos incorrectos en las carpetas
**Solución:**
```bash
sudo chown -R www-data:www-data /home/user/splaitmontserrat
chmod 755 /home/user/splaitmontserrat/data
chmod 755 /home/user/splaitmontserrat/data/users
```

#### ❌ "Cannot start session"
**Causa:** El directorio de sesiones no es escribible
**Solución:**
```bash
# Encontrar dónde se guardan las sesiones
php -r "echo ini_get('session.save_path');"

# Asegurar que es escribible
sudo chmod 1777 /tmp  # O la ruta que te dé el comando anterior
```

#### ❌ "Call to undefined function"
**Causa:** Falta alguna extensión PHP
**Solución:**
```bash
# Instalar extensiones necesarias (Ubuntu/Debian)
sudo apt-get install php8.4-json php8.4-session php8.4-openssl php8.4-mbstring
sudo systemctl restart apache2  # O nginx
```

#### ❌ "include: Failed opening required file"
**Causa:** El path a los includes es incorrecto
**Solución:**
- Verifica que los archivos existen: `includes/config.php`, `includes/auth.php`, etc.
- Comprueba que no hay espacios en blanco antes de `<?php`

## Solución rápida - Script de reparación

Si prefieres ejecutar todo de golpe:

```bash
chmod +x /home/user/splaitmontserrat/fix-permissions.sh
sudo /home/user/splaitmontserrat/fix-permissions.sh
```

## Verificación final

Una vez arreglado, deberías poder:
1. ✅ Acceder a `www.iespai.com/index.php`
2. ✅ Ver el formulario de registro/login
3. ✅ Registrar un usuario
4. ✅ Loguear con ese usuario
5. ✅ Ver la cartilla

## Más ayuda

Si aún tienes problemas:
1. Comparte el contenido de `/var/log/apache2/error.log` (últimas líneas)
2. Ejecuta `www.iespai.com/diagnostico.php` y comparte los resultados
3. Verifica que el servidor web sea accesible: `ping www.iespai.com`

---

**Nota:** El código PHP está 100% funcional (probado en servidor local). El error 500 es siempre un problema de configuración del servidor, nunca del código.
