<?php
namespace NeoSolax\SalesRule\Model;

class Order extends \Magento\Sales\Model\Order
{
    public function canInvoice()
    {
        if ($this->canUnhold()) {
            return false;
        }
        $state = $this->getState();
        if ($this->isCanceled() || $state === self::STATE_COMPLETE || $state === self::STATE_CLOSED) {
            return false;
        }

        if ($this->getActionFlag(self::ACTION_FLAG_INVOICE) === false) {
            return false;
        }

        foreach ($this->getAllItems() as $item) {
            if ($item->getQtyToInvoice() > 0 && !$item->getLockedDoInvoice()) {
                return true;
            }
        }
        return false;
    }
    protected function _canVoidOrder()
    {
        return !($this->isCanceled() || $this->canUnhold());
    }
}
