<?php

namespace NSWDPC\Datawrapper;

use NSWDPC\Elemental\Models\Datawrapper\ElementDatawrapper;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Versioned\Versioned;

/**
 * Controller for handling webhook submissions from Datawrapper
 * To create a webhook URL, see the README.md
 * @author James
 */
class WebHookController extends Controller
{
    private static bool $webhooks_enabled = true;

    private static string $webhooks_random_code = '';

    private static array $allowed_actions = [
        'submit' => true
    ];

    /**
     * Return link to this controller
     * @return string
     */
    #[\Override]
    public function Link($action = null)
    {
        if ($link = self::getWebhookURL()) {
            return $link;
        }

        return "";
    }

    /**
     * Mis-named public method retained for BC
     * @deprecated 1.0
     */
    public static function getWebookURL(): ?string
    {
        return static::getWebhookURL();
    }

    /**
     * Return the URL (absolute) for webhook submissions
     * If webhooks are not enabled, this will return boolean false
     */
    public static function getWebhookURL(): ?string
    {
        $enabled = self::config()->get('webhooks_enabled');
        if (!$enabled) {
            return null;
        }

        $code = self::config()->get('webhooks_random_code');
        $path = "_datawrapperwebhook/submit/";
        if ($code) {
            $path .= "{$code}/";
        }

        return Director::absoluteURL($path);
    }

    /**
     * Return the response body for a webhook submission
     * The two keys are 'success' being a boolean, count being the number of items changed
     * @return string JSON encoded value
     */
    protected function getResponseBody($success = true, $count = 0): string
    {
        $data = [
            'success' => $success,
            'count' => $count
        ];
        return json_encode($data);
    }

    /**
     * We have done something wrong
     */
    protected function serverError($status_code = 503, $message = ""): HTTPResponse
    {
        $response = HTTPResponse::create($this->getResponseBody(false), $status_code);
        $response->addHeader('Content-Type', 'application/json');
        return $response;
    }

    /**
     * Client (being Mailgun user agent) has done something wrong
     */
    protected function clientError($status_code  = 400, $message = ""): HTTPResponse
    {
        $response = HTTPResponse::create($this->getResponseBody(false), $status_code);
        $response->addHeader('Content-Type', 'application/json');
        return $response;
    }

    /**
     * All is good
     */
    protected function returnOK($status_code  = 200, $message = "OK", $count = 0): HTTPResponse
    {
        $response = HTTPResponse::create($this->getResponseBody(true, $count), $status_code);
        $response->addHeader('Content-Type', 'application/json');
        return $response;
    }

    /**
     * Ignore requests to /
     */
    public function index($request): HTTPResponse
    {
        return $this->clientError(404, "Not Found");
    }

    /**
     * Returns whether webhooks are enabled in Configuration
     */
    protected function webhooksEnabled(): bool
    {
        return $this->config()->get('webhooks_enabled');
    }

    /**
     * Test whether the random code sent in the request matches what is configured
     */
    protected function webhookRandomCodeMatch(HTTPRequest $request): bool
    {
        $code = $this->config()->get('webhooks_random_code');
        if (!$code) {
            return true;
        }

        $request_code = $request->param('ID');
        return $request_code == $code;
    }

    /**
     * Primary handler for submitted webooks
     * @throws \Exception
     */
    public function submit(?HTTPRequest $request = null): HTTPResponse
    {
        try {
            if (!$this->webhooksEnabled()) {
                throw new \Exception("Not enabled", 503);
            }

            if (!$this->webhookRandomCodeMatch($request)) {
                throw new \Exception("Forbidden", 403);
            }

            // requests are always posts - Mailgun should only POST
            if (!$request->isPOST()) {
                throw new \Exception("Method not allowed", 405);
            }

            // requests are application/json
            $content_type = $request->getHeader('Content-Type');
            if ($content_type != "application/json") {
                throw new \Exception("Unexpected content-type: {$content_type}");
            }

            // POST body
            $payload = json_decode((string) $request->getBody(), true);
            if (!$payload) {
                throw new \Exception("No payload found");
            }

            // grab DW ID and the version
            $id = $payload['id'] ?? null;
            $public_version = $payload['publicVersion'] ?? null;

            if (!$id || !$public_version) {
                throw new \Exception("Missing id or publicVersion parameter in POST");
            }

            /**
             * Retrieve Datawrapper elements from the draft stage, that can be autopublised
             * Matching the ID sent through
             */
            $elements = Versioned::get_by_stage(ElementDatawrapper::class, Versioned::DRAFT)
                            ->filter([
                                'DatawrapperId' => $id,// for this record ID
                                'DatawrapperVersion:LessThan' => $public_version, // avoid rolling back to earlier versions
                                'AutoPublish' => 1 // only get those that are marked to auto publish
                            ]);

            $count = $elements->count();
            if ($count > 0) {
                foreach ($elements as $element) {
                    // update with the new publicVersion
                    $element->DatawrapperVersion = $public_version;
                    $element->write();
                    // publish the element
                    $element->publishSingle();
                }
            }

            return $this->returnOK(200, "OK", $count);
        } catch (\Exception $exception) {
            return $this->clientError($exception->getCode(), $exception->getMessage());
        }
    }
}
