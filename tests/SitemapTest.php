<?php

namespace Codeception\Module\Tests;

/*
 * This file is part of the Codeception Sitemap Module project
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * LICENSE file that was distributed with this source code.
 *
 */

use Codeception\Module\Sitemap;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Class SitemapTest
 * @package Codeception\Module\Tests
 */
class SitemapTest extends TestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject|Sitemap
     */
    protected $sitemap;

    const SITEMAP_EXAMPLE_PATH = 'example_sitemap';
    const SITEMAPINDEX_EXAMPLE_PATH = 'example_siteindex';
    const SITEMAP_EXAMPLE_URL =
        'https://raw.githubusercontent.com/portrino/codeception-sitemap-module/master/tests/fixtures/sitemap.xml';
    const SITEMAPINDEX_EXAMPLE_URL =
        'https://raw.githubusercontent.com/portrino/codeception-sitemap-module/master/tests/fixtures/sitemap_index.xml';

    /**
     * @var string
     */
    protected $sitemapContent;

    /**
     * @var string
     */
    protected $sitemapIndexContent;

    protected function runDefaultMockInitialization()
    {
        if ($this->sitemap === null) {
            $this->sitemap = $this->getMockBuilder(Sitemap::class)
                    ->setMethods(
                        [
                            'getUrl',
                            'getContentFromUrl'
                        ]
                    )
                    ->disableOriginalConstructor()
                    ->getMock();
        }

        $this->sitemap
            ->expects(static::any())
            ->method('getUrl')
            ->will(
                static::returnValueMap(
                    [
                        [self::SITEMAP_EXAMPLE_PATH, self::SITEMAP_EXAMPLE_URL],
                        [self::SITEMAPINDEX_EXAMPLE_PATH, self::SITEMAPINDEX_EXAMPLE_URL]
                    ]
                )
            );

        $this->sitemapContent = file_get_contents(
            __DIR__ . '/fixtures/sitemap.xml'
        );

        $this->sitemapIndexContent = file_get_contents(
            __DIR__ . '/fixtures/sitemap_index.xml'
        );

        $this->sitemap
            ->expects(static::any())
            ->method('getContentFromUrl')
            ->will(
                static::returnValueMap(
                    [
                        [self::SITEMAP_EXAMPLE_URL, $this->sitemapContent],
                        [self::SITEMAPINDEX_EXAMPLE_URL, $this->sitemapIndexContent]
                    ]
                )
            );
    }

    /**
     * @test
     */
    public function seeSiteMapIsValid()
    {
        $this->runDefaultMockInitialization();
        $this->sitemap->seeSiteMapIsValid(self::SITEMAP_EXAMPLE_PATH);
    }

    /**
     * @test
     */
    public function seeSiteMapIndexIsValid()
    {
        $this->runDefaultMockInitialization();
        $this->sitemap->seeSiteIndexIsValid(self::SITEMAPINDEX_EXAMPLE_PATH);
        $this->sitemap->seeSiteMapIndexIsValid(self::SITEMAPINDEX_EXAMPLE_PATH);
    }

    /**
     * @test
     */
    public function seeResponseContainsValidSiteMap()
    {
        $this->sitemap = $this->getMockBuilder(Sitemap::class)
            ->setMethods(
                [
                    'getUrl',
                    'getContentFromUrl',
                    'getContentFromResponse'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->runDefaultMockInitialization();
        $this->sitemap
            ->expects(static::any())
            ->method('getContentFromResponse')
            ->willReturn($this->sitemapContent);

        $this->sitemap->seeResponseContainsValidSiteMap();
    }

    /**
     * @test
     */
    public function seeResponseContainsValidSiteIndex()
    {
        $this->sitemap = $this->getMockBuilder(Sitemap::class)
            ->setMethods(
                [
                    'getUrl',
                    'getContentFromUrl',
                    'getContentFromResponse'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->runDefaultMockInitialization();
        $this->sitemap
            ->expects(static::any())
            ->method('getContentFromResponse')
            ->willReturn($this->sitemapIndexContent);

        $this->sitemap->seeResponseContainsValidSiteIndex();
    }

    /**
     * @test
     */
    public function seeSiteMapResponseContainsUrl()
    {
        $this->sitemap = $this->getMockBuilder(Sitemap::class)
            ->setMethods(
                [
                    'getUrl',
                    'getContentFromUrl',
                    'getCurrentUrl'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->runDefaultMockInitialization();
        $this->sitemap
            ->expects(static::any())
            ->method('getCurrentUrl')
            ->willReturn(self::SITEMAP_EXAMPLE_URL);

        $this->sitemap->seeSiteMapResponseContainsUrl(
            'http://www.example.com/catalog?item=12&desc=vacation_hawaii'
        );
    }

    /**
     * @test
     */
    public function seeSiteMapResponseContainsUrlPath()
    {
        $this->sitemap = $this->getMockBuilder(Sitemap::class)
            ->setMethods(
                [
                    'getUrl',
                    'getContentFromUrl',
                    'getCurrentUrl'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->runDefaultMockInitialization();
        $this->sitemap
            ->expects(static::any())
            ->method('getCurrentUrl')
            ->willReturn(self::SITEMAP_EXAMPLE_URL);

        $this->sitemap->seeSiteMapResponseContainsUrlPath(
            '/catalog?item=12&desc=vacation_hawaii'
        );
    }

    /**
     * @test
     */
    public function grabUrlsFromSiteMapResponse()
    {
        $this->sitemap = $this->getMockBuilder(Sitemap::class)
            ->setMethods(
                [
                    'getUrl',
                    'getContentFromUrl',
                    'getCurrentUrl'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->runDefaultMockInitialization();
        $this->sitemap
            ->expects(static::any())
            ->method('getCurrentUrl')
            ->willReturn(self::SITEMAP_EXAMPLE_URL);

        $urls = $this->sitemap->grabUrlsFromSiteMapResponse();
        self::assertEquals(5, count($urls));

        self::assertArrayHasKey(
            'http://www.example.com/catalog?item=12&desc=vacation_hawaii',
            $urls
        );
    }

    /**
     * @test
     */
    public function grabUrlsFromSiteMap()
    {
        $this->runDefaultMockInitialization();
        $urls = $this->sitemap->grabUrlsFromSiteMap(self::SITEMAP_EXAMPLE_PATH);
        self::assertEquals(5, count($urls));

        self::assertArrayHasKey(
            'http://www.example.com/catalog?item=12&desc=vacation_hawaii',
            $urls
        );
    }

    /**
     * @test
     */
    public function seeSiteMapContainsUrl()
    {
        $this->runDefaultMockInitialization();

        $this->sitemap->seeSiteMapContainsUrl(
            self::SITEMAP_EXAMPLE_PATH,
            'http://www.example.com/catalog?item=12&desc=vacation_hawaii'
        );
    }

    /**
     * @test
     */
    public function seeSiteMapContainsUrlPath()
    {
        $this->runDefaultMockInitialization();

        $this->sitemap->seeSiteMapContainsUrlPath(
            self::SITEMAP_EXAMPLE_PATH,
            'catalog?item=12&desc=vacation_hawaii'
        );
    }
}
