FROM php:7.4-apache
ARG TIMEZONE

# Configure Apache2
ENV APACHE_RUN_USER=www-data
ENV APACHE_RUN_GROUP=www-data
ENV APACHE_LOG_DIR=/var/log/apache2
ENV APACHE_PID_FILE=/var/run/apache2.pid
ENV APACHE_RUN_DIR=/var/run/apache2
ENV APACHE_LOCK_DIR=/var/lock/apache2

# Configure composer
ENV COMPOSER_HOME=/var/www/.composer/

# Settings for installer
ENV DEBIAN_FRONTEND=noninteractive

WORKDIR /var/www/html


# Configure Timezone
RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo ${TIMEZONE} > /etc/timezone  \
 && printf '[PHP]\ndate.timezone = "%s"\n' ${TIMEZONE} > /usr/local/etc/php/conf.d/php.ini


# Copy Composer binary from official Composer Docker image, @see https://docs.docker.com/develop/develop-images/multistage-build/
COPY --from=composer:2.3.5 /usr/bin/composer /usr/local/bin/composer

# Easier perm handling
RUN usermod -u 1000 www-data


# Enable rewrite module
RUN a2enmod rewrite

RUN echo "memory_limit=2G" > /usr/local/etc/php/conf.d/memory-limit-php.ini

RUN apt-get update                                   \
 && apt-get install -y                               \
    rsync                                            \
    openssl                                          \
    unzip                                            \
    git                                              \
    less                                             \
    cron                                             \
    nano                                             \
    libfreetype6-dev                                 \
    libjpeg-dev                                      \
    libwebp-dev                                      \
    libpng-dev                                       \
    libxpm-dev                                       \
    libicu-dev                                       \
    libxml2-dev                                      \
    libxslt-dev                                      \
    libmcrypt-dev                                    \
    libzip-dev                                       \
    gnupg


RUN curl -fsSL https://deb.nodesource.com/setup_lts.x | bash -
RUN apt-get install -y nodejs                        \
    && npm install -g grunt-cli

# Install tools for compiling and linking of MageSuite
RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
RUN echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list
RUN apt-get update
RUN apt-get install yarn

RUN apt-get install automake -y
RUN apt-get install autoconf -y

# Fixing broken default imagemin install for later `yarn build` (would fail otherwise)
RUN npm uninstall gulp-imagemin --save
RUN npm install gulp-imagemin --save

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN docker-php-ext-configure gd                      \
      --with-freetype              \
      --with-jpeg                  \
      --with-webp                  \
      --with-xpm                   \
 && docker-php-ext-install bcmath                    \
                           gd                        \
                           intl                      \
                           pdo_mysql                 \
                           soap                      \
                           xsl                       \
                           zip                       \
                           opcache                   \
                           sockets                   \
 && docker-php-ext-enable opcache

# Ports
EXPOSE 80 443

CMD ["/usr/sbin/apache2", "-DFOREGROUND"]
