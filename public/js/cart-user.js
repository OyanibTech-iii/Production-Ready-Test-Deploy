
	async function removeCartItem(itemId) {
		const confirmed = await window.showConfirmModal({
			title: 'Remove Item',
			message: 'Are you sure you want to remove this item from your cart?',
			confirmText: 'Remove',
			confirmClass: 'bg-red-600 hover:bg-red-700'
		});

		if (!confirmed) return;

		const itemElement = document.getElementById(`cart-item-${itemId}`);
		const button = document.querySelector(`.remove-cart-item-btn[data-item-id="${itemId}"]`);

		// Disable button and show loading state
		if (button) {
			button.disabled = true;
			button.classList.add('opacity-50');
		}

		try {
			const response = await fetch(`/userpage/cart/items/${itemId}`, {
				method: 'DELETE',
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
					'Content-Type': 'application/json'
				}
			});

			const data = await response.json();

			if (data.success) {
				// Animate removal
				if (itemElement) {
					itemElement.style.opacity = '0';
					itemElement.style.transform = 'scale(0.95)';
					itemElement.style.transition = 'all 0.3s ease';

					setTimeout(() => {
						itemElement.remove();

						// Update cart subtotal
						const subtotalEl = document.getElementById('cart-subtotal');
						if (subtotalEl && data.newTotal !== undefined) {
							// Reload page to update totals and handle empty cart state
							window.location.reload();
						}
					}, 300);
				} else {
					window.location.reload();
				}
			} else {
				window.showAlertModal({
					title: 'Error',
					message: data.message || 'Failed to remove item.',
					confirmClass: 'bg-red-600'
				});
				// Re-enable button on error
				if (button) {
					button.disabled = false;
					button.classList.remove('opacity-50');
				}
			}
		} catch (error) {
			console.error('Error:', error);
			window.showAlertModal({
				title: 'Error',
				message: 'An error occurred while removing the item.',
				confirmClass: 'bg-red-600'
			});
			// Re-enable button on error
			if (button) {
				button.disabled = false;
				button.classList.remove('opacity-50');
			}
		}
	}

	(function () {
		// Get Stripe key from script data attribute
		const stripeScript = document.querySelector('script[src*="js.stripe.com"]');
		const stripeKey = stripeScript ? stripeScript.getAttribute('data-stripe-key') : null;
		// Payment Method Selection
		var paymentOptions = document.querySelectorAll('.payment-option');
		var selectedPaymentMethod = null;

		paymentOptions.forEach(function (opt) {
			opt.addEventListener('click', function () {
				paymentOptions.forEach(function (o) {
					o.classList.remove('border-bright-green', 'bg-bright-green/5', 'dark:bg-gray-700/50');
					var c = o.querySelector('.payment-check');
					if (c) c.classList.add('hidden');
				});
				this.classList.add('border-bright-green', 'bg-bright-green/5');
				var check = this.querySelector('.payment-check');
				if (check) check.classList.remove('hidden');
				selectedPaymentMethod = this.getAttribute('data-method');
				document.querySelector('input[name="paymentMethod"]').value = selectedPaymentMethod;
			});
		});

		// Star Rating Functionality
		document.querySelectorAll('.rating-star').forEach(function (star) {
			star.addEventListener('click', function () {
				const productId = this.getAttribute('data-product-id');
				const rating = parseInt(this.getAttribute('data-rating'));
				const stars = document.querySelectorAll(`.rating-star[data-product-id="${productId}"]`);
				const hiddenInput = document.querySelector(`input[name="reviews[${productId}][rating]"]`);

				// Update star display
				stars.forEach(function (s, index) {
					if (index < rating) {
						s.innerHTML = '<ion-icon name="star"></ion-icon>';
						s.classList.remove('text-gray-300');
						s.classList.add('text-yellow-400');
					} else {
						s.innerHTML = '<ion-icon name="star-outline"></ion-icon>';
						s.classList.remove('text-yellow-400');
						s.classList.add('text-gray-300');
					}
				});

				// Update hidden input
				if (hiddenInput) {
					hiddenInput.value = rating;
				}
			});
		});

		// Modal Logic
		var modal = document.getElementById('cart-checkout-modal');
		var openBtn = document.getElementById('cart-checkout-open');
		var cancelBtn = document.getElementById('cart-checkout-cancel');
		var form = document.getElementById('checkout-form');
		var checkoutItemIds = [];

		// Quantity Update Logic
		document.querySelectorAll('.update-qty-btn').forEach(function (button) {
			button.addEventListener('click', async function () {
				const itemId = this.getAttribute('data-item-id');
				const action = this.getAttribute('data-action');
				const qtyDisplay = document.getElementById(`qty-display-${itemId}`);
				if (!qtyDisplay) return;

				let currentQty = parseInt(qtyDisplay.textContent, 10);
				let newQty = action === 'increase' ? currentQty + 1 : currentQty - 1;

				if (newQty < 1) return;
				
				const max = parseInt(this.getAttribute('data-max'), 10);
				if (action === 'increase' && !isNaN(max) && newQty > max) {
					window.showAlertModal({
						title: 'Stock Limit',
						message: `Only ${max} items available in stock.`,
						confirmClass: 'bg-amber-500'
					});
					return;
				}

				// Disable all quantity buttons for this item during update
				const itemButtons = document.querySelectorAll(`.update-qty-btn[data-item-id="${itemId}"]`);
				itemButtons.forEach(btn => btn.disabled = true);
				qtyDisplay.classList.add('opacity-50');

				try {
					const response = await fetch(`/userpage/cart/items/${itemId}/quantity`, {
						method: 'PATCH',
						headers: {
							'Content-Type': 'application/json',
							'X-Requested-With': 'XMLHttpRequest'
						},
						body: JSON.stringify({ quantity: newQty })
					});

					const result = await response.json();

					if (result.success) {
						// Reload to update all totals correctly
						window.location.reload();
					} else {
						window.showAlertModal({
							title: 'Update Failed',
							message: result.message || 'Failed to update quantity.',
							confirmClass: 'bg-red-600'
						});
						// Reset buttons state on failure
						itemButtons.forEach(btn => {
							const btnAction = btn.getAttribute('data-action');
							if (btnAction === 'decrease' && currentQty <= 1) {
								btn.disabled = true;
							} else if (btnAction === 'increase' && !isNaN(max) && currentQty >= max) {
								btn.disabled = true;
							} else {
								btn.disabled = false;
							}
						});
						qtyDisplay.classList.remove('opacity-50');
					}
				} catch (error) {
					console.error('Error:', error);
					window.location.reload();
				}
			});
		});

		if (!modal || !openBtn || !form) return;

		function parseCurrency(value) {
			return parseFloat(value.replace(/[^0-9.-]+/g, '')) || 0;
		}

		function formatCurrency(value) {
			return '₱' + value.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
		}

		function setModalTotal(amount) {
			var totalAmount = document.getElementById('checkout-total-amount');
			if (totalAmount) {
				totalAmount.textContent = formatCurrency(amount);
			}
		}

		function showReviewItems(itemIds) {
			document.querySelectorAll('.checkout-review-item').forEach(function (item) {
				var cartItemId = item.getAttribute('data-cart-item-id');
				if (!itemIds || itemIds.length === 0 || itemIds.includes(cartItemId)) {
					item.classList.remove('hidden');
				} else {
					item.classList.add('hidden');
				}
			});
		}

		function getCartSubtotal() {
			var subtotalEl = document.getElementById('cart-subtotal');
			return subtotalEl ? parseCurrency(subtotalEl.textContent) : 0;
		}

		function showModal() {
			modal.classList.remove('hidden');
			modal.setAttribute('aria-hidden', 'false');
		}

		function closeModal() {
			modal.classList.add('hidden');
			modal.setAttribute('aria-hidden', 'true');
			// Reset form
			form.reset();
			// Reset star ratings
			document.querySelectorAll('.rating-star').forEach(function (star) {
				star.innerHTML = '<ion-icon name="star-outline"></ion-icon>';
				star.classList.remove('text-yellow-400');
				star.classList.add('text-gray-300');
			});
			// Reset payment method selection
			selectedPaymentMethod = null;
			paymentOptions.forEach(function (opt) {
				opt.classList.remove('border-bright-green', 'bg-bright-green/5', 'dark:bg-gray-700/50');
				var check = opt.querySelector('.payment-check');
				if (check) check.classList.add('hidden');
			});
		}

		function openCheckoutModal(itemIds, totalAmount, productName, quantity) {
			checkoutItemIds = itemIds || [];
			var qtyDisplay = document.getElementById('checkout-quantity-display');
			var qtyText = 'All Items';
			if (typeof quantity !== 'undefined' && quantity !== null) {
				qtyText = quantity;
			}
			if (checkoutItemIds.length === 1 && qtyText === 'All Items') {
				qtyText = '1';
			}
			if (qtyDisplay) {
				qtyDisplay.textContent = qtyText;
			}
			if (checkoutItemIds.length === 1) {
				var title = document.getElementById('cart-checkout-modal-title');
				var note = document.getElementById('checkout-note');
				if (title) {
					title.textContent = 'Checkout this item';
				}
				if (note) {
					note.textContent = 'You are checking out a single selected product.';
				}
				showReviewItems(checkoutItemIds);
				setModalTotal(totalAmount || 0);
			} else {
				var title = document.getElementById('cart-checkout-modal-title');
				var note = document.getElementById('checkout-note');
				if (title) {
					title.textContent = 'Complete Your Order';
				}
				if (note) {
					note.textContent = 'Review your selected items and choose a payment method.';
				}
				showReviewItems([]);
				setModalTotal(getCartSubtotal());
			}
			showModal();
		}

		document.querySelectorAll('.checkout-item-btn').forEach(function (button) {
			button.addEventListener('click', function () {
				var itemId = this.getAttribute('data-item-id');
				var lineTotal = parseCurrency(this.getAttribute('data-line-total') || '0');
				var quantity = parseInt(this.getAttribute('data-quantity'), 10) || 1;
				openCheckoutModal([itemId], lineTotal, this.getAttribute('data-product-name'), quantity);
			});
		});

		openBtn.addEventListener('click', function () {
			openCheckoutModal([]);
		});

		if (cancelBtn) {
			cancelBtn.addEventListener('click', closeModal);
		}

		['cart-checkout-close', 'cart-checkout-backdrop'].forEach(function (id) {
			var el = document.getElementById(id);
			if (el) el.addEventListener('click', closeModal);
		});

		// Form Submission
		form.addEventListener('submit', async function (e) {
			e.preventDefault();

			// Validate payment method
			if (!selectedPaymentMethod) {
				window.showAlertModal({
					title: 'Missing Payment Method',
					message: 'Please select a payment method before placing your order.',
					confirmClass: 'bg-amber-500 hover:bg-amber-600',
					hideIcon: true
				});
				return;
			}

			const visibleReviewItems = Array.from(document.querySelectorAll('.checkout-review-item')).filter(function (item) {
				return !item.classList.contains('hidden');
			});

			if (visibleReviewItems.length === 0) {
				window.showAlertModal({
					title: 'No Items Selected',
					message: 'Please select at least one item to checkout.',
					confirmClass: 'bg-amber-500'
				});
				return;
			}

			const reviews = [];
			let allRated = true;

			visibleReviewItems.forEach(function (item) {
				var productId = item.getAttribute('data-product-id');
				var ratingInput = item.querySelector(`input[name="reviews[${productId}][rating]"]`);
				var commentInput = item.querySelector(`textarea[name="reviews[${productId}][comment]"]`);

				if (!ratingInput || !ratingInput.value) {
					allRated = false;
					return;
				}

				reviews.push({
					productId: parseInt(productId, 10),
					rating: parseInt(ratingInput.value, 10),
					comment: commentInput ? commentInput.value.trim() : null
				});
			});

			if (!allRated) {
				window.showAlertModal({
					title: 'Rating Required',
					message: 'Please rate all selected products before placing your order.',
					confirmClass: 'bg-amber-500'
				});
				return;
			}

			const data = {
				paymentMethod: selectedPaymentMethod,
				reviews: reviews
			};

			if (checkoutItemIds.length > 0) {
				data.itemIds = checkoutItemIds.map(function (id) {
					return parseInt(id, 10);
				});
			}

			const submitBtn = form.querySelector('button[type="submit"]');
			const originalText = submitBtn.textContent;
			submitBtn.disabled = true;
			submitBtn.textContent = 'Placing Order...';

			try {
				const checkoutUrl = form.getAttribute('data-checkout-url') || window.growficoCartCheckoutUrl || "";
				const response = await fetch(checkoutUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-Requested-With': 'XMLHttpRequest'
					},
					body: JSON.stringify(data)
				});

				const result = await response.json();

				if (result.success) {
					if (result.redirect && result.sessionId) {
						// Redirect to Stripe Checkout
						if (stripeKey) {
							const stripe = Stripe(stripeKey);
							await stripe.redirectToCheckout({ sessionId: result.sessionId });
						} else {
							window.showAlertModal({
								title: 'Configuration Error',
								message: 'Stripe payment is not configured. Please contact support.',
								confirmClass: 'bg-red-600'
							});
						}
					} else {
						await window.showAlertModal({
							title: 'Success!',
							message: result.message || 'Order placed successfully.',
							confirmClass: 'bg-[#03A64A]'
						});
						closeModal();
						window.location.reload();
					}
				} else {
					window.showAlertModal({
						title: 'Order Failed',
						message: result.message || 'Failed to place order.',
						confirmClass: 'bg-red-600'
					});
				}
			} catch (error) {
				console.error('Error:', error);
				window.showAlertModal({
					title: 'Error',
					message: 'An error occurred while placing your order.',
					confirmClass: 'bg-red-600'
				});
			} finally {
				submitBtn.disabled = false;
				submitBtn.textContent = originalText;
			}
		});
	})();