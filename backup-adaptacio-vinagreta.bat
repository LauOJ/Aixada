@echo off
REM Script per crear backup de l'adaptació de La Vinagreta

echo 📦 Creant backup de l'adaptació de La Vinagreta...

REM Crear carpeta de personalitzacions
mkdir customizations 2>nul
mkdir customizations\local_config\custom_img 2>nul
mkdir customizations\php\inc 2>nul
mkdir customizations\php\ctrl 2>nul
mkdir customizations\css 2>nul
mkdir customizations\debug_logs 2>nul

REM Llista de fitxers de l'adaptació (Versió 4.3)
set "files=login.php dashboard.php aixada_main.php index.php css\vinagreta-custom.css php\inc\menu.inc.php php\inc\header.inc.base.php local_config\config.php manage_orders.php manage_stock.php manage_providers.php manage_orderable_products.php manage_money.php shop_and_orderstock.php README-adaptacio-vinagreta.md"

REM Copiar fitxers
echo 🔄 Copiant fitxers de l'adaptació...
for %%f in (%files%) do (
    if exist "%%f" (
        copy "%%f" "customizations\%%f" >nul
        echo ✅ Copiat: %%f
    ) else (
        echo ⚠️  No trobat: %%f
    )
)

REM Copiar imatges
echo 🖼️  Copiant imatges...
if exist "local_config\custom_img\logo-vinagreta.png" (
    copy "local_config\custom_img\logo-vinagreta.png" "customizations\local_config\custom_img\" >nul
    echo ✅ Copiat: logo-vinagreta.png
)
if exist "local_config\custom_img\logo-aixada.png" (
    copy "local_config\custom_img\logo-aixada.png" "customizations\local_config\custom_img\" >nul
    echo ✅ Copiat: logo-aixada.png
)

REM Crear timestamp per l'arxiu
for /f "tokens=2 delims==" %%a in ('wmic OS Get localdatetime /value') do set "dt=%%a"
set "YY=%dt:~2,2%" & set "YYYY=%dt:~0,4%" & set "MM=%dt:~4,2%" & set "DD=%dt:~6,2%"
set "HH=%dt:~8,2%" & set "Min=%dt:~10,2%" & set "Sec=%dt:~12,2%"
set "timestamp=%YYYY%%MM%%DD%_%HH%%Min%%Sec%"

REM Crear arxiu comprimit
echo 🗜️  Creant arxiu comprimit...
powershell Compress-Archive -Path customizations\* -DestinationPath "adaptacio-vinagreta-%timestamp%.zip"
echo ✅ Arxiu creat: adaptacio-vinagreta-%timestamp%.zip

echo ✅ Backup completat!
echo 📁 Tots els fitxers de l'adaptació estan a la carpeta 'customizations\'
pause
