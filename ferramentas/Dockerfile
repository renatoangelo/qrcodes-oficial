# Servidor local de testes: PHP 8.4 + Apache, com a extensão GD (gera o PNG dos QR codes), como no servidor real.
FROM php:8.4-apache
RUN apt-get update && apt-get install -y --no-install-recommends libpng-dev \
 && docker-php-ext-install gd \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*
