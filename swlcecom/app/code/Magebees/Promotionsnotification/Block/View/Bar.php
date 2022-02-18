<?php
namespace Magebees\Promotionsnotification\Block\View;

class Bar extends \Magebees\Promotionsnotification\Block\View
{
    public function getNotificationCollection($mode = "bar")
    {
        $notification_collection = parent::getNotificationCollection($mode);
        return $notification_collection;
    }
    
    public function addTop()
    {
        if ($this->getDisplayPosition()=="top") {
            $this->setTemplate('Magebees_Promotionsnotification::bar.phtml');
        }
    }
    
    public function addBottom()
    {
        if ($this->getDisplayPosition()=="bottom") {
            $this->setTemplate('Magebees_Promotionsnotification::bar.phtml');
        }
    }
}
