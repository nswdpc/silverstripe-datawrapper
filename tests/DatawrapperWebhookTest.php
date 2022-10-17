<?php

namespace  NSWDPC\Elemental\Tests\Datawrapper;

use NSWDPC\Datawrapper\WebHookController;
use NSWDPC\Elemental\Models\Datawrapper\ElementDatawrapper;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Director;
use SilverStripe\Dev\FunctionalTest;

/**
 * Unit test to verify Datawrapper element handling
 * @author James
 */
class DatawrapperWebhookTest extends FunctionalTest
{

    /**
     * @inheritdoc
     */
    protected $usesDatabase = true;

    /**
     * @inheritdoc
     */
    protected static $fixture_file = "./DataWrapperTest.yml";

    /**
     * @inheritdoc
     */
    public function setUp() : void {
        parent::setUp();
        Config::inst()->set( WebHookController::class, 'webhooks_random_code', 'randomecodeforurl' );
        Config::inst()->set( WebHookController::class, 'webhooks_enabled', true );
    }

    /**
     * Return test record for test
     */
    private function createTestRecord() {
         return $this->objFromFixture(ElementDatawrapper::class, 'webhookrecord');
    }

    /**
     * Test webhook controller POST
     */
    public function testWebHook() {

        $width = 300;
        $height = 200;

        $datawrapperId = 'hook1';

        $record = $this->createTestRecord();
        $record->DatawrapperId = $datawrapperId;
        $record->DatawrapperVersion = 2;// at this version
        $record->Width = $width;
        $record->Height = $height;
        $record->InputURL = "https://"
                                . ElementDatawrapper::config()->get('default_host')
                                . "/"
                                . $record->DatawrapperId
                                . "/"
                                . $record->DatawrapperVersion
                                . "/";
        $record->write();

        // POST request to the webhook controller
        $url = WebHookController::getWebookURL();
        $headers = [
            'Content-Type' => "application/json"
        ];
        $session = null;
        $data = [
            'id' => $datawrapperId,
            'publicVersion' => 3 // publishing to this version
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
        $record->AutoPublish = 0;
        $record->write();

        $this->assertEquals(0, $record->AutoPublish);

        $url = WebHookController::getWebookURL();
        $headers = [
            'Content-Type' => "application/json"
        ];
        $session = null;
        $data = [
            'id' => $record->DatawrapperId,
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

    /**
     * Test bad webhook controller POST
     */
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
