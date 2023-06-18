# Monwoo Web Starter Symfony PDF Billings (Free)
<img src="https://miguel.monwoo.com/embedded-iframes/prod/embeddable-iframe/favicomatic/favicon-96x96.png" alt="" width="42"/> [Build by Miguel Monwoo, **Open Source Apache-2.0 with Copyright © MONWOO 2023**, all rights reserved.](https://moonkiosk.monwoo.com/en/categorie-produit/produced-solutions/mws_en/)

## Aim
Provide a PHP local tool for pre-fillable self signed PDF billing templates.

## Bonus
Basic controller ok for simple JWT authentification if needed.

## Build

```bash
# tested under php 8.1.2
# Other php versions might work, but it's not tested yet.
php -v

wget https://getcomposer.org/composer.phar
alias composer="php '$PWD/composer.phar'"                      
cd apps/mws-sf-pdf-billings/backend

mkdir config/jwt
openssl genrsa -out config/jwt/private.pem -aes256 4096 # pass : jwt_test
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem # pass : jwt_test

composer install

# bootstrap database
php bin/console doctrine:migrations:migrate

# use php builtin
# (TODO : php router and missing .htaccess checks ok ?)

# Use SYMFONY dev server (not same as php builtin or bin/console)
wget https://get.symfony.com/cli/installer -O - | bash
alias symfony="~/.symfony5/bin/symfony"
symfony server:start

# All is ready for your PDF edition (TODO : dev in progres...)
# TODO : Just fill the form or pre-fill by json POST request...
open http://localhost:8000
```

<div style="page-break-before: always;"></div>

## Going further

```bash
# usefull :
# add users
php bin/console make:user
# list of available make commandes
symfony console list make

# If you change your model,
# you need to generate the associated migrations :
php bin/console make:migration
php bin/console doctrine:migrations:migrate
# other tools for models :
php bin/console make:entity
php bin/console make:entity --regenerate
php bin/console make:entity --help
# add a new controller
php bin/console make:controller PdfBillings

# You can test the JWT feature with curl :
# Add a user (you will have to build up all other security aspects)
curl -X POST -H "Content-Type: application/json" \
    -d '{"username": "test", "password": "123", "email": "test@test.fr"}' \
    http://127.0.0.1:8000/api/register

# Get the JWT token from custom 'login_check' api url
curl -X POST -H "Content-Type: application/json" \
    -d '{"username": "test", "password": "123"}' \
    http://127.0.0.1:8000/api/login_check

```

## Useful Links

Learn more about the power of pdf billings:

- [Décret n° 2023-377 du 16 mai 2023 - PDF signing requirements for big FR buisiness](https://www.legifrance.gouv.fr/jorf/id/JORFTEXT000047558499)
- [Building an SPA (SF doc)](https://symfony.com/doc/current/the-fast-track/en/27-spa.html)
- [QipsiusTCPDFBundle (SF bundle for PDF)](https://github.com/Qipsius/QipsiusTCPDFBundle)
- [Using advanced TCPDF](https://tcpdf.org/examples/example_052/)
- [PHP PDF Library](https://github.com/tecnickcom/TCPDF)

## Supports

- You can use regular features of : https://github.com/Monwoo/web-starters-free/issues

To support us and/or help us open more software, send a subvention with :
- [www.monwoo.com/don](https://www.monwoo.com/don)

