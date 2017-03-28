<?php

namespace SilverStripe\Translatable\Tests;

use Page;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Translatable\Model\Translatable;

/**
 * @package translatable
 */
class TranslatableSiteConfigTest extends SapphireTest
{
    protected static $fixture_file = 'translatable/tests/unit/TranslatableSiteConfigTest.yml';

    protected static $required_extensions = [
        SiteTree::class => [Translatable::class],
        SiteConfig::class => [Translatable::class],
    ];

    protected static $illegal_extensions = [
        // @todo: Update namespace to match subsites
        SiteTree::class => array('SiteTreeSubsites')
    ];

    private $origLocale;

    protected function setUp()
    {
        parent::setUp();

        $this->origLocale = Translatable::default_locale();
        Translatable::set_default_locale('en_US');
    }

    protected function tearDown()
    {
        Translatable::set_default_locale($this->origLocale);
        Translatable::set_current_locale($this->origLocale);

        parent::tearDown();
    }

    public function testCurrentCreatesDefaultForLocale()
    {
        Translatable::set_current_locale(Translatable::default_locale());
        $configEn = SiteConfig::current_site_config();
        Translatable::set_current_locale('fr_FR');
        $configFr = SiteConfig::current_site_config();
        Translatable::set_current_locale(Translatable::default_locale());

        $this->assertInstanceOf(SiteConfig::class, $configFr);
        $this->assertEquals($configFr->Locale, 'fr_FR');
        $this->assertEquals($configFr->Title, $configEn->Title, 'Copies title from existing config');
        $this->assertEquals(
            $configFr->getTranslationGroup(),
            $configEn->getTranslationGroup(),
            'Created in the same translation group'
        );
    }

    public function testCanEditTranslatedRootPages()
    {
        $configEn = $this->objFromFixture(SiteConfig::class, 'en_US');
        $configDe = $this->objFromFixture(SiteConfig::class, 'de_DE');

        $pageEn = $this->objFromFixture(Page::class, 'root_en');
        $pageDe = $pageEn->createTranslation('de_DE');

        $translatorDe = $this->objFromFixture(Member::class, 'translator_de');
        $translatorEn = $this->objFromFixture(Member::class, 'translator_en');

        $this->assertFalse($pageEn->canEdit($translatorDe));
        $this->assertTrue($pageEn->canEdit($translatorEn));
    }
}
