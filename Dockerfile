FROM dunglas/frankenphp:php8.4-alpine

# Instalação de extensões críticas (C-level)
RUN install-php-extensions \
    pcntl \
    pdo_pgsql \
    redis \
    gd \
    intl \
    zip \
    opcache

# Configuração agressiva do OPcache e JIT para execução em memória
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini && \
    echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini && \
    echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini && \
    echo "opcache.jit=tracing" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini && \
    echo "opcache.jit_buffer_size=128M" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

WORKDIR /app

# Injeção do Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Cópia isolada dos ficheiros de dependência para otimizar a cache do Docker
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Cópia do restante código fonte
COPY . .

# Definição de permissões rigorosas para o utilizador do servidor
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

EXPOSE 8000

# Comando de inicialização do Octane (Worker Mode)
CMD ["php", "artisan", "octane:start", "--server=frankenphp", "--host=0.0.0.0", "--port=8000", "--workers=4"]