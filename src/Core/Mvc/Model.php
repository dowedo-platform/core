<?php
/**
 * Model的基类，用于实现读写分类和换成的集中处理
 *
 * Created by PhpStorm.
 * User: Xueron
 * Date: 2015/7/16
 * Time: 13:06
 */

namespace Dowedo\Core\Mvc;

use Dowedo\Core\Mvc\Model\Behavior\CheckCode;
use Phalcon\Mvc\Model as AbstractModel;
use Phalcon\Mvc\Model\Behavior\Timestampable;
use Phalcon\Paginator\Adapter\QueryBuilder;

// Globally set model params
AbstractModel::setup(
    array(
        'notNullValidations' => false,
        'exceptionOnFailedSave' => true
    )
);

/**
 * Class Model
 * @package Dowedo\Core\Mvc
 */
abstract class Model extends AbstractModel
{
    /**
     * Create Instance
     * @return static
     */
    public static function getInstance()
    {
        $className = get_called_class();
        return new $className();
    }

    /**
     * Allows to query a set of records that match the specified conditions
     * @param    string|array parameters
     * @return   static[]
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param string|array parameters
     * @return static
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

    /**
     * Find One By Id with forUpdate option supported
     *
     * @param int $id
     * @param bool $forUpdate
     * @return static
     */
    public static function findOne($id, $forUpdate = null)
    {
        $params = [
            'id = :id:',
            'bind' => ['id' => $id],
            'limit' => 1
        ];
        if ($forUpdate != null) {
            $params['for_update'] = true;
        }

        return parent::findFirst($params);
    }

    /**
     * 临时替代方案,读写分离的时候会用到. 解决在调用 findFirst 的时候需要 for update 的问题.
     *
     * @return \Phalcon\Db\AdapterInterface
     */
    public function selectReadConnection()
    {
        // 如果当前存在事务,则将当前的读取操作置入事务中
        if (di("transactionManager")->has()) {
            $transaction = di("transactionManager")->get();
            return $transaction->getConnection();
        }

        // 默认返回配置的读链接
        return $this->getReadConnection();
    }

    /**
     * 初始化，只在model创建之初调用
     */
    public function initialize()
    {
        // 读写分离的实现，如果有只读实例，则将读取连接指向只读实例
        if (di()->has('dbRead')) {
            $this->setReadConnectionService('dbRead');
        }

        // 自动更新时间戳字段
        $this->addBehavior(new Timestampable([
                'beforeCreate' => [
                    'field' => ['create_time', 'update_time'],
                    'format' => 'Y-m-d H:i:s'
                ],
                'beforeUpdate' => [
                    'field' => 'update_time',
                    'format' => 'Y-m-d H:i:s'
                ]
            ])
        );

        // 自动计算校验码
        $this->addBehavior(new CheckCode());

        // 动态更新，保存镜像
        $this->useDynamicUpdate(true);
        $this->keepSnapshots(true);

        // 加载外部注入的关系
        $this->loadRelations();
    }

    /**
     * 注入外部定义的关系
     *
     * @return $this
     */
    public function loadRelations()
    {
        $relations = di('config')->get('relations', []);
        foreach ($relations as $relation) {
            if ($this instanceof $relation['model']) {
                $relationType = $relation['relationType'];
                call_user_func_array(array($this, $relationType), $relation['parameters']->toArray());
            }
        }

        return $this;
    }

    /**
     * 验证校验码是否正确
     * @return bool
     * @throws AbstractModel\Exception
     */
    public function isCheckCodeValid()
    {
        $checkCodeColumn = 'check_code';

        $metaData = di('modelsMetadata');
        $attributes = $metaData->getAttributes($this);
        if (!in_array($checkCodeColumn, $attributes)) {
            return true;
        }

        $salt = di('config')->app->checkCodeSalt;
        if (!isset($salt) || empty($salt)) {
            throw new AbstractModel\Exception('checkCodeSalt must be set in config for calculate check code.');
        }

        $string = 'check_code';
        foreach ($attributes as $column) {
            if ($column == 'id' || $column == $checkCodeColumn) {
                continue;
            }
            $value = $this->readAttribute($column);
            $string = $string . '|' . (string)$value;
        }
        $string = $string . '|' . $salt;
        $checkCode = md5($string);

        return $checkCode == $this->readAttribute($checkCodeColumn);
    }

    /**
     * @param array $params
     * @param array $options
     * @return \stdClass
     */
    public function pagedQuery(array $params = [], array $options = [])
    {
        if (isset($options['limit'])) {
            $limit = (int)$options['limit'];
        } else {
            $limit = (int)di('config')->app->pageSize;
        }

        if (isset($options['page'])) {
            $page = (int)$options['page'];
        } else {
            $page = 1;
        }

        // create QueryBuilder
        $builder = $this->createBuilder($params);

        //
        $queryBuild = new QueryBuilder([
            'builder' => $builder,
            'limit' => $limit,
            'page' => $page
        ]);

        //
        return $queryBuild->getPaginate();
    }

    /**
     * @param array $params
     * @param string $alias
     * @return AbstractModel\Query\Builder
     */
    public function createBuilder(array $params = [], $alias = '')
    {
        if (!empty($alias)) {
            $source = [$alias => get_called_class()];
        } else {
            $source = get_called_class();
        }
        return di('modelsManager')->createBuilder($params)->from($source);
    }

    /**
     * 重写 save 方法，如果在事务中，则失败的时候呼叫回滚。
     *
     * @param null $data
     * @param null $whiteList
     * @return bool
     */
    public function save($data = null, $whiteList = null)
    {
        try {
            $result = parent::save($data, $whiteList);

            if ($result == false) {
                if ($this->_transaction != null) {
                    $this->_transaction->rollback($this->getMessage(), $this);
                }
            }
            return $result;
        } catch (\Exception $e) {
            if ($this->_transaction != null) {
                $this->_transaction->rollback($this->getMessage(), $e->getMessage());
            }
            $this->_errorMessages = $e->getMessage();
        }
    }

    /**
     * @return string|void
     */
    public function getMessage()
    {
        $class = get_class($this);
        $messages = $this->getMessages();
        if (is_array($messages)) {
            return "[$class] " . join(";", $messages);
        }
        return $messages;
    }
}