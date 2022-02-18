<?php
/*------------------------------------------------------------------------
# SM Aloza
# Copyright (c) 2016 YouTech Company. All Rights Reserved.
# @license - Copyrighted Commercial Software
# Author: YouTech Company
# Websites: http://www.magentech.com
-------------------------------------------------------------------------*/

namespace Sm\Aloza\Model\Config\Source;

class ListLayoutStyles implements \Magento\Framework\Option\ArrayInterface
{
	public function toOptionArray()
	{
		return [
			['value' => 'wide', 'label' => __('Wide')],
			['value' => 'boxed', 'label' => __('Boxed')],
			['value' => 'full_width', 'label' => __('Full Width')],
		];
	}
}