<?php

namespace Tests\Wallabag\CoreBundle\GuzzleSiteAuthenticator;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use BD\GuzzleSiteAuthenticator\SiteConfig\SiteConfig;
use Graby\SiteConfig\SiteConfig as GrabySiteConfig;
use PHPUnit_Framework_TestCase;
use Wallabag\CoreBundle\GuzzleSiteAuthenticator\GrabySiteConfigBuilder;

class GrabySiteConfigBuilderTest extends PHPUnit_Framework_TestCase
{
    /** @var \Wallabag\CoreBundle\GuzzleSiteAuthenticator\GrabySiteConfigBuilder */
    protected $builder;

    public function testBuildConfigExists()
    {
        /* @var \Graby\SiteConfig\ConfigBuilder|\PHPUnit_Framework_MockObject_MockObject */
        $grabyConfigBuilderMock = $this->getMockBuilder('\Graby\SiteConfig\ConfigBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $grabySiteConfig = new GrabySiteConfig();
        $grabySiteConfig->requires_login = true;
        $grabySiteConfig->login_uri = 'http://example.com/login';
        $grabySiteConfig->login_username_field = 'login';
        $grabySiteConfig->login_password_field = 'password';
        $grabySiteConfig->login_extra_fields = ['field=value'];
        $grabySiteConfig->not_logged_in_xpath = '//div[@class="need-login"]';

        $grabyConfigBuilderMock
            ->method('buildForHost')
            ->with('example.com')
            ->will($this->returnValue($grabySiteConfig));

        $logger = new Logger('foo');
        $handler = new TestHandler();
        $logger->pushHandler($handler);

        $this->builder = new GrabySiteConfigBuilder(
            $grabyConfigBuilderMock,
            ['example.com' => ['username' => 'foo', 'password' => 'bar']],
            $logger
        );

        $config = $this->builder->buildForHost('example.com');

        $this->assertEquals(
            new SiteConfig([
                'host' => 'example.com',
                'requiresLogin' => true,
                'loginUri' => 'http://example.com/login',
                'usernameField' => 'login',
                'passwordField' => 'password',
                'extraFields' => ['field' => 'value'],
                'notLoggedInXpath' => '//div[@class="need-login"]',
                'username' => 'foo',
                'password' => 'bar',
            ]),
            $config
        );

        $records = $handler->getRecords();

        $this->assertCount(1, $records, 'One log was recorded');
    }

    public function testBuildConfigDoesntExist()
    {
        /* @var \Graby\SiteConfig\ConfigBuilder|\PHPUnit_Framework_MockObject_MockObject */
        $grabyConfigBuilderMock = $this->getMockBuilder('\Graby\SiteConfig\ConfigBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $grabyConfigBuilderMock
            ->method('buildForHost')
            ->with('unknown.com')
            ->will($this->returnValue(new GrabySiteConfig()));

        $logger = new Logger('foo');
        $handler = new TestHandler();
        $logger->pushHandler($handler);

        $this->builder = new GrabySiteConfigBuilder(
            $grabyConfigBuilderMock,
            [],
            $logger
        );

        $config = $this->builder->buildForHost('unknown.com');

        $this->assertFalse($config);

        $records = $handler->getRecords();

        $this->assertCount(1, $records, 'One log was recorded');
    }
}