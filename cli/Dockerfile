
FROM ubuntu:latest

MAINTAINER Robert Aboukhalil <raboukha@cshl.edu>

# -- Get Ubuntu latest package list --------------------------------------------
RUN apt-get update
RUN apt-get -y upgrade

# -- Install Apache + PHP + MySQL ----------------------------------------------
# See [http://bit.ly/11zWrWI] for details
RUN DEBIAN_FRONTEND=noninteractive apt-get -y install apache2 libapache2-mod-php5 php5-mysql php5-gd php-pear php-apc php5-curl curl lynx-cur

# Enable Apache mods
RUN a2enmod php5
RUN a2enmod rewrite

# Update PHP.ini file: enable <? ?> tags and quieten logging.
RUN sed -i "s/short_open_tag = Off/short_open_tag = On/" /etc/php5/apache2/php.ini
RUN sed -i "s/error_reporting = .*$/error_reporting = E_ERROR | E_WARNING | E_PARSE/" /etc/php5/apache2/php.ini

# Ginkgo needs to be able to upload large files
RUN sed -i "s/upload_max_filesize = .*$/upload_max_filesize = 5G/" /etc/php5/apache2/php.ini
RUN sed -i "s/max_file_uploads = .*$/max_file_uploads = 20/" /etc/php5/apache2/php.ini

# Manually set up the Apache environment variables
ENV APACHE_RUN_USER ginkgo
ENV APACHE_RUN_GROUP ginkgo
ENV APACHE_LOG_DIR /var/log/apache2
ENV APACHE_LOCK_DIR /var/lock/apache2
ENV APACHE_PID_FILE /var/run/apache2.pid

EXPOSE 80

# Update the default Apache site with the config we created.
ADD apache-config.conf /etc/apache2/sites-enabled/000-default.conf

# By default, simply start Apache.
CMD /usr/sbin/apache2ctl -D FOREGROUND

# -- Install R and packages ----------------------------------------------------
RUN apt-get -y install r-base-core

# -- Retrieve latest Ginkgo code from Github -----------------------------------
RUN curl -L -o /var/www/ginkgo.tar.gz http://github.com/robertaboukhalil/ginkgo/tarball/master/
RUN tar xpvf /var/www/ginkgo.tar.gz
RUN mv /var/www/$(tar --exclude="*/*" -tf /var/www/ginkgo.tar.gz) /var/www/ginkgo/
RUN rm /var/www/ginkgo.tar.gz
