<?php
namespace App\Model\Table;

use App\Model\Entity\Post;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Posts Model
 */
class PostsTable extends Table
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        $this->table('posts');
        $this->displayField('title');
        $this->primaryKey('id');
        $this->hasMany('Comments', [
            'foreignKey' => 'post_id'
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->add('id', 'valid', ['rule' => 'numeric'])
            ->allowEmpty('id', 'create');

        $validator
            ->requirePresence('title', 'create')
            ->notEmpty('title');

        $validator
            ->allowEmpty('body');

        return $validator;
    }

    /**
     * Custom finder that selects Posts with Comments from a given `author`.
     *
     * Usage: `$Post->find('author', ['John Doe']);`
     *
     * @param \Cake\ORM\Query $query The query to find with.
     * @param  array $options First element expected to be a Comment.author.
     * @return \Cake\ORM\Query The modified query.
     */
     */
    public function findAuthor(Query $query, array $options)
    {
        if (!count($options)) {
        	return $query;
        }
        $authorName = array_shift($options);

        $query->contain(['Comments' => function ($q) use ($authorName) {
            return $q->find('all', ['Comments.author' => $authorName]);
        }]);
        return $query;

    }
}
