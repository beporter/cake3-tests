# CakePHP 3.x Testbed

A demonstration CakePHP 3.x codebase to illustrate and work through some limitations and my own unfamiliarity with Cake 3.


## Installation

1. `git clone git@github.com:beporter/cake3-tests.git`
1. `cd cake3-tests`
1. `composer install`
1. `vendor/bin/phpunit`  # This will show failing tests from below.



## Current Issues

**tl;dr: See [`PostsTableTest::testFindCommenterAndFindRecent()`](https://github.com/beporter/cake3-tests/blob/d33fdfef271171d156e87527afc281cce287fbf5/tests/TestCase/Model/Table/PostsTableTest.php#L148).**

If you know how to resolve any of these issues, please feel free to post an Issue, or if you're feeling really charitable, submit a pull request.

### Composing `contain()` clauses

It doesn't seem to be possible to compose multiple calls to `Query::contain()` together for the same table relationship.

For example, the following code only filters the `Comments` association by `Comments.published_date`, and not **also** by `Comments.author`.

```php
$this->Posts->find()
    ->contain(['Comments' => function ($q) {
        return $q->andWhere([
            'Comments.author' => 'John Doe',
        ]);
    }])
    ->contain(['Comments' => function ($q) {
        return $q->andWhere([
            'Comments.published_date >=' => new Time('7 days ago'),
        ]);
    }]);
```

Now obviously this is a contrived example, but what if we had two different custom finder methods that tried to do this separately?

```php
class PostsTable extends Table
{
    public function findCommenter(Query $query, array $options)
    {
        if (!count($options)) {
        	return $query;
        }
        $authorName = array_shift($options);

        $query->contain(['Comments' => function ($q) use ($authorName) {
            return $q->andWhere([
                'Comments.author' => $authorName,
            ]);
        }]);
        return $query;
    }

    public function findRecent(Query $query, array $options)
    {
        $query->contain(['Comments' => function ($q) {
            return $q->andWhere([
                'Comments.published_date >=' => new Time('7 days ago'),
            ]);
        }]);
        return $query;
    }
}
```

Now when you try to compose these together, you end up with only the _last_ `contain()` used:

`$this->Posts->find('commenter', ['John Doe'])->find('recent');`


### Extracting sub-entity keys

**tl;dr: See [`PostsTableTest::testResultSetExtract()`](https://github.com/beporter/cake3-tests/blob/d33fdfef271171d156e87527afc281cce287fbf5/tests/TestCase/Model/Table/PostsTableTest.php#L179).**

So assume for a moment that the compose query above actually returns a `$resultSet`.

We would have a Collection of `Post` Entities, and inside each one, we'd have a `comments` property that was an array of `Comment` Entities.

In Cake 2, we could have done this:

```php
$authors = Hash::extract($resultSet, '{n}.Comment.{n}.author');
/* Result:
[
    0 => 'John Doe',
    1 => 'Jane Doe',
]
*/
```

But this no longer works in Cake 3. Even though the `ResultSet` class implements the `CollectionInterface` by way of the `CollectionTrait`, the `::extract()` method isn't capable of retrieving values from sub-Entities. In other words, this doesn't work in Cake 3:

```php
$authors = $resultSet->extract('{n}.comments.{n}.author')->toArray();
/* Result:
[
    0 => null,  // <-- bwah?!
    1 => null,
]
*/
```


### Applying [conditions] using the "far" table in a belongsToMany relationship.

**tl;dr: See [`PostsTableTest::testSaveNewPostWithTags()`](https://github.com/beporter/cake3-tests/blob/xxxxx/tests/TestCase/Model/Table/PostsTableTest.php#L190).**

Let's say I `Posts`, and `Tags`. Posts can be assigned many Tags, and Tags can be re-used on many Posts. This is a classic belongsToMany relationship, and is represented in the database using a "glue" table, conventionally named `posts_tags` and containing at minimum a `post_id` and a `tag_id`.

But what if our **Tags** have additional properties? Save for example that like StackOverflow, some of our Tags are "sponsored" and we need to present them in the finished app separately from "unsponsored" Tags.

Well, we could add a boolean field to the Tags table call `is_sponsored` and use it to indicate which "bucket" a Tag belongs to. Then, for the sake of easy filtering and look up, we can define a few custom associations in the `PostsTable`:

```php
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->table('posts');
        $this->displayField('title');
        $this->primaryKey('id');

        $this->hasMany('Comments', [
            'foreignKey' => 'post_id'
        ]);

        // This is the "normal" association, and will contain ALL
        // associated Tags, regardless of the `is_sponsored` value.
        $this->belongsToMany('Tags', [
            'foreignKey' => 'post_id',
            'targetForeignKey' => 'tag_id',
            'joinTable' => 'posts_tags'
        ]);

        // Extra convenience associations. Groups any associated Tags
        // into "sponsored" and "unsponsored" buckets.
        $this->belongsToMany('SponsoredTags', [
            'className' => 'Tags',
            'foreignKey' => 'post_id',
            'targetForeignKey' => 'tag_id',
            'joinTable' => 'posts_tags',
            'conditions' => [
                'SponsoredTags.is_sponsored' => true,
            ],
        ]);
        $this->belongsToMany('UnsponsoredTags', [
            'className' => 'Tags',
            'foreignKey' => 'post_id',
            'targetForeignKey' => 'tag_id',
            'joinTable' => 'posts_tags',
            'conditions' => [
                'SponsoredTags.is_sponsored' => false,
            ],
        ]);
    }
```

These relationships give us nice lists of Tags that are already pre-sorted by whether they are sponsored or not.

We must remember to also make these accessible in our Entity:

```php
class Post extends Entity
{

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array
     */
    protected $_accessible = [
        'title' => true,
        'body' => true,
        'comments' => true,
        'tags' => true,
        'sponsored_tags' => true,  // NEW!
        'unsponsored_tags' => true,  // NEW!
    ];
}
```

So here's where the issue comes in: When you try to **save** these relationships, you're going to run problems.

Take this example request data array:

```php
$data = [
    'title' => 'Post with sponsored and unsponsored tags',
    'body' => 'This demonstrates request data where default (baked) multi-select inputs have been used for `sponsored_tags._ids` and `unsponsored_tags._ids`.',
    'sponsored_tags' => [
        '_ids' => [
            4, // Loadsys
        ],
    ],
    'unsponsored_tags' => [
        '_ids' => [
            2, // bugs
            3, // orm
        ],
    ],
];
```

In our controller, we would of course have to remember to tell the ORM that we want it to save this related data:

```php
$entityOptions = [
    'associated' => ['SponsoredTags', 'UnsponsoredTags'],
];
$entity = $this->Posts->newEntity($data, $entityOptions);
```

And then we save it:

```php
$result = $this->Posts->save($entity);
```

...which produces the following error:

```shell
PDOException: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'SponsoredTags.is_sponsored' in 'where clause'

ROOT/vendor/cakephp/cakephp/src/Database/Statement/MysqlStatement.php:36
ROOT/vendor/cakephp/cakephp/src/Database/Connection.php:270
ROOT/vendor/cakephp/cakephp/src/Database/Query.php:174
ROOT/vendor/cakephp/cakephp/src/ORM/Query.php:872
ROOT/vendor/cakephp/cakephp/src/Datasource/QueryTrait.php:272
ROOT/vendor/cakephp/cakephp/src/ORM/Query.php:823
ROOT/vendor/cakephp/cakephp/src/Datasource/QueryTrait.php:131
ROOT/vendor/cakephp/cakephp/src/ORM/Association/BelongsToMany.php:816
ROOT/vendor/cakephp/cakephp/src/ORM/Association/BelongsToMany.php:765
ROOT/vendor/cakephp/cakephp/src/Database/Connection.php:557
ROOT/vendor/cakephp/cakephp/src/ORM/Association/BelongsToMany.php:786
ROOT/vendor/cakephp/cakephp/src/ORM/Association/BelongsToMany.php:463
ROOT/vendor/cakephp/cakephp/src/ORM/AssociationCollection.php:251
ROOT/vendor/cakephp/cakephp/src/ORM/AssociationCollection.php:227
ROOT/vendor/cakephp/cakephp/src/ORM/AssociationCollection.php:192
ROOT/vendor/cakephp/cakephp/src/ORM/Table.php:1457
ROOT/vendor/cakephp/cakephp/src/ORM/Table.php:1377
ROOT/vendor/cakephp/cakephp/src/Database/Connection.php:557
ROOT/vendor/cakephp/cakephp/src/ORM/Table.php:1378
ROOT/tests/TestCase/Model/Table/PostsTableTest.php:214
```

...which obviously is wrong and bad.

The source of this error is in [`\Cake\ORM\Assoiation\BelongsToMany::replaceLinks()`](https://github.com/cakephp/cakephp/blob/084ef76/src/ORM/Association/BelongsToMany.php#L761):

```php
    public function replaceLinks(EntityInterface $sourceEntity, array $targetEntities, array $options = [])
    {
        $bindingKey = (array)$this->bindingKey();
        $primaryValue = $sourceEntity->extract($bindingKey);
        if (count(array_filter($primaryValue, 'strlen')) !== count($bindingKey)) {
            $message = 'Could not find primary key value for source entity';
            throw new InvalidArgumentException($message);
        }
        return $this->junction()->connection()->transactional(
            function () use ($sourceEntity, $targetEntities, $primaryValue, $options) {
                $foreignKey = (array)$this->foreignKey();
                $hasMany = $this->source()->association($this->_junctionTable->alias());
                $existing = $hasMany->find('all')
                    ->where(array_combine($foreignKey, $primaryValue));
                $associationConditions = $this->conditions();
                if ($associationConditions) {
                    $existing->andWhere($associationConditions);  // <--- !! RIGHT HERE !!
                }
                $jointEntities = $this->_collectJointEntities($sourceEntity, $targetEntities);
                $inserts = $this->_diffLinks($existing, $jointEntities, $targetEntities);
                if ($inserts && !$this->_saveTarget($sourceEntity, $inserts, $options)) {
                    return false;
                }
                $property = $this->property();
                if (count($inserts)) {
                    $inserted = array_combine(
                        array_keys($inserts),
                        (array)$sourceEntity->get($property)
                    );
                    $targetEntities = $inserted + $targetEntities;
                }
                ksort($targetEntities);
                $sourceEntity->set($property, array_values($targetEntities));
                $sourceEntity->dirty($property, false);
                return true;
            }
        );
    }
```

This method is intended to delete, add or update any records in the join table in order to make them "match" with the set of IDs provided in our `Table::save()` call. The conditions in this case are **necessary**. Without them, we'd wipe out any existing `unsponsored` link records when we saved the updated list of `sponsored` records, and vice versa. We need to make sure we **only** operate on those `PostsTags` records where the `post_id` matches our new record from the `Table::save()`, but _also_ where the associated `Tag.is_sponsored` is either specifically `true` or `false`.

The solution seems to be that `::replaceLinks()` needs to `->contain()` the necessary tables when they are detected in the `$associationConditions` array. (Doing so every time may be ill-advised for a number of reasons.



## License

MIT

