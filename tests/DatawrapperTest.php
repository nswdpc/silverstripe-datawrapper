<?php

namespace  NSWDPC\Elemental\Tests\QuickGallery;

use gorriecoe\Link\Models\Link;
use gorriecoe\LinkField\LinkField;
use NSWDPC\Elemental\Models\Iframe\ElementIframe;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use Silverstripe\Assets\Dev\TestAssetStore;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\View\Requirements;

/**
 * Unit test to verify Datawrapper element handling
 * @author James
 */
class DatawrapperTest extends SapphireTest
{

    protected $usesDatabase = true;

    public function setUp() {
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testIframe() {

        Config::inst()->update(
            ElementIframe::class,
            'default_allow_attributes',
            [
                'fullscreen'
            ]
        );

        $width = 300;
        $height = 200;

        $record = [
            'Title' => 'DATAWRAPPER_TITLE',
            'ShowTitle' => 1,
            'IsLazy' => 1,
            'IsFullWidth' => 1,
            'IsResponsive' => '16x9',
            'Width' => $width,
            'Height' => $height,
            'AlternateContent' => 'ALT_CONTENT',
            'DatawrapperId' => 'abcd1245',
            'DatawrapperVersion' => 8
        ];

        $iframe = ElementDatawrapper::create($record);
        $iframe->write();

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
            "height=\"200\"",
            "<h2>DATAWRAPPER_TITLE</h2>",
            "title=\"ALT_CONTENT\"",
            "src=\"" . htmlspecialchars($iframe->DatawrapperURL()) . "\""
        ];

        foreach($strings as $string) {
            $this->assertTrue(strpos($template, $string) !== false, "{$string} should appear in the template");
        }

    }

}
