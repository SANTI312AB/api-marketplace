# docker/Dockerfile
FROM php:8.4-fpm

WORKDIR /var/www/html
ENV DEBIAN_FRONTEND=noninteractive

# 1) Herramientas de compilación + headers
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
    ca-certificates \
    git \
    unzip \
    curl \
    gnupg \
    build-essential \
    autoconf \
    pkg-config \
    make \
    gcc \
    g++ \
    zlib1g-dev \
    libssl-dev \
    libffi-dev \
    libsasl2-dev \
    libzip-dev \
    libonig-dev \
    libicu-dev \
    libxml2-dev \
    libpng-dev \
    libwebp-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libbz2-dev \
    libldap2-dev \
    libgmp-dev \
    libxslt1-dev \
    libtidy-dev \
    libkrb5-dev \
    libsqlite3-dev \
    libcurl4-openssl-dev \
    libmagic-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2) Intentar instalar variantes libc-client (no fatal)
RUN apt-get update \
    && ( apt-get install -y --no-install-recommends libc-client2007e-dev || true ) \
    && ( apt-get install -y --no-install-recommends libc-client-dev || true ) \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 3) Configurar gd y ldap (previo a build)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-configure ldap --with-ldap=/usr

# 4) Compilar extensiones compatibles una-a-una con logs detallados
RUN set -eux; \
    mkdir -p /tmp/php-ext-logs; \
    EXT_LIST="bz2 curl fileinfo gettext gmp intl mbstring exif mysqli opcache pdo_mysql soap sockets sysvshm tidy xsl zip bcmath pcntl gd ldap"; \
    for ext in $EXT_LIST; do \
        LOG="/tmp/php-ext-logs/${ext}.log"; \
        echo; echo "======================================================"; \
        echo " START BUILD: $ext"; \
        echo " log -> $LOG"; \
        echo "======================================================"; \
        rm -f "$LOG"; \
        if docker-php-ext-install -j$(nproc) "$ext" > "$LOG" 2>&1; then \
            echo " SUCCESS: $ext"; \
        else \
            echo " !! BUILD FAILED for extension: $ext"; \
            echo " ----- BEGIN $ext LOG -----"; \
            sed -n '1,400p' "$LOG" || true; \
            echo " ----- END $ext LOG -----"; \
            echo " ----- TAIL 200 lines of $ext LOG -----"; \
            tail -n 200 "$LOG" || true; \
            false; \
        fi; \
    done
# 5) Compilar IMAP solo si los headers de libc-client están presentes
RUN if [ -d /usr/include/c-client ] || ls /usr/include/c-client/*.h >/dev/null 2>&1 ; then \
        echo "libc-client headers found — building imap"; \
        docker-php-ext-configure imap --with-kerberos --with-imap-ssl; \
        docker-php-ext-install -j$(nproc) imap || (echo "!! BUILD FAILED for extension: imap" && exit 1); \
    else \
        echo "libc-client headers not found — skipping imap build"; \
    fi

# OPcache recomendado
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.validate_timestamps=1'; \
        echo 'opcache.revalidate_freq=0'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

# Composer y Symfony CLI
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony || true

# php.ini personalizado
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

# permisos, puerto y comando
RUN chown -R www-data:www-data /var/www/html
EXPOSE 9000
CMD ["php-fpm"]