/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license sliderConfig is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category  Mageplaza
 * @package   Mageplaza_MassOrderActions
 * @copyright Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license   https://www.mageplaza.com/LICENSE.txt
 */
var config = {
    'config': {
        'mixins': {
            'Magento_Ui/js/grid/massactions': {
                'Mageplaza_MassOrderActions/js/grid/massactions': true
            },
            'Magento_Sales/js/grid/tree-massactions': {
                'Mageplaza_MassOrderActions/js/grid/massactions': true
            }
        }
    }
};
