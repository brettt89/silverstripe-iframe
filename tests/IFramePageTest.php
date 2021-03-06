<?php

class IFramePageTest extends SapphireTest {

	public function setUp() {
		parent::setUp();
		Config::nest();
	}

	public function tearDown() {
		Config::unnest();
		parent::tearDown();
	}

	function testGetClass() {
		$iframe = new IFramePage();
		$iframe->AutoHeight = 1;
		$iframe->getClass();

		$this->assertContains('iframepage-height-auto', $iframe->getClass());

		$iframe->AutoHeight = 0;
		$iframe->getClass();

		$this->assertNotContains('iframepage-height-auto', $iframe->getClass());
	}

	function testGetStyle() {
		$iframe = new IFramePage();

		$iframe->FixedHeight = 0;
		$iframe->getStyle();
		$this->assertContains('height: 800px', $iframe->getStyle(), 'Height defaults to 800 if not set.');

		$iframe->FixedHeight = 100;
		$iframe->getStyle();
		$this->assertContains('height: 100px', $iframe->getStyle(), 'Fixed height is settable');

		$iframe->AutoWidth = 1;
		$iframe->FixedWidth = '200';
		$this->assertContains('width: 100%', $iframe->getStyle(), 'Auto width overrides fixed width');

		$iframe->AutoWidth = 0;
		$iframe->FixedWidth = '200';
		$this->assertContains('width: 200px', $iframe->getStyle(), 'Fixed width is settable');
	}

	function testAllowedUrls() {
		$iframe = new IFramePage();

		$tests = array(
			'allowed' => array(
				'http://anything',
				'https://anything',
				'page',
				'sub-page/link',
				'page/link',
				'page.html',
				'page.htm',
				'page.phpissoawesomewhywouldiuseanythingelse',
				'//url.com/page',
				'/root/page/link',
				'http://intranet:8888',
				'http://javascript:8080',
				'http://username:password@hostname/path?arg=value#anchor'
			),
			'banned' => array(
				'javascript:alert',
				'tel:0210001234',
				'ftp://url',
				'ssh://1.2.3.4',
				'ssh://url.com/page'
			)
		);

		foreach($tests['allowed'] as $url) {
			$iframe->IFrameURL = $url;
			$iframe->write();
			$this->assertContains($iframe->IFrameURL, $url);
		}

		foreach($tests['banned'] as $url) {
			$iframe->IFrameURL = $url;
			$this->setExpectedException('ValidationException');
			$iframe->write();
		}
	}

	public function testForceProtocol() {
		$origServer = $_SERVER;

		$page = new IFramePage();
		$page->URLSegment = 'iframe';
		$page->IFrameURL = 'http://target.com';

		Config::inst()->update('Director', 'alternate_protocol', 'http');
		Config::inst()->update('Director', 'alternate_base_url', 'http://host.com');
		$page->ForceProtocol = '';
		$controller = new IFramePage_Controller($page);
		$response = $controller->init();
		$this->assertNull($response);

		Config::inst()->update('Director', 'alternate_protocol', 'https');
		Config::inst()->update('Director', 'alternate_base_url', 'https://host.com');
		$page->ForceProtocol = '';
		$controller = new IFramePage_Controller($page);
		$response = $controller->init();
		$this->assertNull($response);

		Config::inst()->update('Director', 'alternate_protocol', 'http');
		Config::inst()->update('Director', 'alternate_base_url', 'http://host.com');
		$page->ForceProtocol = 'http://';
		$controller = new IFramePage_Controller($page);
		$response = $controller->init();
		$this->assertNull($response);

		Config::inst()->update('Director', 'alternate_protocol', 'http');
		Config::inst()->update('Director', 'alternate_base_url', 'http://host.com');
		$page->ForceProtocol = 'https://';
		$controller = new IFramePage_Controller($page);
		$response = $controller->init();
		$this->assertEquals($response->getHeader('Location'), 'https://host.com/iframe/');

		Config::inst()->update('Director', 'alternate_protocol', 'https');
		Config::inst()->update('Director', 'alternate_base_url', 'https://host.com');
		$page->ForceProtocol = 'http://';
		$controller = new IFramePage_Controller($page);
		$response = $controller->init();
		$this->assertEquals($response->getHeader('Location'), 'http://host.com/iframe/');

		$_SERVER = $origServer;
	}
}
