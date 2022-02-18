<?php
/*------------------------------------------------------------------------
# SM Super Categories - Version 2.1.0
# Copyright (c) 2015 YouTech Company. All Rights Reserved.
# @license - Copyrighted Commercial Software
# Author: YouTech Company
# Websites: http://www.magentech.com
-------------------------------------------------------------------------*/

namespace Sm\InstagramGallery\Model\Config\Source;

class TypePopup implements \Magento\Framework\Option\ArrayInterface
{
	public function toOptionArray()
	{
		return [
			['value'=>'thumb', 'label'=>__('Thumbnail')],
			['value'=>'button', 'label'=>__('Button')]

		];
	}
}