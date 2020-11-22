<?php

namespace  NSWDPC\Elemental\Tests\Datawrapper;

use gorriecoe\Link\Models\Link;
use gorriecoe\LinkField\LinkField;
use NSWDPC\Datawrapper\WebHookController;
use NSWDPC\Elemental\Models\Datawrapper\ElementDatawrapper;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Director;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\FunctionalTest;
use Silverstripe\Assets\Dev\TestAssetStore;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\View\Requirements;

/**
 * Unit test to verify Datawrapper element handling
 * @author James
 */
class DatawrapperTest extends FunctionalTest
{

    protected $usesDatabase = true;

    public function setUp() {
        parent::setUp();
        Config::inst()->set( WebHookController::class, 'webhooks_random_code', 'randomecodeforurl' );
        Config::inst()->set( WebHookController::class, 'webhooks_enabled', true );
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    private function createTestRecord() {
        return [
            'Title' => 'Title',
            'ShowTitle' => 1,
            'IsLazy' => 1,
            'IsFullWidth' => 1,
            'IsResponsive' => '16x9',
            'AlternateContent' => 'AlternateContent',
        ];
    }

    /**
     * Test that the InputURL value results in the expected values being saved
     */
    public function testUrlCreation() {
        $record = [
            'DatawrapperId' => 'abcd4',
            'DatawrapperVersion' => 8
        ];
        $iframe = ElementDatawrapper::create($record);
        $iframe->InputURL = "https://"
                                . $iframe->config()->get('default_host')
                                . "/"
                                . $record['DatawrapperId']
                                . "/"
                                . $record['DatawrapperVersion']
                                . "/";
        $iframe->write();

        $this->assertEquals($iframe->DatawrapperURL(), $iframe->InputURL);
        $this->assertEquals($record['DatawrapperId'], $iframe->DatawrapperId);
        $this->assertEquals($record['DatawrapperVersion'], $iframe->DatawrapperVersion);
    }

    public function testIframe() {

        $width = 300;
        $height = 200;

        $record = $this->createTestRecord();
        $record['Title'] = 'IFRAME_TEST';
        $record['DatawrapperId'] = 'test1';
        $record['DatawrapperVersion'] = 12;

        $record['Width'] = $width;
        $record['Height'] = $height;

        $iframe = ElementDatawrapper::create($record);
        $iframe->InputURL = "https://"
                                . $iframe->config()->get('default_host')
                                . "/"
                                . $record['DatawrapperId']
                                . "/"
                                . $record['DatawrapperVersion']
                                . "/";
        $iframe->write();

        $this->assertEquals($iframe->DatawrapperURL(), $iframe->InputURL);

        $this->assertTrue($iframe->exists(), "Element datawrapper does not exist");

        $iframe_width = $iframe->getIframeWidth();
        $this->assertEquals("100%", $iframe_width, "Responsive iframe should be 100% width");

        $iframe_height = $iframe->getIframeHeight();
        $this->assertEquals("200", $iframe_height, "Iframe should be {$height} height");

        $template = $iframe->forTemplate();

        $strings = [
            "id=\"{$iframe->DatawrapperIdAttribute()}",
            "allow=\"fullscreen\"",
            "loading=\"lazy\"",
            "height=\"{$iframe_height}\"",
            "<h2>{$record['Title']}</h2>",
            "title=\"{$record['AlternateContent']}\"",
            "src=\"" . htmlspecialchars($iframe->DatawrapperURL()) . "\""
        ];

        foreach($strings as $string) {
            $this->assertTrue(strpos($template, $string) !== false, "{$string} should appear in the template");
        }

    }

    public function testWebHook() {

        $width = 300;
        $height = 200;

        $record = $this->createTestRecord();
        $record['Title'] = 'WEBHOOK_TEST';
        $record['DatawrapperId'] = 'hook1';
        $record['DatawrapperVersion'] = 12;
        $record['Width'] = $width;
        $record['Height'] = $height;
        $record['AutoPublish'] = 1;


        $iframe = ElementDatawrapper::create($record);
        $iframe->InputURL = "https://"
                                . $iframe->config()->get('default_host')
                                . "/"
                                . $record['DatawrapperId']
                                . "/"
                                . $record['DatawrapperVersion']
                                . "/";
        $iframe->write();


        $url = WebHookController::getWebookURL();
        $headers = [
            'Content-Type' => "application/json"
        ];
        $session = null;
        $data = [
            'id' => $record['DatawrapperId'],
            'publicVersion' => 3
        ];
        $cookies = null;
        $body = json_encode($data, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);

        $request = $this->post($url, $data, $headers, $session, $body, $cookies);
        $response = json_decode($request->getBody());

        $this->assertEquals(
            1,
            $response->count // expect 1 element
        );

        $this->assertEquals(
            200,
            $request->getStatusCode(),
            'Expected success, got: ' . $request->getStatusCode() . "/" . $request->getStatusDescription()
        );


        // turn off autopublish
        $iframe->AutoPublish = 0;
        $iframe->write();

        $this->assertEquals(0, $iframe->AutoPublish);

        $url = WebHookController::getWebookURL();
        $headers = [
            'Content-Type' => "application/json"
        ];
        $session = null;
        $data = [
            'id' => $record['DatawrapperId'],
            'publicVersion' => 1
        ];
        $cookies = null;
        $body = json_encode($data, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);

        $request = $this->post($url, $data, $headers, $session, $body, $cookies);
        $response = json_decode($request->getBody());

        $this->assertEquals(
            0,
            $response->count // autopublish turned off
        );

        // should still be a 200 OK
        $this->assertEquals(
            200,
            $request->getStatusCode(),
            'Expected success got: ' . $request->getStatusCode() . "/" . $request->getStatusDescription()
        );

    }

    public function testBadWebhook() {

        $headers = [
            'Content-Type' => "application/json"
        ];
        $session = null;
        $data = [
            'id' => 'fake1',
            'publicVersion' => 1
        ];
        $cookies = null;
        $body = json_encode($data, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);

        // put together a request with a fake code
        $path = "/_datawrapperwebhook/submit/notarealcode/";
        $url = Director::absoluteURL($path);

        $request = $this->post($url, $data, $headers, $session, $body, $cookies);
        $response = json_decode($request->getBody());

        // should not be a 200 OK
        $this->assertNotEquals(
            200,
            $request->getStatusCode(),
            'Expected failure got: ' . $request->getStatusCode() . "/" . $request->getStatusDescription()
        );

        // a valid URL
        $url = WebHookController::getWebookURL();


        $request = $this->get($url);
        $response = json_decode($request->getBody());

        // GET should not be a 200 OK
        $this->assertNotEquals(
            200,
            $request->getStatusCode(),
            'Expected failure got: ' . $request->getStatusCode() . "/" . $request->getStatusDescription()
        );

        // turn off webhooks
        Config::inst()->set( WebHookController::class, 'webhooks_enabled', false );

        $request = $this->post($url, $data, $headers, $session, $body, $cookies);
        $response = json_decode($request->getBody());

        // should not be a 200 OK
        $this->assertNotEquals(
            200,
            $request->getStatusCode(),
            'Expected failure got: ' . $request->getStatusCode() . "/" . $request->getStatusDescription()
        );
    }

}
