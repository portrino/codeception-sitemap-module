# Codeception Sitemap Module

This package provides parsing and validation of sitemap.xml files

## Installation

You need to add the repository into your composer.json file

```bash
    composer require --dev portrino/codeception-sitemap-module
```

## Usage

You can use this module as any other Codeception module, by adding 'Sitemap' to the enabled modules in your Codeception suite configurations.

### Enable module and setup the configuration variables

- The `url` could be set in config file directly or via an environment variable: `%BASE_URL%`

```yml
modules:
    enabled:
        - Sitemap:
            depends: PhpBrowser
            url: ADD_YOUR_BASE_URL_HERE
 ```  

Update Codeception build
  
```bash
  codecept build
```

### Implement the cept / cest 

```php
  $I->wantToTest('If sitemap is valid.');
  $I->amOnPage('sitemap.xml');
  
  $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
  
  // validate all
  $I->seeResponseContainsValidSitemap();
  
```