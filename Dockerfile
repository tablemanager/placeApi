# PHP 이미지 (Apache 포함된 PHP 7.4 사용), 필요에 따라 버전 조정
FROM php:7.4-apache

# Apache 설정 파일 복사
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf

# Workdir 설정
WORKDIR /var/www/html

# 프로젝트 코드 복사
COPY ./api_html /var/www/html/

# Apache 설정 활성화
RUN a2ensite 000-default.conf

# Mod_rewrite 활성화
RUN a2enmod rewrite

# 서버에서 header 설정 적용 위한 mod_headers 활성화
RUN a2enmod headers

# 필요한 PHP 확장 설치 (예: mysqli, curl 등)
RUN docker-php-ext-install mysqli

# 포트 노출
EXPOSE 80