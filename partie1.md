# Partie 1 : Conteneurisation avec Docker

***

## Étape 1 : Préparation de l’application

1. **Dockerfile PHP**

- Création d'un fichier `Dockerfile` dans `php/` avec :

```Dockerfile
FROM php:8.2-apache

ARG APP_VERSION=1.0.0
LABEL org.opencontainers.image.version=$APP_VERSION
LABEL org.opencontainers.image.title="Renforcement D-veloppement Web - App"

# Install system packages and PHP extensions
RUN apt-get update \
 && apt-get install -y --no-install-recommends libzip-dev unzip git zlib1g-dev libpng-dev libjpeg-dev libonig-dev \
 && docker-php-ext-install mysqli pdo_mysql zip gd \
 && a2enmod rewrite \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy application code
COPY www/ /var/www/html/

# Ensure uploads directory exists and is writable
RUN mkdir -p /var/www/html/uploads \
 && chown -R www-data:www-data /var/www/html/uploads \
 && chmod -R 775 /var/www/html/uploads

EXPOSE 80

CMD ["apache2-foreground"]

```

2. **Docker Compose qui compose l'image PHP et une image MySQL officielle**

```yaml
version: "3.8"

services:
  app:
    build:
      context: ./php
      args:
        APP_VERSION: ${APP_VERSION:-1.0.0}
    image: ${DOCKERHUB_REPO:-periicles/gestion-produits-php}:${APP_VERSION}
    ports:
      - "${APP_PORT:-${PHP_PORT:-8080}}:80"
    volumes:
      - ./php/www:/var/www/html:cached
      - uploads:/var/www/html/uploads
    environment:
      MYSQL_HOST: db
      MYSQL_DATABASE: ${MYSQL_DATABASE}
      MYSQL_USER: ${MYSQL_USER}
      MYSQL_PASSWORD: ${MYSQL_PASSWORD}
      APP_VERSION: ${APP_VERSION}
    depends_on:
      - db
    restart: unless-stopped

  db:
    image: mysql:8.0
    ports:
      - "${MYSQL_PORT:-${DB_PORT:-3306}}:3306"
    environment:
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
      MYSQL_DATABASE: ${MYSQL_DATABASE}
      MYSQL_USER: ${MYSQL_USER}
      MYSQL_PASSWORD: ${MYSQL_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql
      - "./database:/docker-entrypoint-initdb.d:${DB_VOLUME_OPTS:-ro}"
    restart: unless-stopped

volumes:
  mysql_data:
  uploads:
```

***

## Étape 2 : Construction des images Docker

1. **Construction de l’image PHP en local avec Dockerfile** :

```bash
docker build -t periicles/gestion-produits-php:latest ./php

[+] Building 0.9s (10/10) FINISHED                                                                                     docker:default
 => [internal] load build definition from Dockerfile                                                                             0.0s
 => => transferring dockerfile: 853B                                                                                             0.0s
 => [internal] load metadata for docker.io/library/php:8.2-apache                                                                0.6s
 => [internal] load .dockerignore                                                                                                0.0s
 => => transferring context: 167B                                                                                                0.0s
 => [1/5] FROM docker.io/library/php:8.2-apache@sha256:b3876890595b471c1eeebe0b073a81070f18100045c92761cb926eb80aca839c          0.0s
 => [internal] load build context                                                                                                0.1s
 => => transferring context: 3.08kB                                                                                              0.0s
 => CACHED [2/5] RUN apt-get update  && apt-get install -y --no-install-recommends libzip-dev unzip git zlib1g-dev libpng-dev l  0.0s
 => CACHED [3/5] WORKDIR /var/www/html                                                                                           0.0s
 => CACHED [4/5] COPY www/ /var/www/html/                                                                                        0.0s
 => CACHED [5/5] RUN mkdir -p /var/www/html/uploads  && chown -R www-data:www-data /var/www/html/uploads  && chmod -R 775 /var/  0.0s
 => exporting to image                                                                                                           0.0s
 => => exporting layers                                                                                                          0.0s
 => => writing image sha256:a411d87653362d6f57a60d0df851b660dabf1bda66c8c0c03a2f730b785d9559                                     0.0s
 => => naming to docker.io/periicles/gestion-produits-php:latest                                                                 0.0s
```

2. **Test avec Docker Compose** :

```bash
docker compose up --build -d

WARN[0000] /home/periicles/Efrei/docker-efrei/2025-2026 (1)/2025-2026/évaluation/Renforcement-D-veloppement-Web/docker-compose.yml: the attribute `version` is obsolete, it will be ignored, please remove it to avoid potential confusion 
[+] Building 0.6s (12/12) FINISHED
 => [internal] load local bake definitions                                                                                       0.0s
 => => reading from stdin 751B                                                                                                   0.0s
 => [internal] load build definition from Dockerfile                                                                             0.0s
 => => transferring dockerfile: 853B                                                                                             0.0s
 => [internal] load metadata for docker.io/library/php:8.2-apache                                                                0.2s
 => [internal] load .dockerignore                                                                                                0.1s
 => => transferring context: 167B                                                                                                0.0s
 => [1/5] FROM docker.io/library/php:8.2-apache@sha256:b3876890595b471c1eeebe0b073a81070f18100045c92761cb926eb80aca839c          0.0s
 => [internal] load build context                                                                                                0.0s
 => => transferring context: 3.08kB                                                                                              0.0s
 => CACHED [2/5] RUN apt-get update  && apt-get install -y --no-install-recommends libzip-dev unzip git zlib1g-dev libpng-dev l  0.0s
 => CACHED [3/5] WORKDIR /var/www/html                                                                                           0.0s
 => CACHED [4/5] COPY www/ /var/www/html/                                                                                        0.0s
 => CACHED [5/5] RUN mkdir -p /var/www/html/uploads  && chown -R www-data:www-data /var/www/html/uploads  && chmod -R 775 /var/  0.0s
 => exporting to image                                                                                                           0.0s
 => => exporting layers                                                                                                          0.0s
 => => writing image sha256:31c693f072aec7cacba61d5e440ad98ff30baa9bcfd77d700041eb58fe8d340a                                     0.0s
 => => naming to docker.io/periicles/gestion-produits-php:1.0                                                                    0.0s
 => resolving provenance for metadata file                                                                                       0.0s
[+] Running 6/6
 ✔ periicles/gestion-produits-php:1.0                Built                                                                       0.0s 
 ✔ Network renforcement-d-veloppement-web_default    Created                                                                     0.2s 
 ✔ Volume renforcement-d-veloppement-web_mysql_data  Created                                                                     0.0s 
 ✔ Volume renforcement-d-veloppement-web_uploads     Created                                                                     0.0s 
 ✔ Container renforcement-d-veloppement-web-db-1     Started                                                                     0.7s 
 ✔ Container renforcement-d-veloppement-web-app-1    Started                                                                     0.7s 
```

3. **Vérification que les containers tournent** :

```bash
docker ps

CONTAINER ID   IMAGE                                 COMMAND                  CREATED          STATUS          PORTS                                                                                                                                  NAMES
a175d479277e   periicles/gestion-produits-php:1.0    "docker-php-entrypoi…"   27 seconds ago   Up 26 seconds   0.0.0.0:8080->80/tcp, [::]:8080->80/tcp                                                                                                renforcement-d-veloppement-web-app-1
a3ca3cf3579f   mysql:8.0                             "docker-entrypoint.s…"   27 seconds ago   Up 26 seconds   0.0.0.0:3306->3306/tcp, [::]:3306->3306/tcp, 33060/tcp                                                                                 renforcement-d-veloppement-web-db-1
c767916d6662   gcr.io/k8s-minikube/kicbase:v0.0.48   "/usr/local/bin/entr…"   8 days ago       Up 2 hours      127.0.0.1:32768->22/tcp, 127.0.0.1:32769->2376/tcp, 127.0.0.1:32770->5000/tcp, 127.0.0.1:32771->8443/tcp, 127.0.0.1:32772->32443/tcp   minikube
```

4. **Test de l'application via <http://localhost:8080>**

***

## Étape 3 : Publication de l’image Docker

1. **Connection à Docker Hub** :

```bash
docker login

Authenticating with existing credentials... [Username: periicles]

i Info → To login with a different account, run 'docker logout' followed by 'docker login'


Login Succeeded```

2. **Push de l’image vers Docker Hub** :

```bash
docker push periicles/gestion-produits-php:latest

The push refers to repository [docker.io/periicles/gestion-produits-php]
bc776db256a1: Layer already exists 
ba40ed703099: Layer already exists 
5f70bf18a086: Layer already exists 
c870dc83402c: Layer already exists 
bfd2b1174474: Layer already exists 
091054d8555c: Layer already exists 
d903e4e7b83a: Layer already exists 
9bf6221005bb: Layer already exists 
3f39ea6bba90: Layer already exists 
55337f1a50c2: Layer already exists 
06e6934f4310: Layer already exists 
076a217684ef: Layer already exists 
61ce035523b2: Layer already exists 
937b03306349: Layer already exists 
8f19c55225a3: Layer already exists 
74f9ad306fab: Layer already exists 
f0c24588d548: Layer already exists 
1d46119d249f: Layer already exists 
latest: digest: sha256:0e3e73f56df2675b3e724041d3fc162dba3dc095e9f16e757097cc73e366f6f8 size: 4286```

***

## Conclusion

- Lien de l'image sur le Docker Hub : <https://hub.docker.com/repository/docker/periicles/gestion-produits-php/general>
- Accès à l'application via <http://localhost:8080>
