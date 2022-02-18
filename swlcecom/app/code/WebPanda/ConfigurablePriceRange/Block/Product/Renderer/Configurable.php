<?php
namespace WebPanda\ConfigurablePriceRange\Block\Product\Renderer;

/**
 * Class Configurable
 * @package WebPanda\ConfigurablePriceRange\Block\Product\Renderer
 */
class Configurable extends \Magento\Swatches\Block\Product\Renderer\Configurable
{
    /**
     * Path to template file with Swatch renderer.
     */
    const SWATCH_RENDERER_TEMPLATE = 'WebPanda_ConfigurablePriceRange::product/view/swatch/renderer.phtml';

    /**
     * Path to default template file with standard Configurable renderer.
     */
    const CONFIGURABLE_RENDERER_TEMPLATE = 'WebPanda_ConfigurablePriceRange::product/view/configurable/renderer.phtml';

    /**
     * {@inheritdoc}
     */
    protected function getRendererTemplate()
    {
        return $this->isProductHasSwatchAttribute() ? self::SWATCH_RENDERER_TEMPLATE : self::CONFIGURABLE_RENDERER_TEMPLATE;
    }
}
