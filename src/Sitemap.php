<?php

namespace Codeception\Module;

use Codeception\Exception\ConnectionException;
use Codeception\Exception\ModuleException;
use Codeception\Lib\Framework;
use Codeception\Lib\InnerBrowser;
use Codeception\Lib\Interfaces\DependsOnModule;
use Codeception\Module;
use Codeception\TestInterface;
use Codeception\Util\Uri;
use Jasny\PHPUnit\Constraint\XSDValidation;
use vipnytt\SitemapParser;
use vipnytt\SitemapParser\Exceptions\SitemapParserException;

/**
 * Class Sitemap
 * @package Codeception\Module
 */
class Sitemap extends Module implements DependsOnModule
{

    protected $config = [
        'url' => '',
        'sitemapParser' => []
    ];

    protected $dependencyMessage = <<<EOF
Example configuring PhpBrowser as backend for Sitemap module.
--
modules:
    enabled:
        - Sitemap:
            depends: PhpBrowser
            url: http://localhost/
            sitemapParser:
                strict: 1
--
EOF;

    /**
     * @var \Symfony\Component\HttpKernel\Client|\Symfony\Component\BrowserKit\Client
     */
    public $client = null;
    public $isFunctional = false;

    /**
     * @var InnerBrowser
     */
    protected $connectionModule;

    public $params = [];
    public $response = "";

    /**
     * @param TestInterface $test
     */
    public function _before(TestInterface $test)
    {
        $this->client = &$this->connectionModule->client;
        $this->resetVariables();
    }

    protected function resetVariables()
    {
        $this->params = [];
        $this->response = "";
        $this->connectionModule->headers = [];
    }

    /**
     * @return array
     */
    public function _depends()
    {
        return ['Codeception\Lib\InnerBrowser' => $this->dependencyMessage];
    }

    /**
     * @param InnerBrowser $connection
     */
    public function _inject(InnerBrowser $connection)
    {
        $this->connectionModule = $connection;
        if ($this->connectionModule instanceof Framework) {
            $this->isFunctional = true;
        }
        if ($this->connectionModule instanceof PhpBrowser) {
            if (!$this->connectionModule->_getConfig('url')) {
                $this->connectionModule->_setConfig(['url' => $this->config['url']]);
            }
        }
    }

    /**
     * @return \Symfony\Component\BrowserKit\Client|\Symfony\Component\HttpKernel\Client
     * @throws ModuleException
     */
    protected function getRunningClient()
    {
        if ($this->client->getInternalRequest() === null) {
            throw new ModuleException($this, "Response is empty. Use `\$I->sendXXX()` methods to send HTTP request");
        }
        return $this->client;
    }

    /**
     * @param string $siteMapPath
     * @return string
     */
    protected function getUrl($siteMapPath)
    {
        return Uri::appendPath($this->config['url'], $siteMapPath);
    }

    /**
     * @param string $sitemapUrl
     * @return string
     */
    protected function getContentFromUrl($sitemapUrl)
    {
        $this->connectionModule->headers['Accept'] = 'application/xml';
        return (string)$this->connectionModule->_request('GET', $sitemapUrl);
    }

    /**
     * @return string
     */
    protected function getContentFromResponse()
    {
        return $this->connectionModule->_getResponseContent();
    }

    /**
     * @return string
     */
    protected function getCurrentUrl()
    {
        return rtrim($this->connectionModule->_getConfig()['url'], '/') . $this->connectionModule->_getCurrentUri();
    }

    /**
     * @param string $siteMapPath
     */
    public function seeSiteMapIsValid($siteMapPath)
    {
        $sitemapUrl = $this->getUrl($siteMapPath);
        $siteMap = $this->getContentFromUrl($sitemapUrl);

        $siteMapXml = simplexml_load_string($siteMap);
        $constraint = new XSDValidation(__DIR__ . '/../sitemap.xsd');
        \PHPUnit_Framework_Assert::assertThat($siteMapXml, $constraint);
    }

    /**
     * @param string $sitemapIndexPath
     */
    public function seeSiteIndexIsValid($sitemapIndexPath)
    {
        $sitemapIndexUrl = $this->getUrl($sitemapIndexPath);
        $sitemapIndex = $this->getContentFromUrl($sitemapIndexUrl);

        $siteIndexXml = simplexml_load_string($sitemapIndex);
        $constraint = new XSDValidation(__DIR__ . '/../siteindex.xsd');
        \PHPUnit_Framework_Assert::assertThat($siteIndexXml, $constraint);
    }

    /**
     * @param string $sitemapIndexPath
     */
    public function seeSiteMapIndexIsValid($sitemapIndexPath)
    {
        $this->seeSiteIndexIsValid($sitemapIndexPath);
    }

    /**
     *
     */
    public function seeResponseContainsValidSiteIndex()
    {
        $siteIndexXml = simplexml_load_string($this->getContentFromResponse());
        $constraint = new XSDValidation(__DIR__ . '/../siteindex.xsd');
        \PHPUnit_Framework_Assert::assertThat($siteIndexXml, $constraint);
    }

    /**
     *
     */
    public function seeResponseContainsValidSiteMap()
    {
        $siteMapXml = simplexml_load_string($this->getContentFromResponse());
        $constraint = new XSDValidation(__DIR__ . '/../sitemap.xsd');
        \PHPUnit_Framework_Assert::assertThat($siteMapXml, $constraint);
    }

    /**
     * @param string $expectedUrl
     */
    public function seeSiteMapResponseContainsUrl($expectedUrl)
    {
        $result = false;

        try {
            $siteMapUrl = $this->getCurrentUrl();
            $parser = $this->getSitemapParser();
            $parser->parseRecursive($siteMapUrl);
            foreach ($parser->getURLs() as $actualUrl => $tags) {
                if ($actualUrl === $expectedUrl) {
                    $result = true;
                    break;
                }
            }
            \PHPUnit_Framework_Assert::assertTrue($result);
        } catch (SitemapParserException $e) {
            throw new ConnectionException($e->getMessage());
        }
    }

    /**
     * @param string $expectedUrlPath
     */
    public function seeSiteMapResponseContainsUrlPath($expectedUrlPath)
    {
        $result = false;
        try {
            $siteMapUrl = $this->getCurrentUrl();
            $parser = $this->getSitemapParser();
            $parser->parseRecursive($siteMapUrl);
            foreach ($parser->getURLs() as $actualUrl => $tags) {
                if (strpos($actualUrl, $expectedUrlPath) > 0) {
                    $result = true;
                    break;
                }
            }
            \PHPUnit_Framework_Assert::assertTrue($result);
        } catch (SitemapParserException $e) {
            throw new ConnectionException($e->getMessage());
        }
    }

    /**
     *
     */
    public function grabUrlsFromSiteMapResponse()
    {
        try {
            $siteMapUrl = $this->getCurrentUrl();
            $parser = $this->getSitemapParser();
            $parser->parseRecursive($siteMapUrl);
            return $parser->getURLs();
        } catch (SitemapParserException $e) {
            throw new ConnectionException($e->getMessage());
        }
    }

    /**
     * @param string $siteMapPath
     */
    public function grabUrlsFromSiteMap($siteMapPath)
    {
        try {
            $siteMapUrl = $this->getUrl($siteMapPath);
            $parser = $this->getSitemapParser();
            $parser->parseRecursive($siteMapUrl);
            return $parser->getURLs();
        } catch (SitemapParserException $e) {
            throw new ConnectionException($e->getMessage());
        }
    }

    /**
     * @param string $siteMapPath
     * @param string $expectedUrl
     */
    public function seeSiteMapContainsUrl($siteMapPath, $expectedUrl)
    {
        $result = false;
        try {
            $siteMapUrl = $this->getUrl($siteMapPath);
            $parser = $this->getSitemapParser();
            $parser->parseRecursive($siteMapUrl);
            foreach ($parser->getURLs() as $actualUrl => $tags) {
                if ($actualUrl === $expectedUrl) {
                    $result = true;
                    break;
                }
            }
            \PHPUnit_Framework_Assert::assertTrue($result);
        } catch (SitemapParserException $e) {
            throw new ConnectionException($e->getMessage());
        }
    }

    /**
     * @param string $siteMapPath
     * @param string $expectedUrlPath
     */
    public function seeSiteMapContainsUrlPath($siteMapPath, $expectedUrlPath)
    {
        $result = false;
        try {
            $siteMapUrl = $this->getUrl($siteMapPath);
            $parser = $this->getSitemapParser();
            $parser->parseRecursive($siteMapUrl);
            foreach ($parser->getURLs() as $actualUrl => $tags) {
                if (strpos($actualUrl, $expectedUrlPath) > 0) {
                    $result = true;
                    break;
                }
            }
            \PHPUnit_Framework_Assert::assertTrue($result);
        } catch (SitemapParserException $e) {
            throw new ConnectionException($e->getMessage());
        }
    }

    /**
     * @return SitemapParser
     */
    protected function getSitemapParser()
    {
        $config = (array)$this->config['sitemapParser'];
        $parser = new SitemapParser(SitemapParser::DEFAULT_USER_AGENT, $config);
        return $parser;
    }
}
