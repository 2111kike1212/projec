FROM php:8.2-apache
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . /var/www/html/
RUN apt-get update && apt-get install -y unzip && rm -rf /var/lib/apt/lists/*
RUN if [ -f composer.json ]; then composer install --no-dev --no-interaction --optimize-autoloader; fi
RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
