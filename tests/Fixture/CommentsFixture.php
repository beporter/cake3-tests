<?php
namespace App\Test\Fixture;

use Cake\I18n\Time;
use Cake\TestSuite\Fixture\TestFixture;

/**
 * CommentsFixture
 *
 */
class CommentsFixture extends TestFixture
{

    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'integer', 'length' => 11, 'unsigned' => true, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
        'post_id' => ['type' => 'integer', 'length' => 11, 'unsigned' => true, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'author' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => '', 'comment' => '', 'precision' => null, 'fixed' => null],
        'body' => ['type' => 'text', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'published_date' => ['type' => 'date', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
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
            'id' => 10,
            'post_id' => 1,
            'author' => 'John Doe',
            'body' => 'Like this.',
            'published_date' => '15 days ago',
        ],
        [
            'id' => 11,
            'post_id' => 1,
            'author' => 'Jane Doe',
            'body' => 'What a great post.',
            'published_date' => '2 days ago',
        ],
        [
            'id' => 12,
            'post_id' => 3,
            'author' => 'John Doe',
            'body' => 'I really mean it.',
            'published_date' => '1 days ago',
        ],
        [
            'id' => 13,
            'post_id' => 3,
            'author' => 'John Doe',
            'body' => 'I really mean it.',
            'published_date' => '14 days ago',
        ],
    ];

    /**
     * Update published dates to be older than a week ago and within the
     * last week.
     *
     * @var array
     */
    public function __construct() {
        foreach ($this->records as $i => $r) {
            $this->records[$i]['published_date'] = new Time($r['published_date']);
        }
        parent::__construct();
    }
}
