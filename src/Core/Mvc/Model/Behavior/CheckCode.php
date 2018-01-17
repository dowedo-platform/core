<?php
/**
 * CheckCode.php -- 计算校验码
 *
 * @copyright   Copyright 2009-2015 江苏麦金莱网络科技有限公司
 * @author      Shurong Ni <nishurong@youbangsoft.com>
 * @package     dolphin
 * @version     $Id: $
 * @link        http://www.ruyidai.cn
 */
namespace Carter\Core\Mvc\Model\Behavior;
use Phalcon\Mvc\Model\Behavior;
use Phalcon\Mvc\Model\BehaviorInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Mvc\Model\MetaData;
use Phalcon\Mvc\ModelInterface;

class CheckCode extends Behavior implements BehaviorInterface
{
    /**
     * Events which this behavior accepts.
     *
     * @var array
     */
    protected $acceptedEvents = [
        'beforeValidationOnCreate',
        'beforeValidationOnUpdate',
        'beforeUpdate',
        'beforeCreate'
    ];

    public function notify($eventType, ModelInterface $model)
    {
        // const
        $checkCodeColumn = 'check_code';

        if (!in_array($eventType, $this->acceptedEvents)) {
            return;
        }

        /* @var $metaData MetaData */
        $metaData = di('modelsMetadata');
        $attributes = $metaData->getAttributes($model);
        if (!in_array($checkCodeColumn, $attributes)) {
            return;
        }

        $salt = di('config')->app->checkCodeSalt;
        if (!isset($salt) || empty($salt)) {
            throw new Exception('checkCodeSalt must be set in config for calculate check code.');
        }

        $string = 'check_code';
        foreach ($attributes as $column) {
            if ($column == 'id' || $column == $checkCodeColumn) { // calc until check_code;
                continue;
            }
            $value = $model->readAttribute($column);
            $string = $string . '|' . (string) $value;
        }
        $string = $string . '|' . $salt;
        $checkCode = md5($string);
        $model->writeAttribute($checkCodeColumn, $checkCode);
    }
}