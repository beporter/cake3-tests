<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * PostsFixture
 *
 */
class PostsFixture extends TestFixture
{

    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'integer', 'length' => 11, 'unsigned' => true, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
        'title' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => '', 'comment' => '', 'precision' => null, 'fixed' => null],
        'body' => ['type' => 'text', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB',
            'collation' => 'utf8_general_ci'
        ],
    ];
    // @codingStandardsIgnoreEnd

    /**
     * Records
     *
     * @var array
     */
    public $records = [
        [
            'id' => 1,
            'title' => 'First Post',
            'body' => 'This is a post. It has comments from multiple authors.'
        ],
        [
            'id' => 2,
            'title' => 'Post with No Comments',
            'body' => 'This is another post that has zero comments.'
        ],
        [
            'id' => 3,
            'title' => 'Post with Comments from Single author',
            'body' => 'This Post has comments exclusively from a single author.'
        ],

        [
            'id' => 4,
            'title' => 'Cake 3 belongsToMany with conditions',
            'body' => 'This post has multiple Tags associated with it.

            Some are "sponsored" and some are not. To make it easy to "group" these Tags into two buckets, the PostsTable defines two "convenience" associations in addition to the default `belongsToMany(Tags)` relationship: `SponsoredTags` and `UnsponsoredTags`

            If a belongsToMany relationship tries to use a [conditions] clause that uses a field from the "far" table (in this case, `Tags.is_sponsored`, then the `BelongsToMany::replaceLinks()` call will fail because that condition will be added into the SELECT looking for existing joining records, but the two joined tables (`Posts` and `Tags`) will not be included in the SELECT query.'
        ],
    ];
}
