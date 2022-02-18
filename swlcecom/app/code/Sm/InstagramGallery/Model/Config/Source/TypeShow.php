<?php
/*------------------------------------------------------------------------
# SM Super Categories - Version 2.1.0
# Copyright (c) 2015 YouTech Company. All Rights Reserved.
# @license - Copyrighted Commercial Software
# Author: YouTech Company
# Websites: http://www.magentech.com
-------------------------------------------------------------------------*/

namespace Sm\InstagramGallery\Model\Config\Source;

class TypeShow implements \Magento\Framework\Option\ArrayInterface
{
	public function toOptionArray()
	{
		return [
			['value'=>'simple', 'label'=>__('Simple')],
			['value'=>'slider', 'label'=>__('Slider')]
		];
	}
}