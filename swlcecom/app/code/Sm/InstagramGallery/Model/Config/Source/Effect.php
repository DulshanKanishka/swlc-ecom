<?php
/*------------------------------------------------------------------------
# SM Instagram Gallery - Version 2.0.1
# Copyright (c) 2016 YouTech Company. All Rights Reserved.
# @license - Copyrighted Commercial Software
# Author: YouTech Company
# Websites: http://www.magentech.com
-------------------------------------------------------------------------*/

namespace Sm\TwitterSlider\Model\Config\Source;

class Effect implements \Magento\Framework\Option\ArrayInterface
{
	public function toOptionArray()
	{
		return [
			['value' => 'slide', 'label' => __('Slide')],
			['value' => 'fade', 'label' => __('Fade')]
		];
	}
}