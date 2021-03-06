<?php
/**
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 * @category    Magemaven
 * @package     Magemaven_OrderComment
 * @copyright   Copyright (c) 2011-2012 Sergey Storchay <r8@r8.com.ua>
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
class Magemaven_OrderComment_Model_Resource_Order_Grid_Collection extends Mage_Sales_Model_Resource_Order_Grid_Collection
{
    /**
     * Init collection select
     *
     * @return  Mage_Core_Model_Resource_Db_Collection_Abstract
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        // map columns to include table alias
        $this->addFilterToMap('increment_id', 'main_table.increment_id');
        $this->addFilterToMap('store_id', 'main_table.store_id');
        $this->addFilterToMap('billing_name', 'main_table.billing_name');
        $this->addFilterToMap('shipping_name', 'main_table.shipping_name');
        $this->addFilterToMap('base_grand_total', 'main_table.base_grand_total');
        $this->addFilterToMap('grand_total', 'main_table.grand_total');
        $this->addFilterToMap('status', 'main_table.status');
        $this->addFilterToMap('created_at', 'main_table.created_at');

        return $this;
    }

    /**
     * Modify collection after load
     *
     * @return  Mage_Core_Model_Resource_Db_Collection_Abstract
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();
        if (count($this->_items) > 0) {
            $ids = array();

            foreach ($this->_items as $item) {
                $ids[] = $item->getId();
            }
            $ids = implode(',', $ids);

            $select = $this->getConnection()
                ->select()
                ->from($this->getTable('sales/order_status_history'))
                ->where("parent_id IN ($ids)")
                ->order('created_at ASC');

            $items = $this->getConnection()->fetchAll($select);

            foreach($items as $item) {
                $parent = $this->_items[$item['parent_id']];
                $parent->setOrdercomment($item['comment']);
            }
        }

        return $this;
    }
}
