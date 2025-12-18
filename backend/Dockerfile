# ============================================
# Dockerfile Backend Symfony 7 - MY-ANKODE
# Image PHP 8.3 avec extensions nécessaires
# ============================================

FROM php:8.3-fpm-alpine

# Installer les dépendances système
RUN apk add --no-cache \
    git \
    unzip \
    curl \
    bash \
    postgresql-dev \
    icu-dev \
    libzip-dev \
    oniguruma-dev

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    intl \
    opcache \
    zip \
    mbstring

# Installer l'extension MongoDB
RUN apk add --no-cache $PHPIZE_DEPS && \
    pecl install mongodb && \
    docker-php-ext-enable mongodb

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Installer Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash && \
    mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers de l'application
COPY . .

# Installer les dépendances Composer
RUN composer install --no-interaction --optimize-autoloader

# Exposer le port 8000 pour Symfony
EXPOSE 8000

# Commande de démarrage (sera override par docker-compose)
CMD ["symfony", "server:start", "--no-tls", "--port=8000"]
