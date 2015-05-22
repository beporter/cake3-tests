<?php
namespace App\Model\Table;

use App\Model\Entity\Post;
use Cake\I18n\Time;
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
     * Custom finder that filters associated Comments to a given `author`.
     *
     * Used by admins to review a commenter's contributions across all
     * Posts to look for signs of harrassing behavior.
     *
     * Usage: `$Posts->find('commenter', ['John Doe']);`
     *
     * @param \Cake\ORM\Query $query The query to find with.
     * @param  array $options First element expected to be a Comment.author.
     * @return \Cake\ORM\Query The modified query.
     */
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

    /**
     * Custom finder that filters Comments on all Posts to within the last week.
     *
     * Usage: `$Posts->find('recent');`
     *
     * @param \Cake\ORM\Query $query The query to find with.
     * @param  array $options First element expected to be a Comment.author.
     * @return \Cake\ORM\Query The modified query.
     */
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
