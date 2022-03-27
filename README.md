# B&L-test

Pasos que he seguido:
- composer create-project symfony/website-skeleton bl-test
- composer require symfony/apache-pack
- composer require easycorp/easyadmin-bundle

- Modificar el archivo .env para configurar la conexión a la BBDD

- php bin/console doctrine:database:create
- php bin/console doctrine:schema:create

- php bin/console make:entity
- php bin/console make:migration
- php bin/console doctrine:migrations:migrate

- php bin/console make:admin:dashboard
- php bin/console make:admin:crud

- php -S localhost:8000 -t public/ para levantar http://localhost:8000


He creado las entidades de Géneros, Actores, Directores y Películas. A pesar de que el CSV contiene más información me he ceñido a los datos que se solicitan en las instrucciones.

Al backend se accede a través de http://localhost:8000/admin

El comando se encuentra en `src/Command/ImportCsvCommand.php` y se lanza: `php bin/console app:import-csv`. Comprueba si existe un Género, Actor/Actriz, Director/a ó Película. En caso de existir lo actualiza, si no se crea.