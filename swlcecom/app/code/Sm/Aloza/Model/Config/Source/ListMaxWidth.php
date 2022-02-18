<?php
/*------------------------------------------------------------------------
# SM Aloza
# Copyright (c) 2016 YouTech Company. All Rights Reserved.
# @license - Copyrighted Commercial Software
# Author: YouTech Company
# Websites: http://www.magentech.com
-------------------------------------------------------------------------*/

namespace Sm\Aloza\Model\Config\Source;

class ListMaxWidth implements \Magento\Framework\Option\ArrayInterface
{
	public function toOptionArray()
	{
		return [
			['value' => '1170', 'label' => __('1170px')],
			['value' => '1290', 'label' => __('1290px')],
		];
	}
}

