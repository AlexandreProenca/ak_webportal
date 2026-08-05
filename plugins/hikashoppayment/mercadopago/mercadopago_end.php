<?php
/**
 * Página intermediária: encaminha o cliente ao checkout do Mercado Pago.
 *
 * @package AK Soluções
 * @license GNU/GPLv3
 */
defined('_JEXEC') or die('Restricted access');

$redirect = isset($this->vars['redirect_url']) ? $this->vars['redirect_url'] : '';
?>
<div class="hikashop_mercadopago_end" id="hikashop_mercadopago_end">
	<span id="hikashop_mercadopago_end_message" class="hikashop_mercadopago_end_message">
		<?php echo JText::sprintf('PLEASE_WAIT_BEFORE_REDIRECTION_TO_X', $this->payment_name); ?>
		<br/>
		<span id="hikashop_mercadopago_button_message"><?php echo JText::_('CLICK_ON_BUTTON_IF_NOT_REDIRECTED'); ?></span>
	</span>
	<span id="hikashop_mercadopago_end_spinner" class="hikashop_mercadopago_end_spinner hikashop_checkout_end_spinner"></span>
	<br/>
	<div id="hikashop_mercadopago_end_image" class="hikashop_mercadopago_end_image">
		<a id="hikashop_mercadopago_button" class="btn btn-primary hikabtn"
		   href="<?php echo htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8'); ?>"
		   rel="noopener">
			<?php echo JText::_('PAY_NOW'); ?>
		</a>
	</div>
	<script type="text/javascript">
		(function () {
			var url = <?php echo json_encode($redirect); ?>;
			if (!url) return;
			var inIframe = false;
			try { inIframe = (window.self !== window.top); } catch (e) { inIframe = true; }
			if (inIframe) {
				document.getElementById('hikashop_mercadopago_button').target = '_blank';
				return;
			}
			window.location.href = url;
		})();
	</script>
</div>
