*(Øivind's original readme is in <./docs/original_readme.md>*

# Project 1 in imt2291 - Web Technology

[Link to project description](https://bitbucket.org/okolloen/imt2291-project1-spring2018/wiki/)

## Group members

* Yngve Hestem
* Kristian Sigtbakken Holm
* Thomas Løkkeborg

## Install

* Place this repository a place where your webserver can see it (htdocs for example)
* Run `composer install` to install dependencies of project
* Add a `config.php` file to the root directory of the project. It is used to provide environment-dependant constants. It should look like the file `config_example.php` in docs. One way to make it is to run the command `cp docs/config_example.php config.php`. Alter the `config.php` file to fit your environment.

## Documentation

Documentation is found under docs/ in the root of the repository.

## Remember

* All db connections should go through DB.php
* We write sql in the relevant classes (User-related sql inside User.php etc.)
* Code should be **tested** and have `/**`-style comments where relevant
* File issues before embarking on larger tasks
* Make one branch per issue you're working on (Unless it's a tiny issue)
