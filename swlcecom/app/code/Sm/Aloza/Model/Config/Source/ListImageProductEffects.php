<?php
/*------------------------------------------------------------------------
# SM Aloza
# Copyright (c) 2016 YouTech Company. All Rights Reserved.
# @license - Copyrighted Commercial Software
# Author: YouTech Company
# Websites: http://www.magentech.com
-------------------------------------------------------------------------*/

namespace Sm\Aloza\Model\Config\Source;

class ListImageProductEffects implements \Magento\Framework\Option\ArrayInterface
{
	public function toOptionArray()
	{
		return [
			['value' => 'simple', 'label' => __('Simple')],
			['value' => 'opacity', 'label' => __('Opacity')],
			['value' => 'rotate', 'label' => __('Rotate')],
			['value' => 'second-image', 'label' => __('Show second image')],
		];
	}
}

