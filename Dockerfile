# Utilise une image officielle PHP avec Apache
FROM php:8.2-cli

# Installe les extensions PHP nécessaires (à adapter selon ton projet)
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    libicu-dev \
    libpq-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    && docker-php-ext-install intl pdo pdo_mysql zip opcache

# Installe Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Installe la CLI Symfony (globale)
RUN curl -sS https://get.symfony.com/cli/installer | bash && \
    mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Crée le dossier du projet
WORKDIR /var/www/html

# Installe les dépendances PHP automatiquement si elles ne sont pas présentes
CMD ["composer", "install", "--no-interaction"]
