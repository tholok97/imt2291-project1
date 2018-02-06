# Explanation of the use of `.htaccess`

The file `.htaccess` stored in the root of the project is used for some custom behavior by apache. It defines a regular expression "RewriteRule" that will make the site more user friendly, and allow for all the pages to be loaded from `index.php`. Loading everything from `index.php` is useful because we use twig.

The specifics of the regular expression are documented in the file itself.

Tutorial I used: <https://www.sitepoint.com/apache-mod_rewrite-examples/>
