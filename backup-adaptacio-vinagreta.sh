#!/bin/bash

# Script per crear backup de l'adaptació de La Vinagreta
# Ús: ./backup-adaptacio-vinagreta.sh

echo "📦 Creant backup de l'adaptació de La Vinagreta..."

# Crear carpeta de personalitzacions
mkdir -p customizations
mkdir -p customizations/local_config/custom_img
mkdir -p customizations/php/inc
mkdir -p customizations/php/ctrl
mkdir -p customizations/css
mkdir -p customizations/debug_logs

# Llista de fitxers de l'adaptació (Versió 4.3)
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
    "README-adaptacio-vinagreta.md"
)

# Copiar fitxers
echo "🔄 Copiant fitxers de l'adaptació..."
for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        cp "$file" "customizations/$file"
        echo "✅ Copiat: $file"
    else
        echo "⚠️  No trobat: $file"
    fi
done

# Copiar imatges
echo "🖼️  Copiant imatges..."
if [ -f "local_config/custom_img/logo-vinagreta.png" ]; then
    cp "local_config/custom_img/logo-vinagreta.png" "customizations/local_config/custom_img/"
    echo "✅ Copiat: logo-vinagreta.png"
fi
if [ -f "local_config/custom_img/logo-aixada.png" ]; then
    cp "local_config/custom_img/logo-aixada.png" "customizations/local_config/custom_img/"
    echo "✅ Copiat: logo-aixada.png"
fi

# Crear arxiu comprimit
echo "🗜️  Creant arxiu comprimit..."
tar -czf "adaptacio-vinagreta-$(date +%Y%m%d_%H%M%S).tar.gz" customizations/
echo "✅ Arxiu creat: adaptacio-vinagreta-$(date +%Y%m%d_%H%M%S).tar.gz"

echo "✅ Backup completat!"
echo "📁 Tots els fitxers de l'adaptació estan a la carpeta 'customizations/'"
