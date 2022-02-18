<?php
namespace Magebees\Promotionsnotification\Block\Adminhtml\Promotionsnotification;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Magebees_Promotionsnotification';
        $this->_controller = 'adminhtml_promotionsnotification';

        parent::_construct();

        $this->buttonList->update('save', 'label', __('Save'));
        $this->buttonList->update('delete', 'label', __('Delete'));

        $this->buttonList->add(
            'saveandcontinue',
            [
                'label' => __('Save and Continue Edit'),
                'class' => 'save',
                'data_attribute' => [
                    'mage-init' => ['button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form']]
                ]
            ],
            -100
        );
        
        if ($this->getRequest()->getParam('id')) {
            $this->buttonList->add('preview', [
                'label' => 'Preview',
            ]);
        }
        
        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('promotions_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'promotions_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'promotions_content');
                }
            }
					
			require(
			[
				'jquery',
				'Magento_Ui/js/modal/modal'
			],
			function(jQuery,modal) {
				var options = {
					type: 'popup',
					responsive: true,
					innerScroll: true,
					title: 'Preview',
					buttons: [{
						text: jQuery.mage.__('Close'),
						class: '',
						click: function () {
							this.closeModal();
						}
					}]
				};
			
				jQuery('#preview').on('click',function(){
					jQuery.ajax({
						url : '".$this->getBaseUrl()."promotions/index/preview"."',
						data : jQuery('#edit_form').serialize() + FORM_KEY,
						dataType: 'json',
						showLoader:true,
						type: 'post',
						success: function(data){
							jQuery('#preview-content').html(data);
							var popup = modal(options, jQuery('#preview-content'));
							jQuery('#preview-content').modal('openModal');
						} 
					});
				});
			});
		
		";
    }
}
