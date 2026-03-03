# Adaptació de La Vinagreta per Aixada

Aquest document descriu totes les adaptacions fetes a l'Aixada original per La Vinagreta. **És essencial mantenir aquest document actualitzat** per poder reaplicar els canvis després d'actualitzacions.

## 📋 Índex de Canvis

### **🆕 Fitxers Nous (Creats per La Vinagreta):**
- `dashboard.php` - Dashboard personalitzat completament nou
- `aixada_main.php` - Pàgina principal personalitzada (no existeix a l'original)
- `css/vinagreta-custom.css` - Estils personalitzats

### **🔧 Fitxers Modificats (de l'original):**
- `login.php` - Login personalitzat amb estils, redirecció i camp `oper=login`
- `index.php` - Redirecció al dashboard
- `php/inc/menu.inc.php` - Menú amb "Torna al tauler" i càrrega automàtica de CSS
- `php/inc/header.inc.base.php` - Funció per CSS personalitzat
- `local_config/config.php` - Credencials de BD i configuració (Docker + Pangea)
- `manage_orders.php` - Estils personalitzats + CSS personalitzat
- `manage_stock.php` - CSS personalitzat
- `manage_providers.php` - CSS personalitzat
- `manage_orderable_products.php` - CSS personalitzat
- `manage_money.php` - CSS personalitzat
- `shop_and_orderstock.php` - Estils personalitzats
- `css/vinagreta-custom.css` - Estils actualitzats (menú amb colors jQuery UI)

### **✅ Pàgines amb Estils Verificades (34 pàgines):**
- Totes les pàgines principals inclouen `header.inc.php` → `menu.inc.php` → `aixada_custom_css()`
- Estils aplicats automàticament a: manage_*, report_*, shop_*, torn.php, validate.php, etc.
- Templates inclosos: tpl/bill_model1.php, tpl/incidents_model1.php, tpl/order_model1.php, tpl/report_order1.php

### **📚 Documentació i Scripts:**
- `README-adaptacio-vinagreta.md` - Documentació completa
- `apply-adaptacio-vinagreta.bat/.sh` - Scripts de reaplicació
- `backup-adaptacio-vinagreta.bat/.sh` - Scripts de backup

---

1. [Sistema de Login i Dashboard](#sistema-de-login-i-dashboard)
2. [Estils Personalitzats](#estils-personalitzats)
3. [Configuració de Base de Dades](#configuració-de-base-de-dades)
4. [Menú i Navegació](#menú-i-navegació)
5. [Imatges i Recursos](#imatges-i-recursos)
6. [Rols i Permisos](#rols-i-permisos)
7. [Funcionalitats Personalitzades](#funcionalitats-personalitzades)

---

## 🆕 Últims Canvis (Versió 4.3)

### **🎨 Estils del Menú Principal:**
- **Hover actualitzat**: Colors de jQuery UI (tema redmond)
  - Fons: `#d0e5f5` (blau clar)
  - Text: `#1d5987` (blau fosc)
  - Border: `1px solid #79b7e7` (blau mitjà)
- **Efecte**: Mantingut `translateY(-2px)` per toc modern
- **Consistència**: Integració perfecta amb botons originals

### **🐳 Configuració Docker:**
- **Detecció d'entorn**: Automàtica (Docker vs Pangea)
- **Docker local**: `db_host = 'mysql'` (nom del contenidor)
- **Servidor Pangea**: `db_host = 'localhost'`

### **📄 Pàgines de Gestió:**
- **CSS personalitzat afegit** a totes les pàgines `manage_*.php`
- **Capçaleres de taula** amb estils consistents
- **Integració completa** amb el disseny de La Vinagreta

---

## 🔐 Sistema de Login i Dashboard

### Fitxers Modificats:
- `login.php` - Pàgina de login personalitzada
- `dashboard.php` - Dashboard personalitzat (NOU)
- `aixada_main.php` - Pàgina principal modificada
- `php/ctrl/Login.php` - Controlador de login amb debug

### Canvis Principals:

#### 1. Login Personalitzat (`login.php`)
```php
// Capçalera personalitzada afegida
<header class="login-header">
    <div class="logo">
        <a href="https://lavinagreta.org">
            <img src="https://lavinagreta.pangea.org/aixada/local_config/custom_img/logo-vinagreta.png" alt="La Vinagreta">
        </a>
    </div>
    <nav class="nav-links">
        <!-- Menú de navegació personalitzat -->
    </nav>
</header>

// Camp ocult per operació de login (necessari per Login.php original)
<input type="hidden" name="oper" value="login">

// Redirecció canviada de index.php a dashboard.php
success: function() {
    top.location.href = 'dashboard.php';
}

// Incidents desactivats per evitar 401 Unauthorized
// $('#newsWrap').xml2html('init',{...}); // COMENTAT
```

#### 2. Dashboard Personalitzat (`dashboard.php`)
- **NOU FITXER** - No existeix a l'Aixada original
- Mostra informació personalitzada de l'usuari
- Enllaços a recursos de La Vinagreta
- Disseny en 3 columnes amb seccions organitzades

#### 3. Pàgina Principal (`aixada_main.php`)
```php
// Redirecció al dashboard si l'usuari està logat
if (is_created_session()) {
    header('Location: dashboard.php');
    exit;
}

// Salutació eliminada (ja està al dashboard)
// Warning desactivat
// if (rowCount == 0){
//     $.showMsg({...}); // COMENTAT
// }
```

#### 4. Index Principal (`index.php`)
```php
// Redirigir al dashboard personalitzat si l'usuari està logat
if (is_created_session()) {
    header('Location: dashboard.php');
    exit;
}

// Obtenir el nom de la sòcia per a la salutació
$memberName = '';
try {
    $db = DBWrap::get_instance();
    // ... codi per obtenir nom de membre
} catch (Exception $e) {
    // ... gestió d'errors
}
```

---

## 🎨 Estils Personalitzats

### Fitxers Modificats:
- `css/vinagreta-custom.css` - Estils personalitzats (NOU)
- `php/inc/header.inc.base.php` - Funció per carregar CSS
- `php/inc/menu.inc.php` - Càrrega automàtica de CSS

### Canvis Principals:

#### 1. CSS Personalitzat (`css/vinagreta-custom.css`)
```css
/* Variables de color de La Vinagreta */
:root {
    --vinagreta-dark-blue-gray: #4a5f6f;
    --vinagreta-accent-green: #6b9e5a;
    --vinagreta-primary: var(--vinagreta-dark-blue-gray);
    /* ... més variables */
}

/* Estils del menú */
#menuBgBar {
    background: var(--vinagreta-primary) !important;
}

.menuTop {
    color: var(--vinagreta-primary) !important;
}

.menuTop:hover {
    background: var(--vinagreta-accent-green) !important;
    color: white !important;
}

/* Estils del login */
#logonWrap {
    /* Centrat i estilitzat */
}

/* Estils del dashboard */
.dashboard-welcome {
    /* Disseny personalitzat */
}
```

#### 2. Càrrega Automàtica de CSS
```php
// php/inc/header.inc.base.php
function aixada_custom_css() {
    return '<link rel="stylesheet" type="text/css" media="screen" href="css/vinagreta-custom.css?v=4.4"/>' . "\n";
}

// php/inc/menu.inc.php
<?php echo aixada_custom_css(); ?>
```

#### 3. Verificació Completa d'Estils
- **34 pàgines verificades** - Totes carreguen correctament els estils personalitzats
- **Càrrega automàtica** - Via `header.inc.php` → `menu.inc.php` → `aixada_custom_css()`
- **Cobertura completa** - manage_*, report_*, shop_*, torn.php, validate.php, templates
- **Consistència visual** - Colors, menús, botons i elements UI uniformes

---

## 🗄️ Configuració de Base de Dades

### Fitxers Modificats:
- `local_config/config.php` - Credencials de BD

### Canvis Principals:
```php
// Credencials específiques de Pangea
public $db_host = 'localhost';
public $db_name = 'lavinagreta-aixada';
public $db_user = 'lavinagreta-aixada';
public $db_password = 'Mvc3%h&kdfg924';

// Configuració de La Vinagreta
public $coop_name = 'La Vinagreta';
public $default_language = 'ca-va';

// Rols personalitzats
public $menu_config = array(
    'Consumer' => array(
        'navWizard' => 'enable',    // Canviat de 'disable'
        'navManage' => 'enable',    // Canviat de 'disable'
        'navIncidents' => 'disable' // Canviat de 'enable'
    ),
    'Accounts Commission' => array(
        // NOU ROL afegit
    )
);
```

---

## 🧭 Menú i Navegació

### Fitxers Modificats:
- `php/inc/menu.inc.php` - Menú principal

### Canvis Principals:

#### 1. Botó "Torna al tauler" al top right
```php
// Afegit després de la selecció d'idioma
echo " | ";
echo "<a href='dashboard.php'>Torna al tauler</a> | ";
```

#### 2. Rols i Permisos Modificats
- **Consumer**: Accés a "Repartiment" i "Gestiona"
- **Accounts Commission**: Nou rol amb accés complet a diners
- **Incidents**: Desactivat per Consumers

---

## 🖼️ Imatges i Recursos

### Fitxers Afegits:
- `local_config/custom_img/logo-vinagreta.png`
- `local_config/custom_img/logo-aixada.png`

### URLs de les Imatges:
```html
<!-- Login i Dashboard -->
<img src="https://lavinagreta.pangea.org/aixada/local_config/custom_img/logo-vinagreta.png">

<!-- Dashboard Aixada -->
<img src="https://lavinagreta.pangea.org/aixada/local_config/custom_img/logo-aixada.png">
```

---

## 🔧 Funcionalitats Personalitzades

### 1. Integració amb WordPress
- Enllaços a `lavinagreta.org`
- Llistats de famílies i contactes
- Recursos externs (Google Sheets, etc.)

---

## 📝 Procediment per Actualitzacions

### Abans d'Actualitzar:
1. **Fer backup** de tots els fitxers modificats
2. **Documentar** qualsevol canvi nou
3. **Provar** en entorn de desenvolupament

### Després d'Actualitzar:
1. **Restaurar** fitxers personalitzats:
   - `login.php`
   - `dashboard.php`
   - `aixada_main.php`
   - `css/vinagreta-custom.css`
   - `php/inc/menu.inc.php`
   - `php/inc/header.inc.base.php`
   - `local_config/config.php`

2. **Verificar** que les funcionalitats funcionen
3. **Actualitzar** aquest README si cal

### Fitxers Crítics (NO SOBRESCRIURE):
- `dashboard.php` - NOU, no existeix a l'original
- `css/vinagreta-custom.css` - NOU, no existeix a l'original
- `local_config/config.php` - Credencials específiques

---

## 🚨 Notes Importants

### Seguretat:
- Les credencials de BD estan hardcodejades (necessari per Pangea)
- Els logs de debug contenen informació sensible

### Compatibilitat:
- Testat amb Aixada original
- Compatible amb actualitzacions menors
- Pot requerir ajustos en actualitzacions majors

### Manteniment:
- Revisar aquest README després de cada canvi
- Mantenir versions dels fitxers personalitzats
- Provar sempre en entorn de desenvolupament

---

## 📞 Contacte

Per qualsevol dubte sobre aquestes personalitzacions, consultar aquest document o el codi comentat als fitxers modificats.

**Última actualització**: 15 d'octubre de 2025
**Versió Aixada**: Original + personalitzacions La Vinagreta
