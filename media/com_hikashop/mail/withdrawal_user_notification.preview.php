<?php
/**
 * @package	HikaShop
 * @version	6.5.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2026 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or defined('ABSPATH') or die('Restricted access');
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?><?php
class withdrawal_user_notificationPreviewMaker {
	public function prepareData($data = null) {
		if(empty($data)) return false; 

        $withdrawalClass = hikashop_get('class.withdrawal');
        $withdrawal = $withdrawalClass->get($data);
        if(!$withdrawal) return false;

        $orderClass = hikashop_get('class.order');
        $order = $orderClass->get($withdrawal->withdrawal_order_id);
        $userClass = hikashop_get('class.user');
        $user = $userClass->get($order->order_user_id);

        $obj = new stdClass();
        $obj->withdrawal = $withdrawal;
        $obj->order = $order;
        $obj->user = $user;
        return $obj;
	}

	public function getSelector($data) {
		$html = hikashop_get('type.namebox')->display(
			'data', 
			$data, 
			hikashopNameboxType::NAMEBOX_SINGLE, 
			'withdrawal',
			array(
				'delete' => false,
				'default_text' => '<em>'.JText::_('HIKA_NONE').'</em>',
				'returnOnEmpty' => false,
			)
		);
		if(!$html){
			echo hikashop_display(JText::_('PLEASE_FIRST_CREATE_A_WITHDRAWAL'), 'info');
			return;
		}
		if(empty($data)) {
			echo hikashop_display(Jtext::_('PLEASE_SELECT_A_WITHDRAWAL_FOR_THE_PREVIEW'));
		}

        ?>
        <dl class="hika_options">
            <dt><?php echo JText::_('HIKA_WITHDRAWAL_ID'); ?></dt>
            <dd><?php echo $html; ?></dd>
        </dl>
		<script type="text/javascript">
		window.hikashop.ready(function() {
			var w = window;
			if(!w.oNameboxes['data'])
				return;
			w.oNameboxes['data'].register('set', function(e) {
				hikashop.submitform('preview','adminForm');
			});
		});
		</script>
        <?php
	}
}
?>
