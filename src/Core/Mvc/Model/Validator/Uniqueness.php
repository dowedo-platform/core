<?php
namespace Carter\Core\Mvc\Model\Validator;

use Phalcon\Mvc\Model\Validator\Uniqueness as PhalconUniqueness;
use Phalcon\Mvc\Model\ValidatorInterface;
use Phalcon\Mvc\ModelInterface;

class Uniqueness extends PhalconUniqueness implements ValidatorInterface
{
    /**
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return bool
     */
    public function validate(ModelInterface $model)
    {
        $conditions = $this->getOption('conditions');
        $bind = $this->getOption('bind');
        if (!$conditions && !$bind) {
            return parent::validate($model);
        }


        $operator = $this->getOption('operator');
        $operator = $operator ? $operator : 'AND';
        $field = $this->getOption('field');
        $conditionString = "$field = ?0 $operator ";
        $conditionString .= $conditions;
        $bindArray = array($model->$field);
        $bindArray += $bind;
        $item = $model->findFirst(
            array(
                'conditions' => $conditionString,
                'bind' => $bindArray
            )
        );

        if ($item) {
            $this->appendMessage(sprintf('Field %s not unique', $field));
            return false;
        }
        return true;
    }
}
