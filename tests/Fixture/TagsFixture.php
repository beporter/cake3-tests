<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * TagsFixture
 *
 */
class TagsFixture extends TestFixture
{

    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'integer', 'length' => 11, 'unsigned' => true, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
        'name' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => '', 'comment' => 'Display name of the Tag.', 'precision' => null, 'fixed' => null],
        'is_sponsored' => ['type' => 'boolean', 'length' => null, 'null' => false, 'default' => '0', 'comment' => 'Like StackOverflow, some tags can come from sponsors and need to have extra visual chrome applied.', 'precision' => null],
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
            'name' => 'PHPUnit',
            'is_sponsored' => 0,
        ],
        [
            'id' => 2,
            'name' => 'bugs',
            'is_sponsored' => 0,
        ],
        [
            'id' => 3,
            'name' => 'orm',
            'is_sponsored' => 0,
        ],
        [
            'id' => 4,
            'name' => 'Loadsys',
            'is_sponsored' => 1,
        ],
        [
            'id' => 5,
            'name' => 'CakePHP',
            'is_sponsored' => 1,
        ],
    ];
}
