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
?><script src="https://www.paypal.com/sdk/js?<?php echo http_build_query($this->params); ?>" data-partner-attribution-id="<?php echo $this->bncode; ?>" <?php echo $this->extraParams; ?>></script>
<div class="hikashop_paypalcheckout_end" id="hikashop_paypalcheckout_end">
<?php
$config = hikashop_config();
$currencyClass = hikashop_get('class.currency');
$group = $config->get('group_options', 0);
$order = $this->order;
?>
<div class="paypal_checkout_columns">
<!-- Left column: order summary -->
<div class="paypal_checkout_summary">
	<h3><?php echo JText::_('ORDER'); ?></h3>
	<table class="paypal_summary_table">
		<thead>
			<tr>
				<th class="paypal_summary_product"><?php echo JText::_('PRODUCT'); ?></th>
				<th class="paypal_summary_qty"><?php echo JText::_('PRODUCT_QUANTITY'); ?></th>
				<th class="paypal_summary_price"><?php echo JText::_('PRICE'); ?></th>
			</tr>
		</thead>
		<tbody>
<?php
	if(!empty($order->cart->products)) {
		foreach($order->cart->products as $product) {
			if($group && !empty($product->order_product_option_parent_id))
				continue;
?>
			<tr>
				<td class="paypal_summary_product"><?php
					echo $product->order_product_name;
					if(!empty($product->order_product_code) && $config->get('show_code'))
						echo ' <small>(' . $product->order_product_code . ')</small>';
				?></td>
				<td class="paypal_summary_qty"><?php echo (int)$product->order_product_quantity; ?></td>
				<td class="paypal_summary_price"><?php echo $currencyClass->format($product->order_product_total_price, $order->order_currency_id); ?></td>
			</tr>
<?php
		}
	}
?>
		</tbody>
	</table>
	<div class="paypal_summary_totals">
<?php
	$subtotal = 0;
	if(!empty($order->cart->products)) {
		foreach($order->cart->products as $product) {
			if($group && !empty($product->order_product_option_parent_id))
				continue;
			$subtotal += $product->order_product_total_price;
		}
	}
?>
		<div class="paypal_summary_line">
			<span><?php echo JText::_('SUBTOTAL'); ?></span>
			<span><?php echo $currencyClass->format($subtotal, $order->order_currency_id); ?></span>
		</div>
<?php
	if(!empty($order->order_shipping_price)) {
?>
		<div class="paypal_summary_line">
			<span><?php echo JText::_('SHIPPING'); ?></span>
			<span><?php echo $currencyClass->format($order->order_shipping_price + $order->order_shipping_tax, $order->order_currency_id); ?></span>
		</div>
<?php
	}
	if(!empty($order->order_payment_price) && bccomp(sprintf('%F', $order->order_payment_price), 0, 5) !== 0) {
?>
		<div class="paypal_summary_line">
			<span><?php echo JText::_('HIKASHOP_PAYMENT'); ?></span>
			<span><?php echo $currencyClass->format($order->order_payment_price + $order->order_payment_tax, $order->order_currency_id); ?></span>
		</div>
<?php
	}
	if(!empty($order->order_discount_price) && bccomp(sprintf('%F', $order->order_discount_price), 0, 5) !== 0) {
?>
		<div class="paypal_summary_line">
			<span><?php echo JText::_('HIKASHOP_COUPON'); ?></span>
			<span>-<?php echo $currencyClass->format($order->order_discount_price + $order->order_discount_tax, $order->order_currency_id); ?></span>
		</div>
<?php
	}
	$order_taxes = $order->order_tax_info;
	if(is_string($order_taxes))
		$order_taxes = hikashop_unserialize($order_taxes);
	$total_tax = 0;
	if(!empty($order_taxes)) {
		foreach($order_taxes as $tax)
			$total_tax += $tax->tax_amount;
	}
	$total_tax += $order->order_shipping_tax + $order->order_payment_tax - $order->order_discount_tax;
	if($total_tax > 0) {
?>
		<div class="paypal_summary_line">
			<span><?php echo JText::_('TAXES'); ?></span>
			<span><?php echo $currencyClass->format($total_tax, $order->order_currency_id); ?></span>
		</div>
<?php
	}
?>
		<div class="paypal_summary_line paypal_summary_total">
			<span><?php echo JText::_('HIKASHOP_TOTAL'); ?></span>
			<span><?php echo $this->total_amount; ?></span>
		</div>
	</div>
<?php
	$addressClass = hikashop_get('class.address');
	$zone_namekeys = array('address_state', 'address_country');

	$summaryAddresses = array();
	if(!empty($order->cart->shipping_address))
		$summaryAddresses['shipping'] = $order->cart->shipping_address;
	if(!empty($order->cart->billing_address))
		$summaryAddresses['billing'] = $order->cart->billing_address;

	foreach($summaryAddresses as &$addr) {
		foreach($zone_namekeys as $zk) {
			if(!empty($addr->$zk) && is_object($addr->$zk)) {
				if(!empty($addr->$zk->zone_code_2) && $zk == 'address_country')
					$addr->address_country_code_2 = $addr->$zk->zone_code_2;
				$addr->$zk = $addr->$zk->zone_name_english;
			}
		}
	}
	unset($addr);

	if(!empty($summaryAddresses['shipping'])) {
?>
	<div class="paypal_summary_address">
		<strong><?php echo JText::_('HIKASHOP_SHIPPING_ADDRESS'); ?></strong><br/>
		<?php echo $addressClass->miniFormat($summaryAddresses['shipping']); ?>
	</div>
<?php
	}
	if(!empty($summaryAddresses['billing'])) {
?>
	<div class="paypal_summary_address">
		<strong><?php echo JText::_('HIKASHOP_BILLING_ADDRESS'); ?></strong><br/>
		<?php echo $addressClass->miniFormat($summaryAddresses['billing']); ?>
	</div>
<?php
	}
?>
</div>
<!-- Right column: payment buttons -->
<div class="paypal_checkout_payment">
<div id="paypal-select-message"><?php echo JText::sprintf('USE_PAYMENT_BUTTON_OR_CREDIT_CARD_FORM_BELOW', $this->total_amount); ?></div>
<div id="paypal-button-container"></div>
<?php
if(!empty($this->payment_params->enable_applepay)) {
?>
<script src="https://applepay.cdn-apple.com/jsapi/1.latest/apple-pay-sdk.js"></script>
<div id="paypal-applepay-container"></div>
<?php
}
if(!empty($this->payment_params->enable_googlepay)) {
?>
<div id="paypal-googlepay-container"></div>
<?php
}
?>
</div>
</div>
<?php
if(!empty($this->payment_params->enable_credit_card)) { ?>
<!-- Advanced credit and debit card payments form -->
<div id="paypal-errors"></div>
<div id="card_container" class='card_container'>
	<form id='my-sample-form'>
		<div class="hk-row-fluid">
			<div class="hkc-md-6">
			<h3 class="card_title"><?php echo JText::_('CREDITCARD_PAYMENT'); ?></h3>
				<div id='card-number' class='card_field'></div>
				<div class="hk-row-fluid">
					<div class="hkc-md-6 expiration_date_position_div">
						<div id='expiration-date' class='card_field'></div>
					</div>
					<div class="hkc-md-6 cvv_position_div">
						<div id='cvv' class='card_field'></div>
					</div>
				</div>
				<label for="card-holder-name"><?php echo JText::_('CREDIT_CARD_OWNER'); ?></label>
				<input type='text' class="form-control" id='card-holder-name' name='card-holder-name' value="<?php echo htmlEntities(@$this->order->cart->billing_address->address_firstname.' '.@$this->order->cart->billing_address->address_lastname, ENT_QUOTES); ?>" autocomplete='off' placeholder='<?php echo JText::_('CREDIT_CARD_OWNER'); ?>'/>
			</div>
			<div class="hkc-md-6">
				<h3 class="card_title"><?php echo JText::_('CREDIT_CARD_BILLING_ADDRESS'); ?></h3>
				<div>
					<label for="card-billing-address-street"><?php echo JText::_('STREET'); ?></label>
					<input type='text' class="form-control" id='card-billing-address-street' name='card-holder-name' value="<?php echo htmlEntities((string)@$this->order->cart->billing_address->address_street, ENT_QUOTES); ?>" name='card-billing-address-street' autocomplete='off' placeholder='<?php echo JText::_('STREET'); ?>'/>
				</div>
				<div>
					<label for="card-billing-address-unit"><?php echo JText::_('STREET'); ?></label>
					<input type='text' class="form-control" id='card-billing-address-unit' name='card-billing-address-unit' name='card-holder-name' value="<?php echo htmlEntities((string)@$this->order->cart->billing_address->address_street2, ENT_QUOTES); ?>" autocomplete='off' placeholder=''/>
				</div>
				<div>
					<label for="card-billing-address-city"><?php echo JText::_('CITY'); ?></label>
					<input type='text' class="form-control" id='card-billing-address-city' name='card-billing-address-city' value="<?php echo htmlEntities((string)@$this->order->cart->billing_address->address_city, ENT_QUOTES); ?>" autocomplete='off' placeholder='<?php echo JText::_('CITY'); ?>'/>
				</div>
				<div>
					<label for="card-billing-address-state"><?php echo JText::_('STATE'); ?></label>
					<input type='text' class="form-control" id='card-billing-address-state' name='card-billing-address-state' value="<?php echo htmlEntities((string)@$this->billing_state, ENT_QUOTES); ?>" autocomplete='off' placeholder='<?php echo JText::_('STATE'); ?>'/>
				</div>
				<div class="hk-row-fluid">
					<div class='hkc-md-6'>
						<label for="card-billing-address-zip"><?php echo JText::_('POST_CODE'); ?></label>
						<input type='text' class="form-control" id='card-billing-address-zip' name='card-billing-address-zip' value="<?php echo htmlEntities((string)@$this->order->cart->billing_address->address_post_code, ENT_QUOTES); ?>" autocomplete='off' placeholder='<?php echo JText::_('POST_CODE'); ?>'/>
					</div>
					<div class='hkc-md-6'>
						<label for="card-billing-address-country"><?php echo JText::_('COUNTRY'); ?></label>
						<select class="form-control" id="card-billing-address-country" name="card-billing-address-country">
							<?php foreach ($this->countries as $key => $value) { ?>
								<option value="<?php echo htmlentities($key, ENT_QUOTES); ?>" <?php echo ($key === @$this->billing_country_code) ? 'selected' : ''; ?>>
									<?php echo htmlentities($value, ENT_QUOTES); ?>
								</option>
							<?php } ?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<button value='submit' id='paypal_pay_button' class='paypal_pay_button hikabtn hikabtn-success'><?php echo JText::_('PAY_NOW'); ?>  <i class="fa fa-credit-card"></i></button>
	</form>
</div>
<?php } ?>
<div id="paypal_cancel_button_div" class="paypal_cancel_button_div">
	<a id="paypal_cancel_button" class="paypal_cancel_button hikabtn hikabtn-error" href="<?php echo $this->cancel_url; ?>"><?php echo JText::_('CANCEL_AND_CHOOSE_ANOTHER_PAYMENT_METHOD'); ?>  <i class="fa fa-ban"></i></a>
</div>
<script>
if(!window.Oby.extractJSON) {
	window.Oby.extractJSON = function(str) {
		var firstOpen, firstClose, candidate;
		firstOpen = str.indexOf('{', firstOpen + 1);
		do {
			firstClose = str.lastIndexOf('}');
			if(firstClose <= firstOpen) {
				return null;
			}
			do {
				candidate = str.substring(firstOpen, firstClose + 1);
				try {
					var res = JSON.parse(candidate);
					return res;
				}
				catch(e) {
					console.log('failed parsing JSON in string below:');
					console.log(candidate);
				}
				firstClose = str.substr(0, firstClose).lastIndexOf('}');
			} while(firstClose > firstOpen);
			firstOpen = str.indexOf('{', firstOpen + 1);
		} while(firstOpen != -1);
		return null;
	};
}
window.displayHKMessage = function(msg) {
	var divName = 'system-message-container';
	if(Joomla && Joomla.renderMessages) {
		Joomla.renderMessages({"error":[msg]});
	} else {
		divName = 'paypal-errors';
		document.getElementById(divName).innerHTML = msg;
	}
	var errDiv = document.getElementById(divName);
	if(errDiv)
		errDiv.scrollIntoView();
}
paypal.Buttons(
	{
		style: {
			layout: '<?php echo $this->payment_params->layout; ?>',
			color: '<?php echo $this->payment_params->color; ?>',
			shape: '<?php echo $this->payment_params->shape; ?>',
			label: '<?php echo $this->payment_params->label; ?>',
<?php if($this->payment_params->tagline) { ?>
			tagline: '<?php echo $this->payment_params->tagline; ?>',
<?php } ?>
		},
		createOrder: function(data, actions) {
			callbackURL = "<?php echo $this->notify_url; ?>&createorder=1";
			return fetch(callbackURL, {method: "POST"})
			.then(response => response.json())
			.then(resp => {
				console.log(callbackURL);
				console.log(resp);

				if(resp && resp.error) {
					window.displayHKMessage(resp.errorMessage);
					return false;
				} else if(resp && resp.redirect) {
					window.location.href = resp.redirect;
					return new Promise(function() {});
				} else {
					return resp.id;
				}
			});
		},
		onApprove: function (data, actions) {
			callbackURL = "<?php echo $this->notify_url; ?>&onapprove=1&orderID=" + data.orderID;
			return fetch(callbackURL, {method: "POST"})
			.then(response => response.json())
			.then(resp => {
				console.log(callbackURL);
				console.log(resp);

				if(resp && resp.error) {
					window.displayHKMessage(resp.errorMessage);
					return false;
				} else if(resp.errorMessage == 'restart') {
					return actions.restart();
				}
				document.getElementById('hikashop_paypalcheckout_end').innerHTML = '<?php echo str_replace(array("\n", "\r"), '', (string)hikashop_display(JText::_('THANK_YOU_FOR_PURCHASE', true), 'success', true)); ?>';
				var redirectUrl = "<?php echo $this->notify_url; ?>&paypal_id="+resp.id;
				if(resp.pending) {
					redirectUrl += "&pending=1";
				}
				window.location.href = redirectUrl;
			});
		},
		onError: function (err) {

			var errormsg = "<?php echo str_replace(array("\n", "\r"), '', str_replace('"','\"',(string)JText::sprintf('PAYMENT_REQUEST_REFUSED_BY_PAYPAL_CANCEL_URL', $this->cancel_url))); ?>";

			var data = window.Oby.extractJSON(err.message);
			if(data) {
				console.log(data);
				for(var i = 0; i < data.body.details.length; i++) {
					var details = data.body.details[i];
					var msg = '';
					if(details.issue)
						msg+='['+details.issue+'] ';
					if(details.description)
						msg+=details.description;
					if(msg.length)
						errormsg+='<br/>'+msg;
				}
			} else {
				console.log(err);
			}
			window.displayHKMessage(errormsg);
		},
<?php
if(!empty($this->payment_params->cancel_url)) {
	?>
		onCancel: function (data) {
			window.location.href = "<?php echo $this->cancel_url; ?>";
		},
	<?php
}
?>
	}
).render('#paypal-button-container');
</script>
<?php if(!empty($this->payment_params->enable_credit_card)) { ?>
<script>
if (paypal.HostedFields.isEligible()) {
	paypal.HostedFields.render({
		createOrder: function () {
			callbackURL = "<?php echo $this->notify_url; ?>&createorder=1";
			return fetch(callbackURL, {method: "POST"})
			.then(response => response.json())
			.then(resp => {
				console.log(callbackURL);
				console.log(resp);

				if(resp && resp.error) {
					window.displayHKMessage(resp.errorMessage);
					return false;
				} else {
					return resp.id;
				}
			});
		},
		styles: {
			'input': {
				'padding': '0.6rem 1rem',
				'font-size': '1rem',
				'font-weight': '400',
				'line-height': '1.5',
				'-webkit-transition': 'border-color 0.15s ease-in-out, -webkit-box-shadow 0.15s ease-in-out',
				'transition': 'border-color 0.15s ease-in-out, -webkit-box-shadow 0.15s ease-in-out',
				'transition': 'border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out',
				'transition': 'border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out, -webkit-box-shadow 0.15s ease-in-out',
			},
			':focus': {
				'color': 'black'
			},
			'.valid': {
				'color': 'green',
			},
			'.invalid': {
				'color': 'red'
			}
		},
		fields: {
			number: {
				selector: '#card-number',
				placeholder: '<?php echo str_replace(array("\n", "\r"), '', (string)JText::_('CREDIT_CARD_NUMBER', true)); ?>'
			},
			cvv: {
				selector: '#cvv',
				placeholder: 'cvv'
			},
			expirationDate: {
				selector: '#expiration-date',
				placeholder: 'MM/YY'
			}
		}
	}).then(function (hf) {
		document.getElementById('my-sample-form').addEventListener('submit', (event) => {
			event.preventDefault();
			document.getElementById('paypal_pay_button').disabled = true;
			document.getElementById('card_container').style.opacity = "0.5";
			hf.submit({
				cardholderName: document.getElementById('card-holder-name').value,
				billingAddress: {
					streetAddress: document.getElementById('card-billing-address-street').value, // address_line_1 - street
					extendedAddress: document.getElementById('card-billing-address-unit').value, // address_line_2 - unit
					region: document.getElementById('card-billing-address-state').value, // admin_area_1 - state
					locality: document.getElementById('card-billing-address-city').value, // admin_area_2 - town / city
					postalCode: document.getElementById('card-billing-address-zip').value, // postal_code - postal_code
					countryCodeAlpha2: document.getElementById('card-billing-address-country').value // country_code - country
				}
<?php if(!empty($this->enable_3dsecure)) { ?>
				,
				contingencies: ['<?php echo $this->enable_3dsecure; ?>']
<?php } ?>
			}).then(function (payload) {
				callbackURL = "<?php echo $this->notify_url; ?>&onapprove=1&orderID=" + payload.orderID;
				return fetch(callbackURL, {method: "POST"})
				.then(response => response.json())
				.then(resp => {
					console.log(callbackURL);
					console.log(resp);

					if(resp && resp.error) {
						window.displayHKMessage(resp.errorMessage);

						if(resp.errorCode == 'stop_and_retry') {
							document.getElementById('paypal_pay_button').disabled = false;
							document.getElementById('card_container').style.opacity = "1";
						}
						return false;
					}
					var mainArea = document.getElementById('hikashop_paypalcheckout_end');
					mainArea.innerHTML = '<?php echo str_replace(array("\n", "\r"), '', (string)hikashop_display(JText::_('THANK_YOU_FOR_PURCHASE', true), 'success', true)); ?>';
					mainArea.scrollIntoView();
					var redirectUrl = "<?php echo $this->notify_url; ?>&paypal_id="+resp.id;
					if(resp.pending) { redirectUrl += "&pending=1"; }
					window.location.href = redirectUrl;
				});
			}).catch(error => {
				console.log(error);
				window.displayHKMessage('<?php echo str_replace(array("\n", "\r"), '', (string)JText::_('PLEASE_FILL_IN_ALL_THE_FIELDS', true)); ?>');
				document.getElementById('paypal_pay_button').disabled = false;
				document.getElementById('card_container').style.opacity = "1";
			});
		});
	});
} else {
	document.querySelector('.card_container').style.display = 'none'; // hides the advanced credit and debit card payments fields if seller isn't eligible
	document.getElementById('paypal-select-message').innerHTML = '<?php echo str_replace(array("\n", "\r"), '', str_replace("'","\'",(string)JText::sprintf('USE_PAYMENT_BUTTON_BELOW', $this->total_amount))); ?>';
}
</script>
<?php } ?>
<?php if(!empty($this->payment_params->enable_applepay)) { ?>
<script>
if (!window.ApplePaySession || !ApplePaySession.canMakePayments()) {
  console.error('This device does not support Apple Pay');
} else {
const applepay = paypal.Applepay();
applepay.config().then(applepayConfig => {
    if (applepayConfig.isEligible) {
        document.querySelector("#paypal-applepay-container").innerHTML =
            '<apple-pay-button id="paypal-applepay-button" buttonstyle="<?php echo $this->payment_params->applepay_color; ?>" type="buy" <?php if(!empty($this->payment_params->applepay_locale)) echo 'locale="'.$this->payment_params->applepay_locale.'"'; ?>></apple-pay-button>';

        document.querySelector("#paypal-applepay-button").addEventListener("click", async () => {

            const session = new ApplePaySession(4, {
                countryCode: applepayConfig.countryCode,
                currencyCode: "<?php echo $this->currency->currency_code; ?>",
                merchantCapabilities: applepayConfig.merchantCapabilities,
                supportedNetworks: applepayConfig.supportedNetworks,
				total: {
					label: "<?php echo $this->payment_params->store_name; ?>",
					type: "final",
					amount: "<?php echo $this->orderData->purchase_units[0]->amount->value; ?>",
				}
            });

            session.onvalidatemerchant = (event) => {
                const validationData = applepay.validateMerchant({
					validationUrl: event.validationURL,
					displayName: "<?php echo $this->payment_params->store_name; ?>"
				}).then(validateResult => {
					session.completeMerchantValidation(validateResult.merchantSession);
				})
				.catch(validateError => {
					console.error(validateError);
					session.abort();
				});
            };

            session.onpaymentauthorized = (event) => {
				callbackURL = "<?php echo $this->notify_url; ?>&createorder=1";
				fetch(callbackURL, {method: "POST"})
				.then(response => response.json())
				.then(resp => {
					console.log(callbackURL);
					console.log(resp);

					if(resp && resp.error) {
						window.displayHKMessage(resp.errorMessage);
						return false;
					} else {
						var orderId = resp.id;
						applepay.confirmOrder({
							orderId: orderId,
							token: event.payment.token,
							billingContact: event.payment.billingContact
						})
						.then(confirmResult => {
							session.completePayment(ApplePaySession.STATUS_SUCCESS);
							callbackURL = "<?php echo $this->notify_url; ?>&onapprove=1&orderID=" + orderId;
							fetch(callbackURL, {method: "POST"})
							.then(response => response.json())
							.then(resp => {
								console.log(callbackURL);
								console.log(resp);

								if(resp && resp.error) {
									window.displayHKMessage(resp.errorMessage);
									return false;
								}
								document.getElementById('hikashop_paypalcheckout_end').innerHTML = '<?php echo str_replace(array("\n", "\r"), '', (string)hikashop_display(JText::_('THANK_YOU_FOR_PURCHASE', true), 'success', true)); ?>';
								var redirectUrl = "<?php echo $this->notify_url; ?>&paypal_id="+resp.id;
								if(resp.pending) { redirectUrl += "&pending=1"; }
								window.location.href = redirectUrl;
							});
						})
						.catch(confirmError => {
							if (confirmError) {
								console.error('Error confirming order with applepay token');
								console.error(confirmError);
								session.completePayment(ApplePaySession.STATUS_FAILURE);
							}
						});
					}
				});
            };
            session.begin();
        });
    }
}).catch(applepayConfigError => {
  console.error('Error while fetching Apple Pay configuration.');
});
}
</script>
<?php } ?>

<?php if(!empty($this->payment_params->enable_googlepay)) { ?>
<script>
const baseRequest = {
  apiVersion: 2,
  apiVersionMinor: 0,
};
let paymentsClient = null,
  allowedPaymentMethods = null,
  merchantInfo = null;

function getGoogleIsReadyToPayRequest(allowedPaymentMethods) {
  return Object.assign({}, baseRequest, {
    allowedPaymentMethods: allowedPaymentMethods,
  });
}

async function getGooglePayConfig() {
  if (allowedPaymentMethods == null || merchantInfo == null) {
    const googlePayConfig = await paypal.Googlepay().config();
	console.log(googlePayConfig);
    allowedPaymentMethods = googlePayConfig.allowedPaymentMethods;
    merchantInfo = googlePayConfig.merchantInfo;
  }
  return {
    allowedPaymentMethods,
    merchantInfo,
  };
}

async function getGooglePaymentDataRequest() {
  const paymentDataRequest = Object.assign({}, baseRequest);
  const { allowedPaymentMethods, merchantInfo } = await getGooglePayConfig();
  paymentDataRequest.allowedPaymentMethods = allowedPaymentMethods;
  paymentDataRequest.transactionInfo = getGoogleTransactionInfo();
  paymentDataRequest.merchantInfo = merchantInfo;
  paymentDataRequest.callbackIntents = ["PAYMENT_AUTHORIZATION"];
  return paymentDataRequest;
}
function onPaymentAuthorized(paymentData) {
  return new Promise(function (resolve, reject) {
    processPayment(paymentData)
      .then(function (data) {
        resolve(data);
      })
      .catch(function (errDetails) {
        resolve({ transactionState: "ERROR", error: { message: errDetails.message } });
      });
  });
}
function getGooglePaymentsClient() {
  if (paymentsClient === null) {
    paymentsClient = new google.payments.api.PaymentsClient({
      environment: "<?php echo $this->payment_params->googlepay_environment; ?>",
      paymentDataCallbacks: {
        onPaymentAuthorized: onPaymentAuthorized,
      },
    });
  }
  return paymentsClient;
}
async function onGooglePayLoaded() {
  const paymentsClient = getGooglePaymentsClient();
  const { allowedPaymentMethods } = await getGooglePayConfig();
  console.log(allowedPaymentMethods);
  const isReadyToPayRequest = getGoogleIsReadyToPayRequest(
	allowedPaymentMethods
  );
  console.log(isReadyToPayRequest);
  paymentsClient
    .isReadyToPay(isReadyToPayRequest)
    .then(function (response) {
      if (response.result) {
        addGooglePayButton();
      }
    })
    .catch(function (err) {
      console.error(err);
    });
}
function addGooglePayButton() {
  const paymentsClient = getGooglePaymentsClient();
  const button = paymentsClient.createButton({
    onClick: onGooglePaymentButtonClicked,
  });
  document.getElementById("paypal-googlepay-container").appendChild(button);
}
function getGoogleTransactionInfo() {
  return {
        currencyCode: '<?php echo $this->currency->currency_code; ?>',
        countryCode: '<?php echo $this->payment_params->country_code; ?>',
        totalPriceStatus: 'FINAL',
        totalPrice: '<?php echo $this->orderData->purchase_units[0]->amount->value; ?>'
  };
}
async function onGooglePaymentButtonClicked() {
  const paymentDataRequest = await getGooglePaymentDataRequest();
<?php  if($this->payment_params->country_code == 'JP') { ?>
  paymentDataRequest.allowedPaymentMethods[0].parameters.allowedAuthMethods = ['PAN_ONLY'];
<?php } ?>
  paymentDataRequest.transactionInfo = getGoogleTransactionInfo();
  const paymentsClient = getGooglePaymentsClient();
  paymentsClient.loadPaymentData(paymentDataRequest);
}
async function processPayment(paymentData) {
  try {
    var callbackURL = "<?php echo $this->notify_url; ?>&createorder=1";
    var response = await fetch(callbackURL, {method: "POST"});
    var resp = await response.json();
    console.log(callbackURL);
    console.log(resp);

    if(resp && resp.error) {
      window.displayHKMessage(resp.errorMessage);
      return { transactionState: "ERROR", error: { message: resp.errorMessage } };
    }

    var orderId = resp.id;
    var confirmOrderResponse = await paypal.Googlepay().confirmOrder({
      orderId: orderId,
      paymentMethodData: paymentData.paymentMethodData,
    });

    if (confirmOrderResponse.status === "PAYER_ACTION_REQUIRED") {
      console.log("==== Confirm Payment Completed Payer Action Required =====");
      await paypal.Googlepay().initiatePayerAction({ orderId: orderId });
    }

    if (confirmOrderResponse.status === "APPROVED" || confirmOrderResponse.status === "PAYER_ACTION_REQUIRED") {
      callbackURL = "<?php echo $this->notify_url; ?>&onapprove=1&orderID=" + orderId;
      response = await fetch(callbackURL, {method: "POST"});
      resp = await response.json();
      console.log(callbackURL);
      console.log(resp);

      if(resp && resp.error) {
        window.displayHKMessage(resp.errorMessage);
        return { transactionState: "ERROR", error: { message: resp.errorMessage } };
      }
      document.getElementById('hikashop_paypalcheckout_end').innerHTML = '<?php echo str_replace(array("\n", "\r"), '', (string)hikashop_display(JText::_('THANK_YOU_FOR_PURCHASE', true), 'success', true)); ?>';
      var redirectUrl = "<?php echo $this->notify_url; ?>&paypal_id="+resp.id;
      if(resp.pending) { redirectUrl += "&pending=1"; }
      window.location.href = redirectUrl;
      return { transactionState: "SUCCESS" };
    }

    return { transactionState: "ERROR", error: { message: "Unexpected status: " + confirmOrderResponse.status } };
  } catch (err) {
    return {
      transactionState: "ERROR",
      error: {
        message: err.message,
      },
    };
  }
}
</script>
<script async src="https://pay.google.com/gp/p/js/pay.js" onload="onGooglePayLoaded()"></script>
<?php } ?>
<style>

.paypal_checkout_columns {
	display: flex;
	gap: 20px;
	margin-bottom: 20px;
}
.paypal_checkout_summary {
	flex: 0 0 45%;
	background: #f8f8f8;
	border: 1px solid #e0e0e0;
	border-radius: 4px;
	padding: 15px 20px;
}
.paypal_checkout_summary h3 {
	margin: 0 0 10px 0;
	font-size: 1.1em;
}
.paypal_checkout_payment {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
}

.paypal_summary_table {
	width: 100%;
	border-collapse: collapse;
	margin-bottom: 10px;
	font-size: 0.9em;
}
.paypal_summary_table th {
	text-align: left;
	border-bottom: 1px solid #ddd;
	padding: 4px 6px;
	font-weight: bold;
	font-size: 0.85em;
	color: #666;
}
.paypal_summary_table td {
	padding: 4px 6px;
	border-bottom: 1px solid #eee;
	vertical-align: top;
}
.paypal_summary_qty,
.paypal_summary_price {
	text-align: right;
	white-space: nowrap;
}
th.paypal_summary_qty,
th.paypal_summary_price {
	text-align: right;
}

.paypal_summary_totals {
	border-top: 1px solid #ddd;
	padding-top: 8px;
	margin-top: 5px;
	font-size: 0.9em;
}
.paypal_summary_line {
	display: flex;
	justify-content: space-between;
	padding: 2px 6px;
}
.paypal_summary_total {
	font-weight: bold;
	font-size: 1.1em;
	border-top: 1px solid #ccc;
	margin-top: 4px;
	padding-top: 6px;
}

.paypal_summary_address {
	margin-top: 10px;
	padding-top: 8px;
	border-top: 1px solid #eee;
	font-size: 0.85em;
	line-height: 1.4;
}

@media (max-width: 768px) {
	.paypal_checkout_columns {
		flex-direction: column;
	}
	.paypal_checkout_summary {
		flex: none;
	}
}
#paypal-button-container,
#paypal-applepay-container,
#paypal-googlepay-container {
    text-align: center;
	max-width: 200px;
	margin: auto;
}
div#paypal-select-message {
    text-align: center;
    margin: 5px;
    font-weight: bold;
}
#card-number,
#expiration-date,
#cvv {
	height: 40px;
	width: 100%;
	border: 1px solid hsl(210, 14%, 83%);
	border-radius: 2px;
	background-color: white;
	margin-top: 20px;
}
.card_container {
    background-color: #f2f2f2;
    color: #000;
    padding: 5px 20px 15px 20px;
    border: 1px solid lightgrey;
    border-radius: 3px;
}
.paypal_pay_button {
	width: 100%;
	margin: 3px;
	font-weight: bold;
	font-size: 1.6em;
}
.paypal_cancel_button {
	width: 100%;
	margin: 3px;
	font-size: 1.2em;
}
.card_container label{
	display:block;
	margin-top: 20px;
    padding-left: 5px;
}
.hkc-md-6.expiration_date_position_div{
    padding-left: 0;
}
.hkc-md-6.cvv_position_div{
    padding-right: 0;
}
#paypal-errors {
	font-size: 1.2em;
	color: red;
	font-weight: bold;
}
#paypal-applepay-container apple-pay-button {
    display: block;       
    width: 100%;          
    height: 44px;         
    border-radius: 6px;   
    margin-top: 10px;     
}
</style>
</div>
