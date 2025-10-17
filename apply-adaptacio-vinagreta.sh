#!/bin/bash

# Script per reaplicar l'adaptació de La Vinagreta després d'actualització d'Aixada
# Ús: ./apply-adaptacio-vinagreta.sh

echo "🔧 Aplicant adaptació de La Vinagreta..."

# Crear backups dels fitxers originals
echo "📦 Creant backups..."
mkdir -p backups/$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)"

# Llista de fitxers a restaurar (Versió 4.3)
FILES=(
    "login.php"
    "dashboard.php"
    "aixada_main.php"
    "index.php"
    "css/vinagreta-custom.css"
    "php/inc/menu.inc.php"
    "php/inc/header.inc.base.php"
    "local_config/config.php"
    "manage_orders.php"
    "manage_stock.php"
    "manage_providers.php"
    "manage_orderable_products.php"
    "manage_money.php"
    "shop_and_orderstock.php"
)

# Restaurar fitxers de l'adaptació
echo "🔄 Restaurant fitxers de l'adaptació..."
for file in "${FILES[@]}"; do
    if [ -f "customizations/$file" ]; then
        cp "customizations/$file" "$file"
        echo "✅ Restaurat: $file"
    else
        echo "⚠️  No trobat: customizations/$file"
    fi
done

# Crear carpetes necessàries
echo "📁 Creant carpetes necessàries..."
mkdir -p debug_logs
mkdir -p local_config/custom_img

# Verificar imatges
echo "🖼️  Verificant imatges..."
if [ ! -f "local_config/custom_img/logo-vinagreta.png" ]; then
    echo "⚠️  Falta: local_config/custom_img/logo-vinagreta.png"
fi
if [ ! -f "local_config/custom_img/logo-aixada.png" ]; then
    echo "⚠️  Falta: local_config/custom_img/logo-aixada.png"
fi

# Permisos
echo "🔐 Ajustant permisos..."
chmod 755 debug_logs
chmod 644 css/vinagreta-custom.css

echo "✅ Adaptació aplicada!"
echo "📋 Revisa el README-adaptacio-vinagreta.md per més detalls"
