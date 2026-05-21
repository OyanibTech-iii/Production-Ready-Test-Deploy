const initialPage = window.initialPage || 'home';

// Initialize SPA navigation
function initSPANavigation() {
	const navLinks = document.querySelectorAll('.nav-link');
	const contentSections = document.querySelectorAll('.content-section');

	// Ensure every section starts from the top when switched via nav.
	function resetViewToTop() {
		window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
		document.documentElement.scrollTop = 0;
		document.body.scrollTop = 0;

		const mainContent = document.getElementById('main-content');
		if (mainContent) {
			mainContent.scrollTop = 0;
		}
	}

	// Helper function to set active nav state
	function setActiveNav(activeLink) {
		navLinks.forEach(link => {
			link.classList.remove('active');
			const a = link.querySelector('a');
			if (a) {
				a.classList.remove('text-bright-green');
				a.removeAttribute('aria-current');
			}
		});

		if (!activeLink) return;
		activeLink.classList.add('active');
		const aa = activeLink.querySelector('a');
		if (aa) {
			aa.classList.add('text-bright-green');
			aa.setAttribute('aria-current', 'page');
		}
	}

	// Show content with smooth animation using class toggles
	function showContent(sectionId) {
		// Remove active class from all sections
		contentSections.forEach(section => {
			section.classList.remove('active');
		});

		// Add active class to the selected section
		const targetSection = document.getElementById(sectionId);
		if (targetSection) {
			targetSection.classList.add('active');

			window.dispatchEvent(
				new CustomEvent('growfico:section-shown', { detail: { id: sectionId } })
			);

			// Reveal any scroll-animated children immediately when a section becomes active
			const animatedChildren = targetSection.querySelectorAll('.scroll-fade-in, .scroll-image, .morph-image, .slide-reveal');
			animatedChildren.forEach((el, i) => { // small stagger
				el.classList.add('animate-fade-in');
				el.style.transitionDelay = `${
					i * 0.06
				}s`;
			});

			// Remove animate class from non-active sections
			contentSections.forEach(section => {
				if (section.id !== sectionId) {
					const cs = section.querySelectorAll('.scroll-fade-in, .scroll-image, .morph-image, .slide-reveal');
					cs.forEach(el => {
						el.classList.remove('animate-fade-in');
						el.style.transitionDelay = '';
					});
				}
			});
		}
	}

	// Add click handlers to navigation links
	navLinks.forEach(link => {
		link.addEventListener('click', function (e) {
			e.preventDefault();
			const section = this.getAttribute('data-section');
			const sectionId = section + '-content';

			resetViewToTop();
			setActiveNav(this);
			showContent(sectionId);
		});
	});

	// Initialize: determine which section to show
	const desiredSection = initialPage || 'home';
	const desiredLink = document.querySelector(`[data-section="${desiredSection}"]`);
	if (desiredLink) {
		setActiveNav(desiredLink);
		showContent(desiredSection + '-content');
	} else {
		// fallback to home
		const homeLink = document.querySelector('[data-section="home"]');
		if (homeLink) {
			setActiveNav(homeLink);
			showContent('home-content');
		}
	}
}

// Initialize SPA navigation when DOM is loaded
document.addEventListener('DOMContentLoaded', initSPANavigation);