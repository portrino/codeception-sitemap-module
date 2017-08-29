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
     */
    public function seeSiteMapIsValid($siteMapPath)
    {
        $siteMapUrl = Uri::appendPath($this->config['url'], $siteMapPath);
        $this->connectionModule->headers['Accept'] = 'application/xml';
        $siteMap = (string)$this->connectionModule->_request('GET', $siteMapUrl);
        $siteMapXml = simplexml_load_string($siteMap);
        $constraint = new XSDValidation(__DIR__ . '/../sitemap.xsd');
        \PHPUnit_Framework_Assert::assertThat($siteMapXml, $constraint);
    }

    /**
     * @param string $siteIndexPath
     */
    public function seeSiteIndexIsValid($siteIndexPath)
    {
        $siteIndexUrl = Uri::appendPath($this->config['url'], $siteIndexPath);
        $this->connectionModule->headers['Accept'] = 'application/xml';
        $siteIndex = (string)$this->connectionModule->_request('GET', $siteIndexUrl);
        $siteIndexXml = simplexml_load_string($siteIndex);
        $constraint = new XSDValidation(__DIR__ . '/../siteindex.xsd');
        \PHPUnit_Framework_Assert::assertThat($siteIndexXml, $constraint);
    }

    /**
     *
     */
    public function seeResponseContainsValidSiteIndex()
    {
        $siteIndexXml = simplexml_load_string($this->connectionModule->_getResponseContent());
        $constraint = new XSDValidation(__DIR__ . '/../siteindex.xsd');
        \PHPUnit_Framework_Assert::assertThat($siteIndexXml, $constraint);
    }

    /**
     *
     */
    public function seeResponseContainsValidSiteMap()
    {
        $siteMapXml = simplexml_load_string($this->connectionModule->_getResponseContent());
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
            $siteMapUrl = rtrim($this->connectionModule->_getConfig()['url'], '/') .
                $this->connectionModule->_getCurrentUri();
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
            $siteMapUrl = rtrim($this->connectionModule->_getConfig()['url'], '/') .
                $this->connectionModule->_getCurrentUri();
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
            $siteMapUrl = rtrim($this->connectionModule->_getConfig()['url'], '/') .
                $this->connectionModule->_getCurrentUri();
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
            $siteMapUrl = Uri::appendPath($this->config['url'], $siteMapPath);
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
            $siteMapUrl = Uri::appendPath($this->config['url'], $siteMapPath);
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
            $siteMapUrl = Uri::appendPath($this->config['url'], $siteMapPath);
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
