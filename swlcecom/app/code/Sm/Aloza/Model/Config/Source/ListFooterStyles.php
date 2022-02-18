<?php
/*------------------------------------------------------------------------
# SM Aloza - Version 1.0.0
# Copyright (c) 2016 YouTech Company. All Rights Reserved.
# @license - Copyrighted Commercial Software
# Author: YouTech Company
# Websites: http://www.magentech.com
-------------------------------------------------------------------------*/

namespace Sm\Aloza\Model\Config\Source;

class ListFooterStyles implements \Magento\Framework\Option\ArrayInterface
{
	public function toOptionArray()
	{
		return [
			['value' => 'footer-1', 'label' => __('Footer Style 1')],
			['value' => 'footer-2', 'label' => __('Footer Style 2')]

		];
	}
}