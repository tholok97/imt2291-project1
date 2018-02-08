FROM ubuntu:16.04
MAINTAINER Thomas Løkkeborg "thomahl@stud.ntnu.no"

RUN apt-get -y update \
	&& apt-get -y install apache2 libapache2-mod-php php-mysql \
	&& apt-get -y install vim

COPY config/php.ini /etc/php/7.0/apache2/php.ini
COPY config/apache2.conf /etc/apache2/

RUN a2enmod rewrite

EXPOSE 80

CMD /usr/sbin/apache2ctl -D FOREGROUND
