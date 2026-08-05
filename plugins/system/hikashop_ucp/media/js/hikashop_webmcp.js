/**
 * HikaShop WebMCP - Register UCP tools via the W3C WebMCP standard.
 * Uses navigator.modelContext.registerTool() to expose HikaShop commerce tools
 * to browser-based AI agents. Each tool calls the UCP REST API via fetch()
 * with the browser session cookie for authentication.
 */
(function() {
	// Feature detection: W3C WebMCP standard (Chrome 146+)
	if(!navigator.modelContext || !navigator.modelContext.registerTool)
		return;

	var cfg = window.hikashopWebMCPConfig;
	if(!cfg || !cfg.siteUrl || !cfg.ucpBase)
		return;

	/**
	 * Call a UCP REST endpoint with the browser session cookie.
	 * No API key needed: the session scopes the cart to the current visitor.
	 */
	function ucpFetch(method, path, body) {
		var url = cfg.siteUrl + cfg.ucpBase + path;
		var opts = {
			method: method,
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json' }
		};
		if(body)
			opts.body = JSON.stringify(body);
		return fetch(url, opts).then(function(r) { return r.json(); });
	}

	// Search for products in the store
	navigator.modelContext.registerTool({
		name: 'search_products',
		description: 'Search for products in this store by keyword, category, or filters. Returns product names, prices, images, and URLs.',
		inputSchema: {
			type: 'object',
			properties: {
				query: { type: 'string', description: 'Search keyword' },
				category: { type: 'string', description: 'Category ID or alias to filter by' },
				limit: { type: 'number', description: 'Maximum number of results (default 20)' },
				offset: { type: 'number', description: 'Number of results to skip for pagination' }
			}
		},
		execute: function(input) {
			return ucpFetch('POST', '/search', input);
		},
		annotations: { readOnlyHint: true }
	});

	// Create a new checkout session with products
	navigator.modelContext.registerTool({
		name: 'create_checkout',
		description: 'Create a new checkout session with products to purchase. Returns the session ID needed for subsequent checkout operations.',
		inputSchema: {
			type: 'object',
			properties: {
				line_items: {
					type: 'array',
					description: 'Products to add: [{ item: { id: "product_id" }, quantity: 1 }]'
				},
				buyer: {
					type: 'object',
					description: 'Optional buyer info: { email: "customer@example.com" }'
				}
			},
			required: ['line_items']
		},
		execute: function(input) {
			return ucpFetch('POST', '/checkout-sessions', input);
		}
	});

	// Get checkout session details
	navigator.modelContext.registerTool({
		name: 'get_checkout',
		description: 'Get checkout session details including line items, totals, and available payment methods.',
		inputSchema: {
			type: 'object',
			properties: {
				session_id: { type: 'string', description: 'Checkout session ID' }
			},
			required: ['session_id']
		},
		execute: function(input) {
			return ucpFetch('GET', '/checkout-sessions/' + encodeURIComponent(input.session_id));
		},
		annotations: { readOnlyHint: true }
	});

	// Update a checkout session
	navigator.modelContext.registerTool({
		name: 'update_checkout',
		description: 'Update a checkout session: modify items, set buyer info, set shipping address, select shipping method, or apply a discount code.',
		inputSchema: {
			type: 'object',
			properties: {
				session_id: { type: 'string', description: 'Checkout session ID' },
				line_items: { type: 'array', description: 'Updated product list' },
				buyer: { type: 'object', description: 'Buyer info: { email, first_name, last_name }' },
				shipping_address: {
					type: 'object',
					description: 'Shipping address: { line1, city, postal_code, country_code }'
				},
				shipping_selection: { type: 'string', description: 'Selected shipping method ID' },
				discount_code: { type: 'string', description: 'Coupon code to apply' }
			},
			required: ['session_id']
		},
		execute: function(input) {
			var id = input.session_id;
			var body = {};
			for(var k in input) {
				if(k !== 'session_id')
					body[k] = input[k];
			}
			return ucpFetch('PUT', '/checkout-sessions/' + encodeURIComponent(id), body);
		}
	});

	// Complete the checkout and create the order
	navigator.modelContext.registerTool({
		name: 'complete_checkout',
		description: 'Complete the checkout, create the order, and get a payment URL if needed. The user may need to visit the payment URL to complete payment.',
		inputSchema: {
			type: 'object',
			properties: {
				session_id: { type: 'string', description: 'Checkout session ID' },
				payment_method_id: { type: 'string', description: 'Payment method to use' }
			},
			required: ['session_id']
		},
		execute: function(input) {
			var id = input.session_id;
			var body = {};
			for(var k in input) {
				if(k !== 'session_id')
					body[k] = input[k];
			}
			return ucpFetch('POST', '/checkout-sessions/' + encodeURIComponent(id) + '/complete', body);
		}
	});

	// List available payment methods
	navigator.modelContext.registerTool({
		name: 'list_payment_methods',
		description: 'List available payment methods for checkout.',
		inputSchema: { type: 'object', properties: {} },
		execute: function() {
			return ucpFetch('GET', '/payment-methods');
		},
		annotations: { readOnlyHint: true }
	});
})();
