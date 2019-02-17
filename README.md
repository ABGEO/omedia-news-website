# omedia-news-website

## Instalation Guide

1) composer install
2) Setup db connection string in .env file; ex.: DATABASE_URL=mysql://user:pass@host:3306/db_name
3) Setup Mailer; ex.: MAILER_URL=smtp://host:port?encryption=ssl&auth_mode=login&username=&password=
4) run php bin/console doctrine:migrations:migrate
