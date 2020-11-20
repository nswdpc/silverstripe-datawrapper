<?php

namespace NSWDPC\Datawrapper;

use NSWDPC\Elemental\Models\Datawrapper\ElementDatawrapper;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;

/**
 * Controller for handling webhook submissions from Datawrapper
 * To create a webhook URL, see the README.md
 * @author James <james.ellis@dpc.nsw.gov.au>
 */
class WebHook extends Controller {

    private static $webhooks_enabled = true;

    private static $allowed_actions = [
        'submit' => true
    ];

    protected function getResponseBody($success = true) {
        $data = [
            'success' => $success
        ];
        return json_encode($data);
    }

    /**
     * We have done something wrong
     */
    protected function serverError($status_code = 503, $message = "") {
        Log::log($message, \Psr\Log\LogLevel::NOTICE);
        $response = HTTPResponse::create( $this->getResponseBody(false), $status_code);
        $response->addHeader('Content-Type', 'application/json');
        return $response;
    }

    /**
     * Client (being Mailgun user agent) has done something wrong
     */
    protected function clientError($status_code  = 400, $message = "") {
        Log::log($message, \Psr\Log\LogLevel::NOTICE);
        $response = HTTPResponse::create($this->getResponseBody(false), $status_code);
        $response->addHeader('Content-Type', 'application/json');
        return $response;
    }

    /**
     * All is good
     */
    protected function returnOK($status_code  = 200, $message = "OK") {
        $response = HTTPResponse::create($this->getResponseBody(true), $status_code);
        $response->addHeader('Content-Type', 'application/json');
        return $response;
    }

    /**
     * Ignore / requests
     */
    public function index($request) {
        return $this->clientError(404, "Not Found");
    }

    protected function webhooksEnabled() {
        return $this->config()->get('webhooks_enabled');
    }

    /**
     * Test whether the random code matches what is configured;
     */
    protected function webhookRandomCodeMatch(HTTPRequest $request) {
        $code = $this->config()->get('webhooks_random_code');
        if(!$code) {
            return true;
        }
        print_r($request->allParams());
    }

    /**
     * Primary handler for submitted webooks
     * @throws \Exception
     */
    public function submit(HTTPRequest $request = null) {

        try {

            if(!$this->webhooksEnabled()) {
                throw new \Exception("Not enabled", 503);
            }

            if(!$this->webhookRandomCodeMatch()) {
                throw new \Exception("Forbidden", 403);
            }

            // requests are always posts - Mailgun should only POST
            if(!$request->isPOST()) {
                throw new \Exception("Method not allowed", 405);
            }

            // requests are application/json
            $content_type = $request->getHeader('Content-Type');
            if($content_type != "application/json") {
                throw new \Exception("Unexpected content-type: {$content_type}");
            }

            // POST body
            $payload = json_decode($request->getBody(), true);
            if(!$payload) {
                throw new \Exception("No payload found");
            }

            // grab DW ID and the version
            $id = $payload['id'] ?? null;
            $public_version = $payload['publicVersion'] ?? null;

            if(!$id || !$public_version) {
                throw new \Exception("Missing id or publicVersion parameter in POST");
            }

            // there may be many
            $elements = ElementDatawrapper::get()->filter('DatawrapperId', $id)
                                                ->filter('DatawrapperVersion:LessThan', $public_version);
            foreach($elements as $element) {
                // publish the element
                $element->doPublish();
            }

            $this->returnOK();

        } catch (\Exception $e) {

        }

    }

}
