<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\PostsTable;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;

/**
 * App\Model\Table\PostsTable Test Case
 */
class PostsTableTest extends TestCase
{

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.posts',
        'app.comments'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::exists('Posts') ? [] : ['className' => 'App\Model\Table\PostsTable'];
        $this->Posts = TableRegistry::get('Posts', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Posts);
        parent::tearDown();
    }

    /**
     * Test the custom findCommenter() method with a valid Comment.author.
     *
     * @return void
     */
    public function testFindCommenterWithValidAuthor()
    {
        $author = 'John Doe';
        $results = $this->Posts->find('commenter', [$author])->hydrate(false)->all();
        $this->assertEquals(
            $this->Posts->find()->count(),
            $results->count(),
            'All Posts fixture records should be returned regardless of comments.'
        );

        $authors = Hash::extract($results->toArray(), '{n}.comments.{n}.author');
        $this->assertTrue(
            array_reduce($authors, function ($carry, $v) {
                if (!$carry) {
                	return false;
                }
                return ($v === 'John Doe');
            }, true),
            'Every associated Comment should be from the expected Author.'
        );
    }

    /**
     * Test the custom findCommenter() method with an invalid Comment.author.
     *
     * @return void
     */
    public function testFindCommenterWithInvalidAuthor()
    {
        $author = 'Not In Fixtures';
        $results = $this->Posts->find('commenter', [$author])->hydrate(false)->all();
        $this->assertEquals(
            $this->Posts->find()->count(),
            $results->count(),
            'All Posts fixture records should be returned regardless of comments.'
        );

        $authors = Hash::extract($results->toArray(), '{n}.comments.{n}.author');
        $this->assertEquals(
            0,
            count($authors),
            'There should be zero Comment.authors returned for an invalid lookup value.'
        );
    }

    /**
     * Test the custom findRecent() method.
     *
     * @return void
     */
    public function testFindRecent()
    {
        $results = $this->Posts->find('recent')->hydrate(false)->all();
        $this->assertEquals(
            $this->Posts->find()->count(),
            $results->count(),
            'All Posts fixture records should be returned regardless of comments.'
        );

        $commentDates = Hash::extract($results->toArray(), '{n}.comments.{n}.published_date');
        $this->assertTrue(
            array_reduce($commentDates, function ($carry, $v) {
                if (!$carry) {
                	return false;
                }
                return ($v >= new Time('7 days ago'));
            }, true),
            'Every associated Comment should have been published within the last 7 days.'
        );
    }

    /**
     * Test the composition of both findCommenter() and findRecent().
     *
     * @return void
     */
    public function testFindCommenterAndFindRecent()
    {
        $author = 'Jane Doe';
        $results = $this->Posts
            ->find('commenter', [$author]) // The order matters here.
            ->find('recent') // This will wipe out the containment from findCommenter().
            ->hydrate(false)->all();
        $this->assertEquals(
            $this->Posts->find()->count(),
            $results->count(),
            'All Posts fixture records should be returned regardless of comments.'
        );

        $authors = Hash::extract($results->toArray(), '{n}.comments.{n}.author');
        foreach ($authors as $result) {
            $this->assertEquals(
                $author,
                $result,
                'Every associated Comment should be from the single expected Author, but this fails because the contain(Comments) from findCommenter() has been wiped out by the contain(Comments) from findRecent().'
            );
        }

        $commentDates = Hash::extract($results->toArray(), '{n}.comments.{n}.published_date');
        $cutOff = new Time('7 days ago');
        foreach ($commentDates as $result) {
            $this->assertTrue(
                ($result >= $cutoff),
                'Every associated Comment should have been published within the last 7 days.'
            );
        }
    }

    /**
     * Test ResultSet::extract() on sub-Entities.
     *
     * @return void
     */
    public function testResultSetExtract()
    {
        $expected = [
            0 => 'John Doe',
            1 => 'Jane Doe',
        ];

        $results = $this->Posts->find()->all();

        $this->assertEquals(
            $expected,
            $results->extract('{n}.comments.{n}.author')->toArray(),
            'Why doesn\'t CollectionTrait::extract() work on sub-Entities?? What incantation is necessary to make this work?'
        );
    }
}
