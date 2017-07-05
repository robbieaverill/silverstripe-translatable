<?php

namespace SilverStripe\Translatable\Tests;

use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Search\ContentControllerSearchExtension;
use SilverStripe\CMS\Search\SearchForm;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Search\FulltextSearchable;
use SilverStripe\Security\Member;
use SilverStripe\Translatable\Model\Translatable;

/**
 * @package translatable
 */
class TranslatableSearchFormTest extends FunctionalTest
{
    protected static $fixture_file = 'translatable/tests/unit/TranslatableSearchFormTest.yml';

    protected $mockController;

    protected static $required_extensions = [
        SiteTree::class => [
            Translatable::class,
            FulltextSearchable::class . "('Title,MenuTitle,Content,MetaDescription')",
        ],
        ContentController::class => [
            ContentControllerSearchExtension::class,
        ],
    ];

    /**
     * @see {@link ZZZSearchFormTest}
     */
    protected function waitUntilIndexingFinished()
    {
        $schema = DB::get_schema();
        if (method_exists($schema, 'waitUntilIndexingFinished')) {
            $schema->waitUntilIndexingFinished();
        }
    }

    public static function setUpBeforeClass()
    {
        static::start();
        // HACK Postgres doesn't refresh TSearch indexes when the schema changes after CREATE TABLE
        // MySQL will need a different table type
        static::$tempDB->kill();
        FulltextSearchable::enable();
        static::$tempDB->build();
        static::resetDBSchema(true, true);
        parent::setUpBeforeClass();
    }

    protected function setUp()
    {
        parent::setUp();

        $holderPage = $this->objFromFixture(SiteTree::class, 'searchformholder');
        $this->mockController = new ContentController($holderPage);

        // whenever a translation is created, canTranslate() is checked
        $admin = $this->objFromFixture(Member::class, 'admin');
        $admin->logIn();

        $this->waitUntilIndexingFinished();
    }

    public function testPublishedPagesMatchedByTitleInDefaultLanguage()
    {
        $sf = new SearchForm($this->mockController, SearchForm::class);

        $publishedPage = $this->objFromFixture(SiteTree::class, 'publishedPage');
        $publishedPage->copyVersionToStage('Stage', 'Live');
        $translatedPublishedPage = $publishedPage->createTranslation('de_DE');
        $translatedPublishedPage->Title = 'translatedPublishedPage';
        $translatedPublishedPage->Content = 'German content';
        $translatedPublishedPage->write();
        $translatedPublishedPage->copyVersionToStage('Stage', 'Live');

        $this->waitUntilIndexingFinished();

        // Translatable::set_current_locale() can't be used because the context
        // from the holder is not present here - we set the language explicitly
        // through a pseudo GET variable in getResults()

        $lang = 'en_US';
        $results = $sf->getResults(null, array('Search' => 'content', 'searchlocale' => $lang));
        $this->assertContains(
            $publishedPage->ID,
            $results->column('ID'),
            'Published pages are found by searchform in default language'
        );
        $this->assertNotContains(
            $translatedPublishedPage->ID,
            $results->column('ID'),
            'Published pages in another language are not found when searching in default language'
        );

        $lang = 'de_DE';
        $results = $sf->getResults(null, array('Search' => 'content', 'searchlocale' => $lang));
        $this->assertNotContains(
            $publishedPage->ID,
            $results->column('ID'),
            'Published pages in default language are not found when searching in another language'
        );
        $actual = $results->column('ID');
        array_walk($actual, 'intval');
        $this->assertContains(
            (int)$translatedPublishedPage->ID,
            $actual,
            'Published pages in another language are found when searching in this language'
        );
    }
}
