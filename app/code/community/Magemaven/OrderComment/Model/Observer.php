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
class Magemaven_OrderComment_Model_Observer extends Varien_Object
{
    /**
     * Current comment
     *
     * @var bool|string
     */
    protected $_currentComment = false;

    /**
     * Save comment from agreement form to order
     *
     * @param   $observer
     * @return  void
     */
    public function saveOrderComment($observer)
    {
        $orderComment = Mage::app()->getRequest()->getPost('ordercomment');
        if (is_array($orderComment) && isset($orderComment['comment'])) {
            $comment = trim($orderComment['comment']);
            $comment = nl2br(Mage::helper('ordercomment')->stripTags($comment));

            if (!empty($comment)) {
                $order = $observer->getEvent()->getOrder();
                $order->setCustomerNoteNotify(true);
                $order->setCustomerNote($comment);
                $this->_currentComment = $comment;
            }
        }
    }

    /**
     * Show customer comment in 'My Account'
     *
     * @param   $observer
     * @return  void
     */
    public function setOrderCommentVisibility($observer)
    {
        if ($this->_currentComment) {
            $statusHistory = $observer->getEvent()->getStatusHistory();

            if ($statusHistory && $this->_currentComment == $statusHistory->getComment()) {
                $statusHistory->setIsVisibleOnFront(1);
            }
        }
    }

    /**
     * Adds column to admin sales order grid
     *
     * @param   Varien_Event_Observer $observer
     * @return  void
     */
    public function appendColumnToSalesOrderGrid(Varien_Event_Observer $observer)
    {
        $block = $observer->getBlock();
        if (!isset($block)) {
            return;
        }

        if ($block->getType() == 'adminhtml/sales_order_grid') {
            /* @var Mage_Adminhtml_Block_Sales_Order_Grid $block */
            $block->addColumnAfter('ordercomment', array(
                'header'        => Mage::helper('ordercomment')->__('Last Comment'),
                'type'          => 'text',
                'index'         => 'customer_note',
                'filter_index'  => 'order.customer_note',
            ), 'status');
        }
    }

    /**
     * Adds join to admin sales order grid collection
     *
     * @param   Varien_Event_Observer $observer
     * @return  void
     */
    public function beforeCollectionLoad(Varien_Event_Observer $observer)
    {
        $request    = Mage::app()->getRequest();
        $controller = $request->getControllerName();
        $action     = $request->getActionName();
        $route      = $request->getRouteName();
        $module     = $request->getModuleName();

        // Mage::log('controller: ' . var_export($controller, true));
        // Mage::log('action: ' . var_export($action, true));
        // Mage::log('route: ' . var_export($route, true));
        // Mage::log('module: ' . var_export($module, true));

        $collection = $observer->getData('order_grid_collection');
        if (!isset($collection)) {
            return;
        }

        /**
         * Mage_Sales_Model_Mysql4_Order_Grid_Collection (1.5.1.0) || Mage_Sales_Model_Resource_Order_Grid_Collection (1.9.1.0)
         */
        if ($collection instanceof Mage_Sales_Model_Mysql4_Order_Grid_Collection
            || $collection instanceof Mage_Sales_Model_Resource_Order_Grid_Collection) {

            /**
             * Fixes error:
             *
             * "You cannot define a correlation name 'order' more than once"
             *
             * when trying to export sales orders to CSV
             */
            if ($controller === 'sales_order' && in_array($action, array('exportCsv', 'exportExcel'))) {
                return;
            }

            /**
             * The join below causes the customer's order tab
             * to choke in the admin. The columns are empty when
             * that particular query runs, so this is a quick method
             * to get around this module's issues.
             */
            if (!empty($collection->getSelect()->getPart('columns'))) {
                $collection->getSelect()->join(
                    array('order' => 'sales_flat_order'), 'order.entity_id = main_table.entity_id', array('order.customer_note')
                );
            }
        }
    }
}
