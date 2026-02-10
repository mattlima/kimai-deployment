# HelloWorld Plugin for Kimai

This is a simple smoke test plugin that adds a new route to the Kimai time tracking application.

## Features

- Adds a `/hello-world` route to the Kimai application
- Displays lorem ipsum text as a smoke test

## Route Information

- **Path**: `/hello-world`
- **Route Name**: `hello_world`
- **Controller**: `KimaiPlugin\HelloWorldBundle\Controller\HelloWorldController::index`

## Files Structure

```
HelloWorldBundle/
├── Controller/
│   └── HelloWorldController.php  # Main controller with route handler
├── Resources/
│   ├── config/
│   │   ├── routes.yaml           # Route configuration (YAML-based routing)
│   │   └── services.yaml         # Service definitions for dependency injection
│   └── views/
│       └── hello.html.twig        # Template for displaying lorem ipsum
├── HelloWorldBundle.php           # Main bundle class implementing PluginInterface
└── composer.json                  # Plugin metadata and requirements
```

## Installation

The plugin is automatically installed when building the Docker image. The Dockerfile copies the plugin to `/opt/kimai/var/plugins/HelloWorldBundle/` in the container.

## Usage

After deploying the application, access the route at:
```
http://your-kimai-domain/hello-world
```

## Requirements

- Kimai 2.0 or higher (version 20000 in Kimai's integer format)
