# Hei Øivind! Her er noen nøkkelpunkter om vår innlevering:

* Vi mener vi har løst hele oppgaven (alle punktene du pratet om i oppgavebeskrivelsen)
* Vi har forsøkt å gjøre det lett å installere prosjektet. Ta kontakt om noe skurrer (om det er lov).
* Merk at hvis noe av dataen som ligger på nettsiden er rar, så er det fordi vi la den direkte inn i databasen for testing (foreks. finnes det brukere der med ikke-epost brukernavn).
* Vi har prosjektet kjørende på en Digital Ocean instance på linken gitt under. (Mest for egen læring. Få erfaring med auto-deploy og driftsproblematikk). Der ligger det allerede noen videoer og spillelister, om du vil slippe å legge inn selv.
* Vi har eksperimentert med Docker i prosjektet. Nettsiden på webben kjører i docker helt fint, men det krever litt manuelt oppsett for å få det til å tikke og gå.
* Vi har skrevet all koden vår på engelsk, da det føles mest naturlig for gruppa å skrive kode på engelsk.

---

# Project 1 in imt2291 - Web Technology

* [Øivind's original readme](./docs/original_readme.md)
* [Link to project description](https://bitbucket.org/okolloen/imt2291-project1-spring2018/wiki/)
* Project should be running on <http://tyk-www-project1.yngvehestem.no/> (which points to <http://188.166.44.12/>) in docker containers with auto-deployment capabilities.

## Group members

* Yngve Hestem
* Kristian Sigtbakken Holm
* Thomas Løkkeborg

## Install

* Place this repository a place where your webserver can see it (htdocs for example)
* Run `composer install` to install dependencies of project
	* To install composer:

				sudo apt update
				sudo apt install composer
				sudo apt install php7.0-mbstring
				sudo apt install php7.0-xml
	
* Add a `config.php` file to the root directory of the project. It is used to provide environment-dependant constants. It should look like the file `config_example.php` in docs. One way to make it is to run the command `cp docs/config_example.php config.php`. Alter the `config.php` file to fit your environment.
* Configure your apache2 php.ini config file to reflect the sizes in `src/constants.php`.
* Import the contents of `docs/export_lowercase.sql` into two new databases, one for production and one for testing. (called `imt2291_project1_db` and `imt2291_project1_test` for example)
* The project needs write access to the `uploadedFiles` directory.
    * **Linux / Mac OS**: `chown -R <apache2-user> uploadedFiles`. (In lampp the user is `daemon` | apache2 default user is www-data)
    * **Windows**: Dunno. Google it

## To run in docker

* Do the steps above (the first step is unnecessary).
* The project also needs write access to the `db_data` directory. This is where the database will end up.
* You need to have `docker` and `docker-compose` installed.
* `docker-compose up -d` should now build and run the service. Check `docker-compose.yml` file for details.
* You need to import the database somehow.. this is not automatic. `docker exec -it <name-of-db-container> bash` while the service is running and adding it manually is one alternative. (from `docs/export_lowercase.sql`)

## Test

Tests are placed under `tests/`. Run all tests with `./vendor/bin/phpunit tests`. (Note that all tests except `tests/FunctionalTest.php` are unit tests)

## Documentation

Documentation is found under `docs/` in the root of the repository. You'll find a collection of documents we used during development of this site.

## Remember

* All db connections should go through DB.php
* We write sql in the relevant classes (User-related sql inside UserManager.php etc.)
* Code should be **tested** and have `/**`-style comments where relevant
* File issues before embarking on larger tasks
* Make one branch per issue you're working on (Unless it's a tiny issue)
