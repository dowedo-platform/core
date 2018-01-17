<?php
/**
 * Created by PhpStorm.
 * User: Xueron
 * Date: 2015/8/27
 * Time: 9:46
 */

namespace Carter\Core\Mvc\Model\Transaction;
use Phalcon\Mvc\Model\TransactionInterface;


/**
 * 扩展事务管理器，调试观察内部信息
 *
 * Class Manager
 * @package Carter\Core\Mvc\Model\Transaction
 */
class Manager extends \Phalcon\Mvc\Model\Transaction\Manager
{
    /**
     * @return mixed
     */
    public function getNumber()
    {
        return $this->_number;
    }

    /**
     * @return mixed
     */
    public function getTransactions()
    {
        return $this->_transactions;
    }

    /**
     * @return int
     */
    public function getTransactionCount()
    {
        if (is_array($this->_transactions)) {
            return count($this->_transactions);
        }
        return 0;
    }

    /**
     * @return mixed
     */
    public function isInitialized()
    {
        return $this->_initialized;
    }

    /**
     * Removes transactions from the TransactionManager
     * @param TransactionInterface $transaction
     */
    protected function _collectTransaction(TransactionInterface $transaction)
    {
        $transactions = $this->_transactions;
        if (count($transactions)) {
            $newTransactions = [];
            foreach ($transactions as $managedTransaction) {
                if ($managedTransaction == $transaction) {
                    $this->_number--;
                } else {
                    $newTransactions[] = $managedTransaction;
                }
            }
            $this->_transactions = $newTransactions;
        }
    }
} // END Manager
