FROM wordpress:latest

ENV DOCKER_ID=1000
RUN usermod -u ${DOCKER_ID} www-data && groupmod -g ${DOCKER_ID} www-data

# Install MailHog's mhsendmail
RUN curl --location --output /usr/local/bin/mhsendmail https://github.com/mailhog/mhsendmail/releases/download/v0.2.0/mhsendmail_linux_amd64 && \
    chmod a+x /usr/local/bin/mhsendmail
RUN echo 'sendmail_path="/usr/local/bin/mhsendmail --smtp-addr=mailhog:1025 --from=no-reply@docker.dev"' > /usr/local/etc/php/conf.d/mailhog.ini

# Install xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug
RUN { \
        echo 'xdebug.mode=coverage,debug,develop'; \
        echo 'xdebug.start_with_request=trigger'; \
        echo 'xdebug.client_host=host.docker.internal'; \
        echo 'xdebug.idekey=PHPSTORM'; \
	} | tee -a "/usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini"

# set forwarded ip
RUN { \
		echo '<FilesMatch \.php$>'; \
		echo '\tSetHandler application/x-httpd-php'; \
		echo '</FilesMatch>'; \
		echo; \
		echo 'DirectoryIndex disabled'; \
		echo 'DirectoryIndex index.php index.html'; \
		echo; \
		echo '<Directory /var/www/html/>'; \
		echo '\tOptions Indexes FollowSymLinks'; \
		echo '\tAllowOverride All'; \
		echo '\tRequire all granted'; \
		echo '\tOrder allow,deny'; \
		echo '\tAllow from all'; \
		echo '\tSetEnvIf X-Forwarded-Proto https HTTPS=on'; \
		echo '\tSetEnvIf X-Forwarded-Host ^(.+) HTTP_X_FORWARDED_HOST=$1'; \
		echo '\tRequestHeader set Host %{HTTP_X_FORWARDED_HOST}e env=HTTP_X_FORWARDED_HOST'; \
		echo '</Directory>'; \
	} | tee "/etc/apache2/conf-available/docker-php.conf" \
    && a2enconf docker-php && a2enmod headers && a2enmod rewrite

# install composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

COPY --from=wordpress:cli --chown=www-data:www-data /usr/local/bin/wp /usr/local/bin/wp
