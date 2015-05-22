# CakePHP 3.x Testbed

A demonstration CakePHP 3.x codebase to illustrate and work through some limitations and my own unfamiliarity with Cake 3.


## Installation

1. `git clone git@github.com:beporter/cake3-tests.git`
1. `cd cake3-tests`
1. `composer install`
1. `vendor/bin/phpunit`  # This will show failing tests from below.



## Current Issues

**tl;dr: See [`PostsTableTest::testFindCommenterAndFindRecent()`]().**

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

**tl;dr: See [`PostsTableTest::testResultSetExtract()`]().**

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


## License

MIT

