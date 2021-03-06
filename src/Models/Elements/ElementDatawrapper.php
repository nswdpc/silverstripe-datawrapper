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

    /**
     * Handle default settings prior to write
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->IsFullWidth = 1;//DW elements are always full width
        $this->Width = "100%";// DW elements are always full width
        $this->IsResponsive = 1;//DW elements are always responsive
        $this->URLID = 0;// DW URLs are generated based on the provided embed URL, link module not used
        $this->setPartsFromUrl();
    }

    /**
     * Set required parts from the URL saved
     * @return void
     */
    protected function setPartsFromUrl() {
        if(!empty($this->InputURL)) {

            $path = trim( trim( parse_url( $this->InputURL, PHP_URL_PATH ), "/"));
            $path_parts = explode("/", $path);
            if(count($path_parts) != 2) {
                throw new ValidationException(
                    _t(
                        __CLASS__ . '.DW_URL_NOT_VALID',
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
                        __CLASS__ . '.DW_ID_CHR_LENGTH',
                        'The Datawrapper chart Id must be 5 characters long'
                    )
                );
            }

            $version = intval($path_parts[1]);
            if($version < 1) {
                throw new ValidationException(
                    _t(
                        __CLASS__ . '.DW_URL_VERSION_FAILURE',
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
     * @return string
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

    /**
     * Return the "id" attribute for a DW element
     * Note that only one element per DatawrapperId can exist on a single page or "id" clashes will happen
     * @return string
     */
    public function DatawrapperIdAttribute() {
        $id = "datawrapper-chart-{$this->DatawrapperId}";
        return $id;
    }

    /**
     * Set up fields for editor content updates
     */
    public function getCMSFields() {
        $fields = parent::getCMSFields();
        $fields->removeByName([
            'IsResponsive',
            'Width',// the item width cannot be changed, it is always 100%
            'IsFullWidth',// this item is always full width
            'DatawrapperId',
            'DatawrapperVersion',
        ]);

        $fields->insertAfter(
            ExternalURLField::create(
                'InputURL',
                _t(__CLASS__ . ".DW_URL_NOT_EMBED_CODE", 'Datawrapper \'fullscreen share URL\' (not the embed code)'),
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
                        "If checked, this element will be published when the chart is published at Datawrapper, "
                        . "<br>"
                        . "The parent item of this element will not be published at the same time"
                        . "<br>"
                        . "To enable this feature, please ensure the following URL is configured as a custom webhook in the relevant Team at Datawrapper<br><br>{url}",
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
