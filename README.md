<img src="http://imgh.us/leap-logo.svg" width="200">

Lightweight Extensible Adaptable PHP framework made by Laurens Verspeek

## Docker
You can [install docker](https://docs.docker.com/engine/installation/) and [docker-compose](https://docs.docker.com/compose/install/) and run leap with:

```
docker-compose up -d
```
Then you can visit leap on localhost:8080

## Folder Structure
- Directory `core` contains the core files for the framework
- Directory `site` will hold all files for your site (pages, javascript, stylesheets, images etc.)
- Directory `plugins` contains all plugins.
- Directory `files` will contain all files created or uploaded by the webserver. It is therefore important that the directory is writable for the webserver. See ... for more help.
- Directory `vendor` contains all external libraries
