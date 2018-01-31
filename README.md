# Prosjekt 1 IMT2291 våren 2018 #

Velkommen til prosjekt 1 i IMT2291 WWW-teknologi våren 2018. For å begynne å jobbe med prosjektet må en fra hver gruppe lage en fork av dette repositoriet og invitere de andre på gruppen til å delta på dette repositoriet.

Husk å velge å beholde rettigheter fra det originale repositoriet når dere oppretter forken av dette repositoriet, da får jeg automatisk tilgang til repositoriet. Sett også repositoriet til et privat repository, dere vil ikke dele koden deres med alle andre men kun de andre på gruppa.

# Prosjektdeltakere #

* Yngve Hestem
* Kristian Sigtbakken Holm
* Thomas Løkkeborg

# Oppgaveteksten

Oppgaveteksten ligger i [Wikien til det originale repositoriet](https://bitbucket.org/okolloen/imt2291-project1-spring2018/wiki/).

# Rapporten #

Rapporten til prosjektet legger dere i Wikien til deres egen fork av repositoriet.

*(Original readme from Øivind above)*

---

*(Our readme below)*

# Remember

* All db connections should go through DB.php
* We write sql in the relevant classes (User-related sql inside User.php etc.)
* Code should be **tested** and have `/**`-style comments where relevant
* File issues before embarking on larger tasks
* Make one branch per issue you're working on (?)

# Install

* Place this repository a place where your webserver can see it (htdocs for example)
* Run `composer install` to install dependencies of project
* Add a `config.php` file to the root directory of the project. It is used to provide environment-dependant constants. It should look like this:

        
```php
<?php

/**
 * Environment-dependant constants.
 */

class Constants {

    /**
     * DB constants. Used when connecting to database. Change to make php use 
     * different website (put in your own db details)
     */
    const $DB_DSN = 'mysql:dbname=imt2291_project1;host=127.0.0.1';
    const $DB_USER = 'root';
    const $DB_PASSWORD = 'veldigsikkertpassord';

    /**
     * Db constants for use during testing. Same as above but SHOULD POINT TO 
     * A DIFFERENT DATABASE. One that is disposable
     */
    const $DB_TEST_DSN = 'mysql:dbname=imt2291_project1_test;host=127.0.0.1';
    const $DB_TEST_USER = 'root';
    const $DB_TEST_PASSWORD = 'veldigsikkertpassord';
}
```
