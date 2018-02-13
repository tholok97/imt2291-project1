
# Project 1 in imt2291 - Web Technology

* [Øivind's original readme](./docs/original_readme.md)
* [Link to project description](https://bitbucket.org/okolloen/imt2291-project1-spring2018/wiki/)
* Project should be running on <http://10.212.136.151> (IP is internal to NTNU's networks. You must be connected to a NTNU network to have access).

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
* Import the contents of `docs/export_lowercase.sql` into two new databases, one for production and one for testing. (called `imt2291_project1_db` and `imt2291_project1_test` for example)
* The project needs write access to the `uploadedFiles` directory.
    * **Linux / Mac OS**: `chown -R <apache2-user> uploadedFiles`. (In lampp the user is `daemon`)
    * **Windows**: Dunno. Google it

## Test

Tests are placed under `tests/`. Run all tests with `./vendor/bin/phpunit tests/*`

## Documentation

Documentation is found under docs/ in the root of the repository.

## Remember

* All db connections should go through DB.php
* We write sql in the relevant classes (User-related sql inside UserManager.php etc.)
* Code should be **tested** and have `/**`-style comments where relevant
* File issues before embarking on larger tasks
* Make one branch per issue you're working on (Unless it's a tiny issue)
