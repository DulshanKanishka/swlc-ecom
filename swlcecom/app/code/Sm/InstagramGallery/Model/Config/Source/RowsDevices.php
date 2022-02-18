<?php
/*------------------------------------------------------------------------
# SM Super Categories - Version 2.1.0
# Copyright (c) 2015 YouTech Company. All Rights Reserved.
# @license - Copyrighted Commercial Software
# Author: YouTech Company
# Websites: http://www.magentech.com
-------------------------------------------------------------------------*/

namespace Sm\InstagramGallery\Model\Config\Source;

class RowsDevices implements \Magento\Framework\Option\ArrayInterface
{
	public function toOptionArray()
	{
		$array = array(1,2,3,4,5,6,7,8,9,10);
		$data = [];
		foreach ($array as $a)
		{
			$data[] = ['value' => $a, 'label' => __($a)];
		}
		$option =  [];
		foreach ($data as $d)
		{
			$option[] = $d;
		}
		return $option;
	}
}
