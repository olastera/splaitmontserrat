# 🧪 Tests de Producción — Cartilla Virtual Spai-T

Suite de pruebas automáticas para garantizar que todas las funcionalidades principales funcionan correctamente en cada cambio.

## ✨ Características

- **56 pruebas automáticas** que verifican:
  - Permisos de carpetas y archivos
  - Autenticación (login/registro)
  - Cifrado de contraseñas
  - Funcionalidades principales (cartilla, check-in, ranking)
  - Existencia de todas las páginas del proyecto

- **Auto-fix de permisos**: Si detecta problemas de permisos, pregunta si quieres que los arregle

- **Salida clara**: Colores en terminal, mensajes descriptivos, estadísticas finales

## 🚀 Ejecutar tests

```bash
php tests/TestRunner.php
```

### Ejemplos de salida

**✅ Todas las pruebas pasaron:**
```
════════════════════════════════════════
📊 RESUMEN FINAL
════════════════════════════════════════
  ✅ Pasadas: 56
  ❌ Fallidas: 0
  ⚠️  Advertencias: 0

✨ ¡TODAS LAS PRUEBAS PASARON! ✨
```

**❌ Hay problemas:**
```
  ❌ Fallidas: 1
  ⚠️  Advertencias: 2

⚠️  HAY PRUEBAS QUE FALLARON
```

## 📋 Pruebas incluidas

### PermissionTest (18 pruebas)
- Verifica que existen directorios: data/, data/users/, includes/, admin/, assets/
- Verifica que las carpetas son escribibles
- Verifica que los archivos críticos se pueden leer
- Verifica que existen .htaccess de seguridad
- **Auto-fix**: Puede crear directorios faltantes y cambiar permisos

### AuthTest (13 pruebas)
- Verifica que todos los includes cargan correctamente
- Prueba crear un usuario
- Prueba buscar usuario por ID
- Verifica cifrado/descifrado de contraseñas (AES-256-CBC)
- Prueba login con email y contraseña
- Verifica que login rechaza contraseña incorrecta
- Limpia usuarios de prueba

### FeaturesTest (25 pruebas)
- Crea usuario de prueba con check-ins
- Verifica que get_settings() funciona
- Verifica que todas las funciones de ranking existen
- Prueba sistema de check-in (agregar, verificar, contar)
- Prueba get_overall_ranking()
- Prueba get_ranking_by_stop()
- Prueba medallas 🥇🥈🥉
- Verifica que existen todos los archivos principales

## 🔧 Cómo usar en CI/CD

En GitHub Actions:

```yaml
name: Tests
on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Run tests
        run: php tests/TestRunner.php
```

En pre-commit hook:

```bash
#!/bin/bash
php tests/TestRunner.php || exit 1
```

## 📊 Códigos de salida

- `0` — Todas las pruebas pasaron ✅
- `1` — Hay pruebas que fallaron ❌

Útil para scripts de automatización:

```bash
php tests/TestRunner.php && echo "OK" || echo "FAILED"
```

## 🛠️ Agregar nuevas pruebas

1. Crear nueva clase que extienda `TestCase`:

```php
class MyTest extends TestCase {
    public function run(): void {
        $this->info("Probando algo...");
        $this->assert($condition, "Mensaje");
        $this->warn("Advertencia si es necesario");
    }
}
```

2. Incluir en `TestRunner.php`:

```php
require_once 'MyTest.php';

$tests = [
    // ... otros tests
    'MyTest' => new MyTest(),
];
```

## 📝 Métodos disponibles

```php
$this->assert($condition, $message);      // Verificar condición
$this->warn($message);                    // Advertencia
$this->info($message);                    // Información
```

## 🎯 Buenas prácticas

- ✅ Ejecutar tests antes de hacer commit
- ✅ Usar para CI/CD automation
- ✅ Agregar nuevas pruebas cuando agregues features
- ✅ Mantener los tests actualizados

---

**Última actualización:** 2026-04-23  
**Más información:** Consulta `tests/README.md`
