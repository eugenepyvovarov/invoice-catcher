FROM php:8.0.11-fpm-buster

RUN apt-get update -y && \
    docker-php-ext-install \
    opcache \
    bcmath \
    pdo \
    pdo_mysql

#zip
RUN apt-get install -y \
        libzip-dev \
        zip \
  && docker-php-ext-install zip

# GD Not needed by actual application, only for mocking files for testing
RUN apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    && \
    docker-php-ext-configure gd --with-freetype --with-jpeg \
    && \
    docker-php-ext-install \
    gd

# wkhtmltopdf
ARG WKHTMLTOPDF_URL=https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6-1/wkhtmltox_0.12.6-1.stretch_arm64.deb

RUN apt-get install -y \
    libxrender1 \
    libfontconfig1 \
    libx11-dev \
    libjpeg62 \
    libxtst6 \
    fontconfig \ 
    libjpeg62-turbo \
    xfonts-base \
    xfonts-75dpi \
    wget \
    && wget $WKHTMLTOPDF_URL -O /usr/local/bin/wkhtmltopdf \
    && chmod +x /usr/local/bin/wkhtmltopdf \
    && dpkg -i --force-depends /usr/local/bin/wkhtmltopdf \
    && apt -f install

# composer
RUN wget https://getcomposer.org/installer -O - -q \
    | php -- --install-dir=/bin --filename=composer --quiet



# Copy needed files into the container
COPY --chown=www-data:www-data ./html /var/www/html
COPY --chown=www-data:www-data ./config /var/config

# Laravel writable
RUN chmod -R 777 /var/www/html/bootstrap/cache
RUN chmod -R 777 /var/www/html/storage

# General php config
COPY ./config/php/custom.ini /usr/local/etc/php/conf.d/custom.ini

# Load some php configuration in production only, false is default - override in DC
ARG LOCAL=false
RUN if [ "$LOCAL" = "false" ];then \
    # Configure php-fpm pool - should to be customized for each application
    cp /var/config/php/www.conf /usr/local/etc/php-fpm.d/www.conf; \
    # Configure opcache
    cp /var/config/php/opcache.ini /usr/local/etc/php/conf.d/99-opcache.ini; \
fi

COPY ./config/php/custom.ini /usr/local/etc/php/conf.d/custom.ini

EXPOSE 9000

VOLUME ["/var/www/html"]

CMD php-fpm
