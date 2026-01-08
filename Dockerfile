FROM php:8.2-apache
COPY index.php /var/www/html/index.php
RUN chmod 755 /var/www/html/index.php
EXPOSE 80
CMD ["apache2-foreground"]
