<?php

namespace NSWDPC\Elemental\Models\Datawrapper;

use BurnBright\ExternalURLField\ExternalURLField;
use NSWDPC\Datawrapper\WebhookController;
use NSWDPC\Elemental\Models\Iframe\ElementIframe;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\ValidationException;
use Silverstripe\View\ArrayData;
use SilverStripe\View\Requirements;

/**
 * Datawrapper Element
 * @author James Ellis <mark.taylor@dpc.nsw.gov.au>
 */
class ElementDatawrapper extends ElementIframe {

    private static $table_name = 'ElementDatawrapper';

    private static $icon = 'font-icon-code';

    private static $inline_editable = true;

    private static $singular_name = 'Datawrapper element';
    private static $plural_name = 'Datawrapper elements';

    private static $title = 'Datawrapper element';
    private static $description = 'Display Datawrapper content';

    private static $default_host = 'datawrapper.dwcdn.net';

    private static $db = [
        'DatawrapperId' => 'Varchar(5)',// dw IDs are 5 chr long
        'DatawrapperVersion' => 'Int',
        'AutoPublish' => 'Boolean',
    ];

    private static $defaults = [
        'DatawrapperVersion' => 1,
        'AutoPublish' => 0,
    ];

    public function getType()
    {
        return _t(__CLASS__ . '.BlockType', 'Datawrapper');
    }

    /**
     * Apply requirements when templating
     */
    public function forTemplate($holder = true)
    {
        Requirements::customScript(
            ArrayData::create([])->renderWith('NSWDPC/Elemental/Models/Datawrapper/ResponsiveScript'),
            'datawrapper-repsonsive-script'
        );

        Requirements::css(
            'nswdpc/silverstripe-datawrapper:client/static/style/datawrapper.css',
            'screen'
        );

        return parent::forTemplate($holder);
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->IsFullWidth = 1;//DW elements are always full width
        $this->IsResponsive = 1;//DW elements are always responsive
        $this->URLID = 0;// DW URLs are generated based on the ID, this field is removed

        $this->setPartsFromUrl();

    }

    /**
     * Set required parts from the URL saved
     * @return void
     */
    protected function setPartsFromUrl() {
        if(empty($this->InputURL)) {
            $this->DatawrapperId = '';
            $this->DatawrapperVersion = 1;
        } else {

            $path = trim( trim( parse_url( $this->InputURL, PHP_URL_PATH ), "/"));
            $path_parts = explode("/", $path);
            if(count($path_parts) != 2) {
                throw new ValidationException(
                    _t(
                        __CLASS__ . '.URL_NOT_HTTPS',
                        'The Datawrapper path must have a 5 character Datawrapper chart Id and a version number. The URL provided was {url}',
                        [
                            'url' => $this->InputURL
                        ]
                    )
                );
            }

            if(strlen($path_parts[0]) != 5) {
                throw new ValidationException(
                    _t(
                        __CLASS__ . '.URL_NOT_HTTPS',
                        'The Datawrapper chart Id must be 5 characters long'
                    )
                );
            }

            $version = intval($path_parts[1]);
            if($version < 1) {
                throw new ValidationException(
                    _t(
                        __CLASS__ . '.URL_NOT_HTTPS',
                        'The Datawrapper version must be >= 1'
                    )
                );
            }

            $this->DatawrapperId = $path_parts[0];
            $this->DatawrapperVersion = $version;


        }
    }

    /**
     * Return the datawrapper URL
     */
    public function DatawrapperURL() {
        if(!$this->DatawrapperId) {
            return "";
        }
        if(!$this->DatawrapperVersion || $this->DatawrapperVersion <= 1) {
            $this->DatawrapperVersion = 1;
        }
        $url = "https://"
                . $this->config()->get('default_host')
                . "/"
                . $this->DatawrapperId
                . "/"
                . $this->DatawrapperVersion
                . "/";
        return $url;
    }

    public function DatawrapperIdAttribute() {
        $id = "datawrapper-chart-" . $this->DatawrapperId;
        return $id;
    }

    public function getCMSFields() {
        $fields = parent::getCMSFields();
        $fields->removeByName([
            'IsResponsive',
            'Width',
            'DatawrapperId',
            'DatawrapperVersion',
        ]);

        $fields->insertAfter(
            ExternalURLField::create(
                'InputURL',
                'Datawrapper embed URL',
                $this->DatawrapperURL()
            )->setDescription("In the format 'https://datawrapper.dwcdn.net/abc12/1/'")
            ->setAttribute('pattern', 'https://datawrapper.dwcdn.net/abc12/1/')
            ->setConfig([
                'html5validation' => true,
                'defaultparts' => [
                    'scheme' => 'https'
                ]
            ])->setInputType('url'),
            'Title'
        );

        $webhook_url = WebhookController::getWebookURL();
        if(!$webhook_url) {
            $fields->removeByName('AutoPublish');
        } else {
            $fields->insertAfter(
                CheckboxField::create(
                    'AutoPublish',
                    'Auto publish'
                )->setDescription(
                    _t(
                        __CLASS__ . '.DW_AUTOPUBLISH',
                        "If checked, when the chart is published at Datawrapper, this element will be published."
                        . "<br>"
                        . "The parent item of this element will not be auto-published"
                        . "<br>"
                        . "Ensure the following URL is configured as a custom webhook at Datawrapper<br><br>{url}",
                        [
                            "url" => $webhook_url
                        ]
                    )
                ),
                'InputURL'
            );
        }

        return $fields;
    }

}
