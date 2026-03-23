#!/bin/bash
# ============================================
# Script para reparar permisos de Spai-T
# ============================================

echo "🔧 Reparando permisos del proyecto..."

# Determinar el usuario del servidor web
WEB_USER="www-data"  # Para Apache/Nginx estándar en Debian/Ubuntu

# Cambiar propietario de la carpeta
echo "📁 Cambiando propietario a $WEB_USER:$WEB_USER..."
sudo chown -R $WEB_USER:$WEB_USER /home/user/splaitmontserrat

# Establecer permisos de directorios
echo "📋 Estableciendo permisos de directorios..."
find /home/user/splaitmontserrat -type d -exec chmod 755 {} \;

# Establecer permisos de archivos PHP
echo "📄 Estableciendo permisos de archivos..."
find /home/user/splaitmontserrat -type f -name "*.php" -exec chmod 644 {} \;

# Permisos especiales para carpeta data/
echo "🔐 Protegiendo carpeta data/..."
chmod 750 /home/user/splaitmontserrat/data
chmod 750 /home/user/splaitmontserrat/data/users

# Permisos para CSS, JS, IMG
chmod 644 /home/user/splaitmontserrat/assets/css/*.css
find /home/user/splaitmontserrat/assets -type f \( -name "*.js" -o -name "*.css" -o -name "*.png" -o -name "*.jpg" -o -name "*.gif" \) -exec chmod 644 {} \;

echo "✅ Permisos reparados!"
echo ""
echo "Para comprobar que funciona:"
echo "  1. Accede a: www.iespai.com/diagnostico.php"
echo "  2. Verifica que todos los checks están en ✅"
echo ""
echo "Si aún hay problemas:"
echo "  1. Revisa los logs: sudo tail -f /var/log/apache2/error.log"
echo "  2. Asegúrate de que el usuario web es: $WEB_USER"
