document.addEventListener('DOMContentLoaded', function () {
	const isDarkMode = document.documentElement.classList.contains('dark');
	const labelColor = isDarkMode ? '#e5e7eb' : '#4b5563';
	const chartEl = document.getElementById('area-chart');
	if (!chartEl) {
		return;
	}

	const totalUsers = Number(chartEl.dataset.totalUsers) || 0;
	const ctx = chartEl.getContext('2d');

	// Try to read real data arrays passed from the controller via Twig
	let growthData = [];
	let labels = [];
	if (chartEl.dataset.growth) {
		try { growthData = JSON.parse(chartEl.dataset.growth); } catch (e) { growthData = []; }
	}
	if (chartEl.dataset.dates) {
		try { labels = JSON.parse(chartEl.dataset.dates); } catch (e) { labels = []; }
	}

	const DEFAULT_RANGE_DAYS = 7;

	// If growthData provided, use it directly. Otherwise synthesize a full trend and then default to 7 days.
	let dataPoints = [];
	if (Array.isArray(growthData) && growthData.length > 0) {
		dataPoints = growthData.map(n => Number(n));
		// If no labels or mismatch, generate date labels for the data length
		if (!Array.isArray(labels) || labels.length !== dataPoints.length) {
			labels = [];
			const n = dataPoints.length;
			for (let i = n - 1; i >= 0; i--) {
				const d = new Date();
				d.setDate(d.getDate() - i);
				labels.push(d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }));
			}
		}
	} else {
		// Fallback: 7-day synthetic trend that ends at totalUsers
		for (let i = 6; i >= 0; i--) {
			const d = new Date();
			d.setDate(d.getDate() - i);
			labels.push(d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }));
		}
		for (let i = 0; i < 7; i++) {
			const factor = 0.5 + 0.5 * (i / 6);
			dataPoints.push(Math.round(totalUsers * factor));
		}
	}

	// Enforce a consistent max height to prevent Chart.js from stretching the canvas
	const MAX_CANVAS_PX = 320;
	chartEl.style.height = MAX_CANVAS_PX + 'px';
	chartEl.style.maxHeight = MAX_CANVAS_PX + 'px';
	chartEl.style.width = '100%';

	// Create gradient fill using theme-like greens
	const gradient = ctx.createLinearGradient(0, 0, 0, 200);
	gradient.addColorStop(0, 'rgba(16,185,129,0.16)');
	gradient.addColorStop(1, 'rgba(6,78,59,0.04)');

	const adminChart = new Chart(ctx, {
		type: 'line',
		data: {
			labels: labels,
			datasets: [{
				label: 'Users',
				data: dataPoints,
				fill: true,
				backgroundColor: gradient,
				borderColor: '#065f46',
				pointBackgroundColor: '#10B981',
				tension: 0.35,
				pointRadius: 3,
				borderWidth: 2
			}]
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
			plugins: {
				legend: { display: false }
			},
			scales: {
				x: {
					grid: { display: false },
					ticks: { color: '#6b7280', font: { size: 11, family: 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif' } }
				},
				y: {
					grid: { color: 'rgba(15,23,42,0.04)' },
					ticks: { color: '#6b7280', beginAtZero: true, font: { size: 11, family: 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif' } }
				}
			},
			elements: {
				line: { capBezierPoints: true }
			}
		}
	});

	// expose to window for controls
	window.adminChart = adminChart;

	// Render default range as 7 days on initial load
	if (dataPoints.length > DEFAULT_RANGE_DAYS) {
		updateForRange(DEFAULT_RANGE_DAYS);
	}

	// helper to update chart for a given number of days
	function updateForRange(days) {
		let pts = [];
		let lbls = [];
		if (Array.isArray(growthData) && growthData.length > 0) {
			// use most recent `days` points if available
			const n = Math.min(days, growthData.length);
			pts = growthData.slice(-n).map(Number);
			lbls = labels.slice(-n);
			// if labels empty or mismatch, generate relative labels
			if (!Array.isArray(lbls) || lbls.length !== pts.length) {
				lbls = [];
				for (let i = pts.length - 1; i >= 0; i--) {
					const d = new Date();
					d.setDate(d.getDate() - i);
					lbls.push(d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }));
				}
			}
		} else {
			// synthesize `days` points ending at totalUsers
			for (let i = days - 1; i >= 0; i--) {
				const d = new Date();
				d.setDate(d.getDate() - i);
				lbls.push(d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }));
			}
			for (let i = 0; i < days; i++) {
				const factor = 0.2 + 0.8 * (i / Math.max(1, days - 1));
				pts.push(Math.round(totalUsers * factor));
			}
		}

		adminChart.data.labels = lbls;
		adminChart.data.datasets[0].data = pts;
		adminChart.update();
	}

	// wire timeframe buttons
	document.querySelectorAll('.timeframe-btn').forEach(btn => {
		btn.addEventListener('click', function () {
			document.querySelectorAll('.timeframe-btn').forEach(b => b.classList.remove('bg-white', 'text-dark-forest-green'));
			this.classList.add('bg-white', 'text-dark-forest-green');
			const days = Number(this.dataset.range) || 7;
			updateForRange(days);
		});
	});

	// Download chart flow (requires admin password)
	const downloadBtn = document.getElementById('download-chart-btn');
	// CSRF token for authorize endpoint
	// const csrfToken = "{{ csrf_token('admin_dashboard_download') }}";
	const csrfToken = window.dashboardCsrfToken;

	// Function to generate and download the PDF with retry logic
	// Function to generate and download the PDF
	function downloadChartAsPDF() {
		// Target the entire grid/container containing your charts
		// Ensure you add this ID to your HTML wrapper in dashboard.html.twig
		const element = document.getElementById('dashboard-charts-container');

		if (!element) {
			showErrorModal('Export Error', 'Could not find the chart container to download.');
			return;
		}

		const opt = {
			margin: [10, 10],
			filename: 'growfico-dashboard-report.pdf',
			image: { type: 'jpeg', quality: 0.98 },
			html2canvas: {
				scale: 2,
				useCORS: true,
				logging: true,
				letterRendering: true
			},
			jsPDF: { orientation: 'portrait', unit: 'mm', format: 'a4' }
		};

		try {
			html2pdf().set(opt).from(element).save();
		} catch (error) {
			console.error('PDF generation error:', error);
			showErrorModal('PDF Generation Error', error.message);
		}
	}

	// create modal markup with backdrop blur and eye icon for password toggle
	const modalHtml = `
								<div id="download-modal" class="fixed inset-0 z-50 hidden backdrop-blur-md" style="display: flex; align-items: center; justify-content: center;">
									<div class="absolute inset-0 bg-black/40 backdrop-blur-md"></div>
									<div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md mx-4 relative z-10">
										<div class="flex items-center gap-4 mb-4">
											<div class="w-12 h-12 rounded-full bg-bright-green/10 flex items-center justify-center">
												<ion-icon name="download-outline" class="text-2xl text-bright-green"></ion-icon>
											</div>
											<div>
												<h3 class="text-lg font-semibold text-dark-forest-green">Download Chart as PDF</h3>
												<p class="text-xs text-light-gray">Verify your admin password</p>
											</div>
										</div>
										<p class="text-sm text-light-gray mb-5">Enter your admin password to download the chart as a PDF file.</p>
										<form id="download-form">
											<div class="relative mb-4">
												<input type="password" id="download-password" name="password" placeholder="Admin password" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm" required />
												<button type="button" id="toggle-password-visibility" class="absolute right-3 top-1/2 -translate-y-1/2 text-light-gray hover:text-dark-forest-green transition-colors">
													<ion-icon name="eye-outline" class="text-xl"></ion-icon>
												</button>
											</div>
											<div class="flex gap-3">
												<button type="button" id="download-cancel" class="flex-1 px-4 py-2.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-dark-forest-green font-medium transition-colors duration-200">Cancel</button>
												<button type="submit" class="flex-1 px-4 py-2.5 rounded-lg bg-bright-green hover:bg-green-600 text-white font-medium transition-colors duration-200 flex items-center justify-center gap-2">
													<ion-icon name="download-outline" class="text-lg"></ion-icon>Download PDF
												</button>
											</div>
										</form>
									</div>
								</div>`;

	function openModal() {
		const div = document.createElement('div');
		div.innerHTML = modalHtml;
		document.body.appendChild(div);
		document.getElementById('download-password').focus();

		// Eye icon toggle for password visibility
		const toggleBtn = document.getElementById('toggle-password-visibility');
		const pwdInput = document.getElementById('download-password');
		toggleBtn.addEventListener('click', function (e) {
			e.preventDefault();
			const isPassword = pwdInput.type === 'password';
			pwdInput.type = isPassword ? 'text' : 'password';
			toggleBtn.innerHTML = isPassword
				? '<ion-icon name="eye-off-outline" class="text-xl"></ion-icon>'
				: '<ion-icon name="eye-outline" class="text-xl"></ion-icon>';
		});

		// cancel handler
		document.getElementById('download-cancel').addEventListener('click', () => closeModal());
		// form handler
		document.getElementById('download-form').addEventListener('submit', async function (e) {
			e.preventDefault();
			const pwd = document.getElementById('download-password').value;
			try {
				const res = await fetch(window.dashboardAuthorizeUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-CSRF-TOKEN': csrfToken
					},
					body: JSON.stringify({ password: pwd })
				});
				const json = await res.json();

				if (json.authorized) {
					// FIX: We no longer need to pass 'img' because the function 
					// now captures the DOM element directly.
					downloadChartAsPDF();
					closeModal();
				} else {
					const errorMsg = json.message || 'Invalid password. Please try again.';
					showErrorModal('Invalid Password', errorMsg);
				}
			} catch (err) {
				console.error('Authorization error:', err);
				showErrorModal('Authorization Failed', err.message);
			}
		});
	}

	function closeModal() {
		const m = document.getElementById('download-modal');
		if (m) m.parentNode.remove();
	}

	downloadBtn.addEventListener('click', function () {
		openModal();
	});

	// Reusable modal functions
	function showErrorModal(title, message) {
		showModal(title, message, 'error', 'OK', false);
	}

	function showSuccessModal(title, message) {
		showModal(title, message, 'success', 'OK', false);
	}

	function showInfoModal(title, message) {
		showModal(title, message, 'info', 'OK', false);
	}

	function showModal(title, message, type = 'info', confirmText = 'OK', showCancel = false) {
		const modalId = 'dynamic-modal-' + Date.now();
		const iconMap = {
			'error': { name: 'alert-circle-outline', bg: 'bg-red-100', text: 'text-red-600' },
			'warning': { name: 'warning-outline', bg: 'bg-yellow-100', text: 'text-yellow-600' },
			'success': { name: 'checkmark-circle-outline', bg: 'bg-green-100', text: 'text-green-600' },
			'info': { name: 'information-circle-outline', bg: 'bg-blue-100', text: 'text-blue-600' }
		};
		const icon = iconMap[type] || iconMap['info'];
		const buttonColor = type === 'error' ? 'bg-red-600 hover:bg-red-700' :
			type === 'warning' ? 'bg-yellow-600 hover:bg-yellow-700' :
				type === 'success' ? 'bg-green-600 hover:bg-green-700' :
					'bg-blue-600 hover:bg-blue-700';

		const modalHtml = `
				<div id="${modalId}" class="fixed inset-0 z-50 backdrop-blur-md" style="display: flex;">
					<div class="absolute inset-0 bg-black/40 backdrop-blur-md"></div>
					<div class="absolute inset-0 flex items-center justify-center">
						<div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full mx-4 transform transition-all relative z-10 ">
							<div class="flex items-center gap-4 mb-4">
								<div class="w-12 h-12 rounded-full ${icon.bg} flex items-center justify-center">
									<ion-icon name="${icon.name}" class="text-2xl ${icon.text}"></ion-icon>
								</div>
								<div>
									<h3 class="text-lg font-semibold text-dark-forest-green">${title}</h3>
								</div>
							</div>
							<p class="text-sm text-light-gray mb-6">${message}</p>
							<div class="flex gap-3">
								${showCancel ? '<button class="modal-cancel-btn flex-1 px-4 py-2.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-dark-forest-green font-medium transition-colors duration-200">Cancel</button>' : ''}
								<button class="modal-confirm-btn flex-1 px-4 py-2.5 rounded-lg ${buttonColor} text-white font-medium transition-colors duration-200">${confirmText}</button>
							</div>
						</div>
					</div>
				</div>
			`;

		const div = document.createElement('div');
		div.innerHTML = modalHtml;
		document.body.appendChild(div);

		const modal = document.getElementById(modalId);
		const confirmBtn = modal.querySelector('.modal-confirm-btn');
		const cancelBtn = modal.querySelector('.modal-cancel-btn');

		function closeModal() {
			modal.style.display = 'none';
			setTimeout(() => modal.remove(), 300);
		}

		confirmBtn.addEventListener('click', closeModal);
		if (cancelBtn) {
			cancelBtn.addEventListener('click', closeModal);
		}

		modal.addEventListener('click', function (e) {
			if (e.target === modal || e.target.classList.contains('bg-black/40')) {
				closeModal();
			}
		});

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && modal.style.display !== 'none') {
				closeModal();
			}
		});
	}

	// Stock bar chart (by stock type)
	const stockCanvas = document.getElementById('stock-bar-chart');
	if (stockCanvas) {
		let stockLabels = [];
		let stockValues = [];
		if (stockCanvas.dataset.labels) {
			try { stockLabels = JSON.parse(stockCanvas.dataset.labels); } catch (e) { stockLabels = []; }
		}
		if (stockCanvas.dataset.values) {
			try { stockValues = JSON.parse(stockCanvas.dataset.values); } catch (e) { stockValues = []; }
		}

		stockCanvas.style.height = MAX_CANVAS_PX + 'px';
		stockCanvas.style.maxHeight = MAX_CANVAS_PX + 'px';
		stockCanvas.style.width = '100%';

		const stockCtx = stockCanvas.getContext('2d');
		new Chart(stockCtx, {
			type: 'bar',
			data: {
				labels: stockLabels,
				datasets: [{
					label: 'Total Quantity',
					data: stockValues,
					backgroundColor: '#10B981',
					borderColor: 'transparent',
					borderWidth: 1.5,
					borderRadius: 12,
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: { display: false }
				},
				scales: {
					x: {
						grid: { display: false },
						ticks: {
							color: '#6b7280',
							font: { size: 11, family: 'Poppins, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif' }
						}
					},
					y: {
						beginAtZero: true,
						grid: { color: 'rgba(15,23,42,0.04)' },
						ticks: {
							color: '#6b7280',
							font: { size: 11, family: 'Poppins, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif' }
						}
					}
				}
			}
		});
	}

	// Product status pie chart
	const productPieCanvas = document.getElementById('product-pie-chart');
	if (productPieCanvas) {
		let statusLabels = [];
		let statusValues = [];
		if (productPieCanvas.dataset.labels) {
			try { statusLabels = JSON.parse(productPieCanvas.dataset.labels); } catch (e) { statusLabels = []; }
		}
		if (productPieCanvas.dataset.values) {
			try { statusValues = JSON.parse(productPieCanvas.dataset.values); } catch (e) { statusValues = []; }
		}

		productPieCanvas.style.height = MAX_CANVAS_PX + 'px';
		productPieCanvas.style.maxHeight = MAX_CANVAS_PX + 'px';
		productPieCanvas.style.width = '100%';

		// Semantic colors: Green for In stock, Orange/Amber for Low stock, Red for Out of stock
		const colors = ['#10b981', '#f59e0b', '#ef4444'];

		const pieCtx = productPieCanvas.getContext('2d');
		new Chart(pieCtx, {
			type: 'doughnut',
			data: {
				labels: statusLabels,
				datasets: [{
					data: statusValues,
					backgroundColor: colors,
					borderColor: '#ffffff',
					borderWidth: 2,
					hoverOffset: 4
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				cutout: '70%',
				plugins: {
					legend: {
						position: 'bottom',
						labels: {
							color: labelColor,
							padding: 20,
							font: { size: 11, family: 'Poppins, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif' },
							usePointStyle: true,
						}
					}
				}
			}
		});
	}
});
