# PHP va Apache o'rnatilgan tayyor imijdan foydalanamiz
FROM php:8.2-apache

# Ishchi katalogni belgilaymiz
WORKDIR /var/www/html

# Barcha fayllarni konteyner ichiga nusxalaymiz
COPY . .

# JSON fayllar va papkalarga to'liq ruxsat beramiz (777)
# Bu botga ma'lumot yozish imkonini beradi
RUN chmod -R 777 /var/www/html

# Portni ochamiz
EXPOSE 80

# Apache serverini ishga tushiramiz
CMD ["apache2-foreground"]
