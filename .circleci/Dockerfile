# Lock to known-good version (7.2.9-cli)
FROM circleci/php@sha256:5856fc70ea0abe2a92a63ea6ef56460949e13f47b2abd76866fb0d88cbd52744

# Install bcmath, which is required.
RUN sudo docker-php-ext-install bcmath

# Ensure phar building is enabled.
RUN echo phar.readonly = Off | sudo tee -a /usr/local/etc/php/php.ini

CMD ["/bin/sh"]