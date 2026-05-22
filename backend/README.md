# 🏥 SistemaReportesUTI — Backend

Backend del Sistema de Reportes UTI desarrollado con **Laravel**. Esta guía explica cómo clonar e instalar el proyecto desde cero.

---

## ✅ Requisitos previos

Asegúrate de tener instalado lo siguiente antes de comenzar:

- [PHP >= 8.1](https://www.php.net/downloads)
- [Composer](https://getcomposer.org/download/)
- [MySQL](https://dev.mysql.com/downloads/) o MariaDB
- [XAMPP](https://www.apachefriends.org/) / [Laragon](https://laragon.org/) / [WAMP](https://www.wampserver.com/) (gestor de BD local)
- [Git](https://git-scm.com/)

---

## 📦 Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/Michael383883/SistemaReportesUTI.git
```

### 2. Ingresar al proyecto y cambiar a la rama de desarrollo

```bash
cd SistemaReportesUTI
cd backend
git checkout develop
```

### 3. Instalar dependencias de PHP

```bash
composer install
```

### 4. Crear el archivo de entorno

```bash
cp .env.example .env
```

### 5. Generar la clave de la aplicación

```bash
php artisan key:generate
```

### 6. Configurar la base de datos

Abre el archivo `.env` y edita las siguientes variables con los datos de tu entorno local:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nombre_de_tu_bd
DB_USERNAME=root
DB_PASSWORD=tu_password
```

---

## 🚀 Levantar el proyecto

### 7. Inicia tu servidor de base de datos

Abre **XAMPP**, **Laragon** o el gestor que uses y asegúrate de que **MySQL esté corriendo**.

### 8. Crear las tablas en la base de datos

```bash
php artisan migrate
```

### 9. Poblar la base de datos con datos iniciales

```bash
php artisan db:seed
```

### 10. Iniciar el servidor de desarrollo

```bash
php artisan serve
```

El backend estará disponible en: [http://127.0.0.1:8000](http://127.0.0.1:8000)

---

## 🗂️ Resumen de comandos

```bash
git clone https://github.com/Michael383883/SistemaReportesUTI.git
cd SistemaReportesUTI
cd backend
git checkout develop
composer install
cp .env.example .env
php artisan key:generate
# Configura tu .env con los datos de la BD
php artisan migrate
php artisan db:seed
php artisan serve
```

---

## ⚠️ Notas importantes

- La carpeta `/vendor` y el archivo `.env` **no están incluidos en el repositorio** (están en `.gitignore`). Por eso es necesario ejecutar `composer install` y crear el `.env` manualmente.
- Asegúrate de que el servidor de base de datos esté activo **antes** de correr `php artisan migrate` o `php artisan serve`.
- Si usas una base de datos diferente a MySQL, cambia `DB_CONNECTION` en el `.env` según corresponda (`pgsql`, `sqlite`, etc.).

---

## 📄 Licencia

Este proyecto está licenciado bajo la [MIT License](https://opensource.org/licenses/MIT).