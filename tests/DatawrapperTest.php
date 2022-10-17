<?php

namespace NSWDPC\Elemental\Tests\Datawrapper;

use NSWDPC\Elemental\Models\Datawrapper\ElementDatawrapper;
use SilverStripe\Dev\SapphireTest;

/**
 * Unit test to verify Datawrapper element handling
 * @author James
 */
class DatawrapperTest extends SapphireTest
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
     * Return test record for test
     */
    private function createTestRecord()
    {
        return $this->objFromFixture(ElementDatawrapper::class, 'standardrecord');
    }

    /**
     * Test that the InputURL value results in the expected values being saved
     */
    public function testUrlCreation()
    {
        $record = [
            'DatawrapperId' => 'abcd4',
            'DatawrapperVersion' => 8
        ];
        $record = ElementDatawrapper::create($record);
        $record->InputURL = "https://"
                                . ElementDatawrapper::config()->get('default_host')
                                . "/"
                                . $record->DatawrapperId
                                . "/"
                                . $record->DatawrapperVersion
                                . "/";
        $id = $record->write();

        $updatedRecord = ElementDatawrapper::get()->byId($id);

        $this->assertEquals($record->InputURL, $updatedRecord->DatawrapperURL());
        $this->assertEquals($record->DatawrapperId, $updatedRecord->DatawrapperId);
        $this->assertEquals($record->DatawrapperVersion, $updatedRecord->DatawrapperVersion);
    }

    /**
     * Test iframe save/update
     */
    public function testIframe()
    {
        $width = 300;
        $height = 200;

        $record = $this->createTestRecord();
        $record->DatawrapperId = 'test1';
        $record->DatawrapperVersion = 12;

        $record->Width = $width;
        $record->Height = $height;

        $record->InputURL = "https://"
                                . ElementDatawrapper::config()->get('default_host')
                                . "/"
                                . $record->DatawrapperId
                                . "/"
                                . $record->DatawrapperVersion
                                . "/";
        $id = $record->write();

        $updatedRecord = ElementDatawrapper::get()->byId($id);

        $this->assertTrue($updatedRecord->exists(), "Element datawrapper does not exist");

        $this->assertEquals($record->InputURL, $updatedRecord->DatawrapperURL());

        $iframe_width = $record->getIframeWidth();
        $this->assertEquals("100%", $iframe_width, "Responsive iframe should be 100% width");

        $iframe_height = $record->getIframeHeight();
        $this->assertEquals("200", $iframe_height, "Iframe should be {$height} height");

        $template = $record->forTemplate();

        $strings = [
            "id=\"{$record->DatawrapperIdAttribute()}",
            "allow=\"fullscreen\"",
            "loading=\"lazy\"",
            "height=\"{$iframe_height}\"",
            "<h2>{$record->Title}</h2>",
            "title=\"{$record->AlternateContent}\"",
            "src=\"" . htmlspecialchars($record->DatawrapperURL()) . "\""
        ];

        foreach ($strings as $string) {
            $this->assertTrue(strpos($template, $string) !== false, "{$string} should appear in the template");
        }
    }
}
