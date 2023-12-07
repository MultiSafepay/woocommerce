FROM wordpress:latest

ENV DOCKER_ID=1000
RUN usermod -u ${DOCKER_ID} www-data && groupmod -g ${DOCKER_ID} www-data
# To create at /var/www/ the folders: '.composer', '.npm' and '.wp-cli' ...
RUN chown -R www-data:www-data /var/www

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

# Set forwarded ip
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

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

COPY --from=wordpress:cli --chown=www-data:www-data /usr/local/bin/wp /usr/local/bin/wp

# Install npm

# Define the Node.js version
ARG NODE_MAJOR=20

# Update the package list and install necessary packages
RUN apt-get update && apt-get install -y ca-certificates curl gnupg lsb-release unzip

# Create a directory for the NodeSource keyring
RUN mkdir -p /usr/share/keyrings

# Add the NodeSource GPG key to the keyring
RUN curl -fsSL https://deb.nodesource.com/gpgkey/nodesource.gpg.key | gpg --dearmor -o /usr/share/keyrings/nodesource.gpg

# Add the NodeSource repository to your sources list
RUN echo "deb [signed-by=/usr/share/keyrings/nodesource.gpg] https://deb.nodesource.com/node_${NODE_MAJOR}.x $(lsb_release -cs) main" | tee /etc/apt/sources.list.d/nodesource.list

# Update the package list with the new repository and install Node.js
RUN apt-get update && apt-get install -y nodejs
