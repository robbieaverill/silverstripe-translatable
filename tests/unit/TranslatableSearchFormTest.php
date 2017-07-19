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
use SilverStripe\Versioned\Versioned;
use SilverStripe\Control\HTTPRequest;

/**
 * @package translatable
 */
class TranslatableSearchFormTest extends FunctionalTest
{
    protected static $fixture_file = 'TranslatableSearchFormTest.yml';

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
        $publishedPage = $this->objFromFixture(SiteTree::class, 'publishedPage');
        $publishedPage->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);

        $translatedPublishedPage = $publishedPage->createTranslation('de_DE');
        $translatedPublishedPage->Title = 'translatedPublishedPage';
        $translatedPublishedPage->Content = 'German content';
        $translatedPublishedPage->write();
        $publishedPage->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);

        $this->waitUntilIndexingFinished();

        $lang = 'en_US';
        $request = new HTTPRequest('GET', 'search', ['Search'=>'content', 'searchlocale' => $lang]);
        $request->setSession($this->session());
        $this->mockController->setRequest($request);
        $sf = new SearchForm($this->mockController);
        $results = $sf->getResults();

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
        $request = new HTTPRequest('GET', 'search', ['Search'=>'content', 'searchlocale' => $lang]);
        $request->setSession($this->session());
        $this->mockController->setRequest($request);
        $sf2 = new SearchForm($this->mockController);
        $results = $sf2->getResults();

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
