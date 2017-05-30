<?php

namespace Codeception\Module;

use Codeception\Exception\ModuleException;
use Codeception\Lib\Framework;
use Codeception\Lib\InnerBrowser;
use Codeception\Lib\Interfaces\API;
use Codeception\Lib\Interfaces\ConflictsWithModule;
use Codeception\Lib\Interfaces\DependsOnModule;
use Codeception\Module;
use Codeception\TestInterface;
use Jasny\PHPUnit\Constraint\XSDValidation;
use vipnytt\SitemapParser;
use vipnytt\SitemapParser\Exceptions\SitemapParserException;

/**
 * Class Sitemap
 * @package Codeception\Module
 */
class Sitemap extends Module implements DependsOnModule, API, ConflictsWithModule
{

    protected $config = [
        'apiKey' => '',
        'url' => ''
    ];

    protected $dependencyMessage = <<<EOF
Example configuring PhpBrowser as backend for Sitemap module.
--
modules:
    enabled:
        - Yandix:
            depends: PhpBrowser
            url: http://localhost/
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
     * @return string
     */
    public function _conflicts()
    {
        return 'Codeception\Lib\Interfaces\API';
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
        $context = stream_context_create([
            'http' => [
                'header' => 'Accept: application/xml'
            ]
        ]);
        $siteMapUrl = $this->connectionModule->_getConfig()['url'] . ltrim($siteMapPath, '/');
        $siteMap = file_get_contents($siteMapUrl, false, $context);
        $siteMapXml = simplexml_load_string($siteMap);
        $constraint = new XSDValidation(__DIR__ . '/../sitemap.xsd');
        \PHPUnit_Framework_Assert::assertThat($siteMapXml, $constraint);
    }

    /**
     * @param string $siteIndexPath
     */
    public function seeSiteIndexIsValid($siteIndexPath)
    {
        $context = stream_context_create([
            'http' => [
                'header' => 'Accept: application/xml'
            ]
        ]);
        $siteIndexUrl = $this->connectionModule->_getConfig()['url'] . ltrim($siteIndexPath, '/');
        $siteIndex = file_get_contents($siteIndexUrl, false, $context);
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
            $parser = new SitemapParser();
            $parser->parseRecursive($siteMapUrl);

            foreach ($parser->getURLs() as $actualUrl => $tags) {
                if ($actualUrl === $expectedUrl) {
                    $result = true;
                    break;
                }
            }
            \PHPUnit_Framework_Assert::assertTrue($result);
        } catch (SitemapParserException $e) {
            echo $e->getMessage();
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
            $parser = new SitemapParser();
            $parser->parseRecursive($siteMapUrl);

            foreach ($parser->getURLs() as $actualUrl => $tags) {
                if (strpos($actualUrl, $expectedUrlPath) > 0) {
                    $result = true;
                    break;
                }
            }
            \PHPUnit_Framework_Assert::assertTrue($result);
        } catch (SitemapParserException $e) {
            echo $e->getMessage();
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
            $parser = new SitemapParser();
            $parser->parseRecursive($siteMapUrl);
            return $parser->getURLs();
        } catch (SitemapParserException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * @param string $siteMapPath
     */
    public function grabUrlsFromSiteMap($siteMapPath)
    {
        try {
            $siteMapUrl = $this->connectionModule->_getConfig()['url'] . ltrim($siteMapPath, '/');
            $parser = new SitemapParser();
            $parser->parseRecursive($siteMapUrl);
            return $parser->getURLs();
        } catch (SitemapParserException $e) {
            echo $e->getMessage();
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
            $siteMapUrl = $this->connectionModule->_getConfig()['url'] . ltrim($siteMapPath, '/');
            $parser = new SitemapParser();
            $parser->parseRecursive($siteMapUrl);
            foreach ($parser->getURLs() as $actualUrl => $tags) {
                if ($actualUrl === $expectedUrl) {
                    $result = true;
                    break;
                }
            }
            \PHPUnit_Framework_Assert::assertTrue($result);
        } catch (SitemapParserException $e) {
            echo $e->getMessage();
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
            $siteMapUrl = $this->connectionModule->_getConfig()['url'] . ltrim($siteMapPath, '/');
            $parser = new SitemapParser();
            $parser->parseRecursive($siteMapUrl);
            foreach ($parser->getURLs() as $actualUrl => $tags) {
                if (strpos($actualUrl, $expectedUrlPath) > 0) {
                    $result = true;
                    break;
                }
            }
            \PHPUnit_Framework_Assert::assertTrue($result);
        } catch (SitemapParserException $e) {
            echo $e->getMessage();
        }
    }
}
