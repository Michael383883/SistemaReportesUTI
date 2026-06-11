FROM php:8.3-fpm
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev \
    libxml2-dev libzip-dev gnupg2 apt-transport-https \
    ca-certificates && \
    rm -rf /var/lib/apt/lists/*
# Driver ODBC Microsoft para SQL Server
RUN curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | \
    gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg && \
    echo "deb [arch=amd64 signed-by=/usr/share/keyrings/microsoft-prod.gpg] \
    https://packages.microsoft.com/debian/12/prod bookworm main" \
    > /etc/apt/sources.list.d/mssql-release.list && \
    apt-get update && \
    ACCEPT_EULA=Y apt-get install -y msodbcsql17 unixodbc-dev && \
    rm -rf /var/lib/apt/lists/*
# Extensiones PHP
RUN pecl install sqlsrv pdo_sqlsrv && \
    docker-php-ext-enable sqlsrv pdo_sqlsrv && \
    docker-php-ext-install pdo mbstring exif pcntl bcmath gd zip
# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/backend
COPY . .
RUN DB_CONNECTION=sqlite \
    DB_DATABASE=/tmp/fake.db \
    APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= \
    composer install --no-dev --optimize-autoloader && \
    chown -R www-data:www-data /var/www/backend && \
    chmod -R 775 storage bootstrap/cache
# Fix TLS para SQL Server 2008
COPY openssl-fix.cnf /usr/lib/ssl/openssl-fix.cnf

RUN cp /usr/lib/ssl/openssl.cnf /usr/lib/ssl/openssl.cnf.bak && \
    cat /usr/lib/ssl/openssl-fix.cnf >> /usr/lib/ssl/openssl.cnf
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
EXPOSE 8000
CMD ["/entrypoint.sh"]