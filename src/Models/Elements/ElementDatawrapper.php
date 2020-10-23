<?php

namespace NSWDPC\Elemental\Models\Datawrapper;

use NSWDPC\Elemental\Models\Iframe\ElementIframe;
use SilverStripe\Forms\NumericField;
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

    private static $db = [
        'DatawrapperId' => 'Varchar(5)',// dw IDs are 5 chr long
        'DatawrapperVersion' => 'Int'
    ];

    private static $defaults = [
        'DatawrapperVersion' => 1
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
        if($this->DatawrapperVersion <= 0) {
            throw new ValidationException("Datawrapper version should be a number >= 1");
        }
        if($this->DatawrapperId == "") {
            throw new ValidationException("Please provide a Datawrapper ID");
        }
    }

    public function DatawrapperURL() {
        if(!$this->DatawrapperId) {
            return "";
        }
        $url = "https://datawrapper.dwcdn.net/"
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
            'URL',
            'IsResponsive',
            'Width',
        ]);

        $fields->addFieldToTab(
            'Root.Main',
            TextField::create(
                'DatawrapperId',
                'Datawrapper Id'
            )->setDescription(
                _t(
                    __CLASS__ . '.DW_ID_DESCRIPTION',
                    "If the URL provided is https://datawrapper.dwcdn.net/abcd1/10/, the Id is 'abcd1'"
                )
            )->setAttribute('required','required'),
            'IsLazy'
        );

        $fields->addFieldToTab(
            'Root.Main',
            NumericField::create(
                'DatawrapperVersion',
                'Datawrapper version'
            )->setDescription(
                _t(
                    __CLASS__ . '.DW_VERSION_DESCRIPTION',
                    "If the URL provided is https://datawrapper.dwcdn.net/abcd1/13/, the version is '13'"
                )
            )->setAttribute('required','required'),
            'IsLazy'
        );

        return $fields;
    }

}
