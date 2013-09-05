php-odt2html
============

PHP library to convert OpenOffice text files (ODT) to HTML.

Usage
-----

```php
$odt2html = new \webd\odt2html\ODT2HTML($odt_file);
echo $odt2html->parse();
```