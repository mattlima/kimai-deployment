FROM debian:12-slim

# Install mysql-client (default-mysql-client) and rclone
RUN apt-get update && \
    apt-get install -y \
    default-mysql-client \
    rclone \
    curl \
    ca-certificates && \
    rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Copy backup script
COPY backup.sh .
RUN chmod +x backup.sh

CMD ["/app/backup.sh"]