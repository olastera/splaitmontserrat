# 🧪 Test Suite — Cartilla Virtual Spai-T

Suite de pruebas automáticas para verificar que las funcionalidades principales funcionan correctamente.

## 🚀 Inicio rápido

```bash
php tests/TestRunner.php
```

## 📊 Qué se prueba

### 1. **PermissionTest** — Permisos de carpetas
- ✅ Directorios necesarios existen
- ✅ Carpetas son escribibles
- ✅ Archivos críticos son legibles
- ✅ Archivos `.htaccess` de seguridad están presentes
- ⚙️ **Auto-fix**: Pregunta si habilitar permisos si hay problemas

### 2. **AuthTest** — Autenticación
- ✅ Los archivos `include/` cargan correctamente
- ✅ Crear usuario funciona
- ✅ Buscar usuario por ID funciona
- ✅ Cifrado de contraseñas funciona (AES-256-CBC)
- ✅ Login funciona (con email y contraseña)
- ✅ Login rechaza contraseña incorrecta

### 3. **FeaturesTest** — Funcionalidades principales
- ✅ Configuración carga correctamente
- ✅ Sistema de check-in funciona
- ✅ Funciones de ranking funcionan:
  - `get_overall_ranking()` — Top 10 usuarios
  - `get_ranking_by_stop()` — Top 10 por parada
  - `get_medal()` — Medallas 🥇🥈🥉
- ✅ Todas las páginas principales existen

## 📝 Ejemplo de salida

```
════════════════════════════════════════
  🧪 TEST SUITE — Cartilla Virtual Spai-T
════════════════════════════════════════

📋 PermissionTest
──────────────────────────────────────────
  ℹ️  Verificando que existen directorios clave...
  ✅ Directorio existe: data/
  ✅ Directorio existe: data/users/
  ✅ Es escribible: data/users/ (para guardar usuarios)
  ✅ .htaccess presente (raíz)
  ...

📋 AuthTest
──────────────────────────────────────────
  ℹ️  Verificando que los archivos include cargan correctamente...
  ✅ config.php cargado
  ✅ auth.php cargado
  ✅ user.php cargado
  ...

📋 FeaturesTest
──────────────────────────────────────────
  ℹ️  Preparando datos de prueba...
  ✅ Usuario de prueba creado
  ✅ get_settings() retorna un array
  ...

════════════════════════════════════════
📊 RESUMEN FINAL
════════════════════════════════════════
  ✅ Pasadas: 45
  ❌ Fallidas: 0
  ⚠️  Advertencias: 0

✨ ¡TODAS LAS PRUEBAS PASARON! ✨
```

## 🔧 Cómo auto-arreglar permisos

Si las pruebas detectan problemas de permisos, te preguntarán:

```
  ⚠️  HAY PROBLEMAS DE PERMISOS
  ¿Deseas que intente arreglarlo automáticamente? [s/n]: s
```

Responde con `s` (sí) y se intentarán arreglar automáticamente los permisos de las carpetas.

Si necesitas usar `sudo`:

```bash
sudo php tests/TestRunner.php
```

## 🛠️ Estructura de archivos

```
tests/
├── TestRunner.php      ← Ejecutador principal
├── PermissionTest.php  ← Pruebas de permisos
├── AuthTest.php        ← Pruebas de autenticación
├── FeaturesTest.php    ← Pruebas de funcionalidades
└── README.md           ← Este archivo
```

## 🔍 Agregar más tests

Para agregar nuevas pruebas:

1. **Crear nueva clase** que extienda `TestCase`:
```php
class MyNewTest extends TestCase {
    public function run(): void {
        $this->info("Probando algo...");
        $this->assert($condition, "Mensaje de prueba");
    }
}
```

2. **Incluir en TestRunner.php**:
```php
require_once 'MyNewTest.php';

$tests = [
    // ... otros tests
    'MyNewTest' => new MyNewTest(),
];
```

## 📋 Métodos disponibles en TestCase

```php
$this->assert($condition, $message);      // Verificar condición
$this->warn($message);                    // Advertencia (no falla la prueba)
$this->info($message);                    // Información (solo logs)
```

## 🎯 Códigos de salida

- **0** (EXIT_SUCCESS) — Todas las pruebas pasaron
- **1** (EXIT_FAILURE) — Hay pruebas que fallaron

Útil para CI/CD:

```bash
php tests/TestRunner.php && echo "✅ Tests OK" || echo "❌ Tests failed"
```

## 🚀 Integración CI/CD

Para ejecutar automáticamente antes de cada deploy:

```yaml
# .github/workflows/test.yml
- name: Run tests
  run: php tests/TestRunner.php
```

---

**Última actualización:** 2026-04-23  
**Mantén estos tests actualizados cuando añadas nuevas funcionalidades!**
