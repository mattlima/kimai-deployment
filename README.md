# Kimai Deployment Project

This project automates the deployment of the Kimai application using Docker and Caddy. It provides a customizable environment for running and testing the Kimai time-tracking application.

## Project Structure

- **caddy_config/Caddyfile**: Configuration for the Caddy web server, handling incoming requests, domain settings, and SSL configurations.
  
- **kimai_config/local.yaml**: Customization settings for the Kimai application, allowing deeper modifications of its behavior and features.

- **scripts/**: Contains various scripts for deployment and setup:
  - **deploy.sh**: Automates the deployment process, including building and running Docker containers.
  - **restore.sh**: Restores the application state from backups or resets the database.
  - **setup_dev.sh**: Sets up a local development environment for testing customizations.

- **.env**: Environment variables for Docker containers, including database credentials, timezone settings, and domain configurations.

- **docker-compose.yml**: Defines services, networks, and volumes for the Docker application, specifying how to run Caddy, MySQL, and Kimai in containers.

## Setup Instructions

1. **Clone the Repository**: Clone this repository to your local machine.
   
2. **Configure Environment Variables**: Update the `.env` file with your specific settings, including database credentials and domain information.

3. **Caddy Configuration**: Modify the `caddy_config/Caddyfile` to set up your domain and SSL settings as needed.

4. **Kimai Customization**: Edit the `kimai_config/local.yaml` file to customize the Kimai application according to your requirements.

5. **Run the Application**: Use the `deploy.sh` script to build and run the application:
   ```bash
   ./scripts/deploy.sh
   ```

6. **Local Development**: For local development, run the `setup_dev.sh` script:
   ```bash
   ./scripts/setup_dev.sh
   ```

## Usage Guidelines

- Ensure Docker and Docker Compose are installed on your machine.
- Use the `restore.sh` script to revert to a previous application state if necessary.
- Refer to the Kimai documentation for additional customization options and features.

## License

This project is licensed under the MIT License. See the LICENSE file for more details.