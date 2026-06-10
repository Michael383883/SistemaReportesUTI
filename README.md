# SistemaReportesUTI

Sistema de reportes con Laravel 12 + Vue 3 + SQL Server 2022, dockerizado.

## Requisitos del servidor
- Ubuntu Server 22.04+
- Docker y Docker Compose instalados

## Despliegue desde cero

### 1. Clonar el repositorio
```bash
git clone https://github.com/Michael383883/SistemaReportesUTI.git
cd SistemaReportesUTI
```

### 2. Configurar el .env del backend
```bash
cp backend/.env.example backend/.env
nano backend/.env
```
Llenar los campos:
- `APP_KEY` → se genera en el paso 4
- `DB_PASSWORD` → UTIPassword123! (o la que elijas, debe coincidir con SA_PASSWORD en docker-compose.yml)
- `FRONTEND_URL` → http://IP_DE_TU_SERVIDOR

### 3. Levantar los contenedores
```bash
docker compose up -d --build
```
> La primera vez tarda varios minutos porque descarga SQL Server y compila PHP con los drivers ODBC.

### 4. Generar la clave de Laravel
```bash
docker exec laravel-app php artisan key:generate
```

### 5. Crear la base de datos y ejecutar migraciones
```bash
# Crear la base de datos
docker exec laravel-app php -r "
\$pdo = new PDO('sqlsrv:Server=sqlserver-db,1433;TrustServerCertificate=yes;Encrypt=no', 'SA', 'UTIPassword123!');
\$pdo->exec('CREATE DATABASE SistemaReportesUTI');
echo 'Base de datos creada.' . PHP_EOL;
"

# Ejecutar migraciones
docker exec laravel-app php artisan migrate --force

# Ejecutar seeders (datos iniciales)
docker exec laravel-app php artisan db:seed --force
```

### 6. Verificar que todo funciona
```bash
# Ver contenedores corriendo
docker ps

# Probar el backend
curl -i http://localhost/sanctum/csrf-cookie
```

## Comandos útiles

```bash
# Ver logs de un contenedor
docker logs laravel-app
docker logs vue-app
docker logs nginx-proxy
docker logs sqlserver-db

# Reiniciar un servicio
docker compose restart laravel-app

# Detener todo
docker compose down

# Detener y borrar volúmenes (¡borra la BD!)
docker compose down -v
```

## Estructura del proyecto
```
SistemaReportesUTI/
├── backend/            ← Laravel 12 + PHP 8.3
│   ├── Dockerfile
│   ├── .env.example
│   └── ...
├── frontend/           ← Vue 3 + Vite + Tailwind
│   ├── Dockerfile
│   ├── nginx-frontend.conf
│   └── ...
├── nginx/
│   └── default.conf    ← Proxy inverso
├── docker-compose.yml
└── README.md
```

## Puertos
| Servicio | Puerto interno | Expuesto |
|---|---|---|
| nginx-proxy | 80 | ✅ 80 |
| laravel-app | 8000 | ❌ solo interno |
| vue-app | 80 | ❌ solo interno |
| sqlserver-db | 1433 | ✅ 1433 |