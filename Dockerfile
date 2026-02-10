FROM kimai/kimai2:apache

# Install mysql client for mysqldump
RUN apt-get update \
	&& apt-get install -y --no-install-recommends default-mysql-client \
	&& rm -rf /var/lib/apt/lists/*

# Copy custom invoice templates
# The destination path is inside the container
COPY invoices/ /opt/kimai/var/invoices/

# Copy HelloWorld plugin
COPY plugins/HelloWorldBundle/ /opt/kimai/var/plugins/HelloWorldBundle/

# Copy DBExport plugin
COPY plugins/DBExportBundle/ /opt/kimai/var/plugins/DBExportBundle/

# Fix permissions so the web server can read them
RUN chown -R www-data:www-data /opt/kimai/var/invoices
RUN chown -R www-data:www-data /opt/kimai/var/plugins
