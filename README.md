# Codeception Sitemap Module

[![Build Status](https://travis-ci.org/portrino/codeception-sitemap-module.svg?branch=master)](https://travis-ci.org/portrino/codeception-sitemap-module)
[![Code Climate](https://codeclimate.com/github/portrino/codeception-sitemap-module/badges/gpa.svg)](https://codeclimate.com/github/portrino/codeception-sitemap-module)
[![Test Coverage](https://codeclimate.com/github/portrino/codeception-sitemap-module/badges/coverage.svg)](https://codeclimate.com/github/portrino/codeception-sitemap-module/coverage)
[![Issue Count](https://codeclimate.com/github/portrino/codeception-sitemap-module/badges/issue_count.svg)](https://codeclimate.com/github/portrino/codeception-sitemap-module)
[![Latest Stable Version](https://poser.pugx.org/portrino/codeception-sitemap-module/v/stable)](https://packagist.org/packages/portrino/codeception-sitemap-module)
[![Total Downloads](https://poser.pugx.org/portrino/codeception-sitemap-module/downloads)](https://packagist.org/packages/portrino/codeception-sitemap-module)

This package provides parsing and validation of sitemap.xml files

## Installation

You need to add the repository into your composer.json file

```bash
    composer require --dev portrino/codeception-sitemap-module
```

## Usage

You can use this module as any other Codeception module, by adding 'Sitemap' to the enabled modules in your Codeception suite configurations.

### Enable module and setup the configuration variables

The `url` could be set in config file directly or via an environment variable: `%BASE_URL%`

```yml
modules:
    enabled:
        - Sitemap:
            depends: PhpBrowser
            url: ADD_YOUR_BASE_URL_HERE
 ```  

You could also configure the guzzle instance of the sitemap parser package. For example to disable SSL certification checks:  

```yml
modules:
    enabled:
      - Sitemap:
          sitemapParser:
            guzzle:
              verify: false
 ``` 

Update Codeception build
  
```bash
  codecept build
```

### Implement the cept / cest 

```php
    $I->wantToTest('If sitemap is valid.');
    
    $I->amOnPage('sitemap_index.xml');
    
    // validation against https://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd
    // sitemap will be retrieved from: http://<www.domain.tld>/sitemap.xml, where http://<www.domain.tld>/ is configured in module config
    $I->seeSiteMapIsValid('sitemap.xml');
    
    // validation against https://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd
    // siteindex will be retrieved from: http://<www.domain.tld>/sitemap_index.xml, where http://<www.domain.tld>/ is configured in module config
    $I->seeSiteIndexIsValid('sitemap_index.xml');

    // validate url occurence (also recursively through siteindex files!)
    
    // complete url
    $I->seeSiteMapContainsUrl('sitemap_index.xml', 'https://www.domain.tld/foo/bar/');
    
    // without base_url (checks if one of the sitemap urls contains the path) 
    $I->seeSiteMapContainsUrlPath('sitemap.xml', '/foo/bar');
    
    
    // via response object
    $I->seeSiteMapResponseContainsUrlPath('/bar/');
    $I->seeSiteMapResponseContainsUrlPath('/foo/');
  
```

## Authors

![](https://avatars0.githubusercontent.com/u/726519?s=40&v=4)

* **André Wuttig** - *Initial work, Unit Tests, Documentation* - [aWuttig](https://github.com/aWuttig)

See also the list of [contributors](https://github.com/portrino/codeception-sitemap-module/graphs/contributors) who participated in this project.
