FROM php:8.5-fpm-bookworm

ARG DEBIAN_FRONTEND=noninteractive
ARG TARGETARCH
ARG WKHTMLTOPDF_VERSION=0.12.6.1-3
ARG WKHTMLTOPDF_URL

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        fontconfig \
        libfontconfig1 \
        libfreetype6-dev \
        libjpeg62-turbo \
        libjpeg62-turbo-dev \
        libpng-dev \
        libx11-6 \
        libxrender1 \
        libxtst6 \
        libzip-dev \
        wget \
        xfonts-75dpi \
        xfonts-base \
        zip; \
    # opcache is often already compiled into official PHP 8.x images; enable only if present as source ext
    if [ -d /usr/src/php/ext/opcache ]; then docker-php-ext-install opcache; fi; \
    docker-php-ext-install bcmath pdo pdo_mysql zip; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install gd; \
    docker-php-ext-enable opcache 2>/dev/null || true; \
    rm -rf /var/lib/apt/lists/*

RUN set -eux; \
    if [ -z "${WKHTMLTOPDF_URL:-}" ]; then \
        case "${TARGETARCH}" in \
            amd64) \
                WKHTMLTOPDF_URL="https://github.com/wkhtmltopdf/packaging/releases/download/${WKHTMLTOPDF_VERSION}/wkhtmltox_${WKHTMLTOPDF_VERSION}.bookworm_amd64.deb" \
                ;; \
            arm64) \
                WKHTMLTOPDF_URL="https://github.com/wkhtmltopdf/packaging/releases/download/${WKHTMLTOPDF_VERSION}/wkhtmltox_${WKHTMLTOPDF_VERSION}.bookworm_arm64.deb" \
                ;; \
            *) \
                echo "Unsupported TARGETARCH: ${TARGETARCH}. Pass WKHTMLTOPDF_URL build-arg." >&2; \
                exit 1 \
                ;; \
        esac; \
    fi; \
    wget -O /tmp/wkhtmltox.deb "${WKHTMLTOPDF_URL}"; \
    apt-get update; \
    apt-get install -y --no-install-recommends /tmp/wkhtmltox.deb || apt-get install -y -f /tmp/wkhtmltox.deb; \
    rm -f /tmp/wkhtmltox.deb; \
    rm -rf /var/lib/apt/lists/*; \
    ln -sf /usr/local/bin/wkhtmltopdf /usr/bin/wkhtmltopdf || true

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY --chown=www-data:www-data ./html /var/www/html
COPY --chown=www-data:www-data ./config /var/config

RUN chmod -R 777 /var/www/html/bootstrap/cache \
    && chmod -R 777 /var/www/html/storage

ARG LOCAL=false
RUN if [ "$LOCAL" = "false" ]; then \
    cp /var/config/php/www.conf /usr/local/etc/php-fpm.d/www.conf; \
    cp /var/config/php/opcache.ini /usr/local/etc/php/conf.d/99-opcache.ini; \
fi

COPY ./config/php/custom.ini /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html

EXPOSE 9000

VOLUME ["/var/www/html"]

CMD ["php-fpm"]
