FROM kimai/kimai2:apache

# Copy custom invoice templates
# The destination path is inside the container
COPY invoices/ /opt/kimai/var/invoices/

# Fix permissions so the web server can read them
RUN chown -R www-data:www-data /opt/kimai/var/invoices
