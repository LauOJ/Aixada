@echo off
REM Script per reaplicar l'adaptació de La Vinagreta després d'actualització d'Aixada

echo 🔧 Aplicant adaptació de La Vinagreta...

REM Crear backups dels fitxers originals
echo 📦 Creant backups...
for /f "tokens=2 delims==" %%a in ('wmic OS Get localdatetime /value') do set "dt=%%a"
set "YY=%dt:~2,2%" & set "YYYY=%dt:~0,4%" & set "MM=%dt:~4,2%" & set "DD=%dt:~6,2%"
set "HH=%dt:~8,2%" & set "Min=%dt:~10,2%" & set "Sec=%dt:~12,2%"
set "timestamp=%YYYY%%MM%%DD%_%HH%%Min%%Sec%"

mkdir backups\%timestamp% 2>nul

REM Llista de fitxers a restaurar (Versió 4.3)
set "files=login.php dashboard.php aixada_main.php index.php css\vinagreta-custom.css php\inc\menu.inc.php php\inc\header.inc.base.php local_config\config.php manage_orders.php manage_stock.php manage_providers.php manage_orderable_products.php manage_money.php shop_and_orderstock.php"

REM Restaurar fitxers de l'adaptació
echo 🔄 Restaurant fitxers de l'adaptació...
for %%f in (%files%) do (
    if exist "customizations\%%f" (
        copy "customizations\%%f" "%%f" >nul
        echo ✅ Restaurat: %%f
    ) else (
        echo ⚠️  No trobat: customizations\%%f
    )
)

REM Crear carpetes necessàries
echo 📁 Creant carpetes necessàries...
mkdir debug_logs 2>nul
mkdir local_config\custom_img 2>nul

REM Verificar imatges
echo 🖼️  Verificant imatges...
if not exist "local_config\custom_img\logo-vinagreta.png" (
    echo ⚠️  Falta: local_config\custom_img\logo-vinagreta.png
)
if not exist "local_config\custom_img\logo-aixada.png" (
    echo ⚠️  Falta: local_config\custom_img\logo-aixada.png
)

echo ✅ Adaptació aplicada!
echo 📋 Revisa el README-adaptacio-vinagreta.md per més detalls
pause
