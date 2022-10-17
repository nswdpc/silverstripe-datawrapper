<?php

namespace NSWDPC\Elemental\Models\Datawrapper;

use Codem\Utilities\HTML5\UrlField;
use NSWDPC\Datawrapper\WebhookController;
use NSWDPC\Elemental\Models\Iframe\ElementIframe;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\ValidationException;
use Silverstripe\View\ArrayData;
use SilverStripe\View\Requirements;

/**
 * Datawrapper Element
 * @author James Ellis <mark.taylor@dpc.nsw.gov.au>
 */
class ElementDatawrapper extends ElementIframe
{

    /**
     * @var string
     */
    private static $table_name = 'ElementDatawrapper';

    /**
     * @var string
     */
    private static $icon = 'font-icon-code';

    /**
     * @var bool
     */
    private static $inline_editable = false;

    /**
     * @var string
     */
    private static $singular_name = 'Datawrapper visualisation';

    /**
     * @var string
     */
    private static $plural_name = 'Datawrapper visualisations';

    /**
     * @var string
     */
    private static $title = 'Datawrapper visualisation';

    /**
     * @var string
     */
    private static $description = 'Display a Datawrapper visualisation';

    /**
     * @var string
     */
    private static $default_host = 'datawrapper.dwcdn.net';

    /**
     * @var array
     */
    private static $db = [
        'Content' => 'HTMLText',
        'DatawrapperId' => 'Varchar(5)',// dw IDs are 5 chr long
        'DatawrapperVersion' => 'Int',
        'AutoPublish' => 'Boolean',
    ];

    /**
     * @var array
     */
    private static $defaults = [
        'DatawrapperVersion' => 1,
        'AutoPublish' => 0,
    ];

    /**
     * @var array
     * Provide indexes for fields used in queries
     */
    private static $indexes = [
        'DatawrapperVersion' => true,
        'DatawrapperId' => true,
        'AutoPublish' => true
    ];

    /**
     * @return string
     */
    public function getType()
    {
        return _t(__CLASS__ . '.BlockType', 'Datawrapper visualisation');
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
        $this->IsDynamic = 0;// iframe module turn off IsDynamic, DW provides its own.
        $this->setPartsFromUrl();
    }

    /**
     * Set required parts from the URL saved
     * @return void
     */
    protected function setPartsFromUrl()
    {
        if (!empty($this->InputURL)) {
            $path = trim(trim(parse_url($this->InputURL, PHP_URL_PATH), "/"));
            $path_parts = explode("/", $path);
            if (count($path_parts) != 2) {
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

            if (strlen($path_parts[0]) != 5) {
                throw new ValidationException(
                    _t(
                        __CLASS__ . '.DW_ID_CHR_LENGTH',
                        'The Datawrapper chart Id must be 5 characters long'
                    )
                );
            }

            $version = intval($path_parts[1]);
            if ($version < 1) {
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
    public function DatawrapperURL() : string
    {
        if (!$this->DatawrapperId) {
            return "";
        }
        if (!$this->DatawrapperVersion || $this->DatawrapperVersion <= 1) {
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
    public function DatawrapperIdAttribute() : string
    {
        $id = "datawrapper-chart-{$this->DatawrapperId}";
        return $id;
    }

    /**
     * Apply validator for CMS
     */
    public function getCMSValidator()
    {
        return new RequiredFields('InputURL');
    }

    /**
     * @inheritdoc
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName([
            'URL',// link field
            'URLID',// link field
            'IsDynamic',// remove auto resize field from iframe
            'IsResponsive',
            'Width',// the item width cannot be changed, it is always 100%
            'IsFullWidth',// this item is always full width
            'DatawrapperId',
            'DatawrapperVersion',
        ]);

        $fields->insertAfter(
            'Title',
            UrlField::create(
                'InputURL',
                _t(
                    __CLASS__ . ".DW_URL_LINK_TO_VISUALISATION",
                    'The Datawrapper \'Link to your visualisation:\' URL (Visualisation only option)'
                ),
                $this->DatawrapperURL()
            )->setDescription("In the format <code>https://datawrapper.dwcdn.net/abc12/1/</code>")
            ->setAttribute('pattern', 'https://datawrapper.dwcdn.net/abc12/1/')
            ->restrictToHttps()
            ->setRequiredParts(['scheme','host','path'])
        );

        $webhook_url = WebhookController::getWebookURL();
        if (!$webhook_url) {
            $fields->removeByName('AutoPublish');
        } else {
            $fields->insertAfter(
                'InputURL',
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
                        . "To enable this feature, please ensure the following URL is configured as a custom webhook in the relevant Team at Datawrapper: <code>{url}</code>",
                        [
                            "url" => $webhook_url
                        ]
                    )
                )
            );
        }

        $contentField = $fields->dataFieldByName('Content');
        if ($contentField) {
            $fields->insertAfter(
                'AutoPublish',
                $contentField
            );
        }

        return $fields;
    }
}
