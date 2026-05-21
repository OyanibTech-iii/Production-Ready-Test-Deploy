function initScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in');

                // Add staggered delay for multiple elements
                const siblings = Array.from(entry.target.parentNode.children);
                const index = siblings.indexOf(entry.target);
                entry.target.style.transitionDelay = `${index * 0.1}s`;
            } else {
                entry.target.classList.remove('animate-fade-in');
            }
        });
    }, observerOptions);

    // Observe all scroll elements
    document.querySelectorAll('.scroll-fade-in, .scroll-image, .morph-image, .slide-reveal').forEach(el => {
        observer.observe(el);
    });
}

// Enhanced Parallax scrolling effect
function initParallaxScrolling() {
    const parallaxElements = document.querySelectorAll('.parallax-image');

    function updateParallax() {
        const scrolled = window.pageYOffset;
        const rate = scrolled * -0.3;
        const scale = 1 + scrolled * 0.0002;

        parallaxElements.forEach((element, index) => {
            const speed = 0.3 + (index * 0.1);
            const yPos = scrolled * -speed;
            const rotation = scrolled * 0.05;
            element.style.transform = `translateY(${yPos}px) scale(${scale}) rotate(${rotation}deg)`;
        });
    }

    window.addEventListener('scroll', updateParallax);
}

// Enhanced floating animation controller
function initFloatingAnimations() {
    const floatingElements = document.querySelectorAll('.floating-image');

    floatingElements.forEach((element, index) => {
        // Add staggered delay to floating animations
        element.style.animationDelay = `${index * 0.8}s`;
        element.style.animationDuration = `${4 + index * 0.5}s`;

        // Add custom floating effect
        element.addEventListener('mouseenter', function () {
            this.style.animationPlayState = 'paused';
            this.style.transform = 'translateY(-15px) rotate(2deg) scale(1.02)';
        });

        element.addEventListener('mouseleave', function () {
            this.style.animationPlayState = 'running';
        });
    });
}

// Enhanced hover effects with advanced animations
function initHoverEffects() {
    const hoverElements = document.querySelectorAll('.image-hover-effect');

    hoverElements.forEach((element, index) => {
        element.addEventListener('mouseenter', function () {
            this.style.transform = 'translateY(-15px) scale(1.05) rotate(1deg)';
            this.style.transition = 'all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
            this.style.boxShadow = '0 20px 40px rgba(3, 166, 74, 0.2), 0 8px 16px rgba(0, 0, 0, 0.1)';
        });

        element.addEventListener('mouseleave', function () {
            this.style.transform = 'translateY(0) scale(1) rotate(0deg)';
            this.style.transition = 'all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
            this.style.boxShadow = '0 4px 16px rgba(3, 166, 74, 0.07), 0 2px 8px rgba(0, 0, 0, 0.03)';
        });
    });
}

// Enhanced stats animation with counting effect
function initStatsAnimation() {
    const stats = document.querySelectorAll('.pulse-stat');

    const statsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'pulse 2s ease-in-out infinite';

                // Add counting animation
                const finalValue = entry.target.textContent;
                const numericValue = parseInt(finalValue.replace(/\D/g, ''));
                if (numericValue && numericValue > 0) {
                    animateCounter(entry.target, 0, numericValue, 2000, finalValue);
                }
            }
        });
    }, { threshold: 0.5 });

    stats.forEach(stat => statsObserver.observe(stat));
}

// Counter animation function
function animateCounter(element, start, end, duration, originalText) {
    const startTime = performance.now();
    const suffix = originalText.replace(/\d/g, '');

    function updateCounter(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const current = Math.floor(start + (end - start) * progress);

        element.textContent = current + suffix;

        if (progress < 1) {
            requestAnimationFrame(updateCounter);
        }
    }

    requestAnimationFrame(updateCounter);
}

// Advanced text reveal animation
function initTextRevealAnimations() {
    const textElements = document.querySelectorAll('h1, h2, p');

    const textObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('text-reveal');
                setTimeout(() => {
                    entry.target.classList.add('revealed');
                }, 100);
            }
        });
    }, { threshold: 0.3 });

    textElements.forEach(el => textObserver.observe(el));
}

// Advanced glow effects
function initGlowEffects() {
    const glowElements = document.querySelectorAll('.image-hover-effect');

    glowElements.forEach(element => {
        element.classList.add('glow-effect');

        element.addEventListener('mouseenter', function () {
            this.style.boxShadow = '0 0 30px rgba(3, 166, 74, 0.3), 0 0 60px rgba(3, 166, 74, 0.1)';
        });

        element.addEventListener('mouseleave', function () {
            this.style.boxShadow = '';
        });
    });
}

// Performance optimized scroll handler
function initOptimizedScrollAnimations() {
    let ticking = false;

    function updateScrollAnimations() {
        const scrolled = window.pageYOffset;

        document.querySelectorAll('.parallax-image').forEach((element) => {
            // Find the section this image actually lives in
            const parentSection = element.closest('section');
            if (!parentSection) return;

            const sectionTop = parentSection.offsetTop;
            const sectionHeight = parentSection.offsetHeight;

            // Only calculate parallax if the section is actually on screen
            if (scrolled + window.innerHeight > sectionTop && scrolled < sectionTop + sectionHeight) {
                const relativeScroll = scrolled - sectionTop;
                const speed = 0.1; // Lower speed so it doesn't jump

                // This prevents the JS from 'eating' your top margin
                const yPos = relativeScroll * -speed;
                element.style.transform = `translateY(${yPos}px)`;
            }
        });

        ticking = false;
    }

    function requestTick() {
        if (!ticking) {
            requestAnimationFrame(updateScrollAnimations);
            ticking = true;
        }
    }

    window.addEventListener('scroll', requestTick, { passive: true });
}
function initMultiLeafParallax() {
    const sections = document.querySelectorAll('section');
    const leafAssets = ['../leaf/blur.png', '../leaf/motion.png'];
    const MIN_DISTANCE = 15; // Minimum percentage distance between leaves
    /** Match Tailwind md — hide decorative leaves on phones / small tablets */
    const mobileLeafQuery = window.matchMedia('(max-width: 767px)');

    function clearSectionLeaves() {
        document.querySelectorAll('.section-leaf').forEach((el) => el.remove());
    }

    function buildSectionLeaves() {
        sections.forEach((section) => {
            if (section.querySelector('.section-leaf')) {
                return;
            }

            const isVideoSection = section.querySelector('video');
            const leafCount = isVideoSection ? 2 : 5;
            const placedLeaves = [];

            section.style.position = 'relative';
            section.style.overflow = 'hidden';

            for (let i = 0; i < leafCount; i++) {
                let randomX;
                let randomTop;
                let side;
                let isValid = false;
                let attempts = 0;

                while (!isValid && attempts < 15) {
                    side = Math.random() > 0.5 ? 'left' : 'right';
                    randomX = Math.floor(Math.random() * 8);
                    const absoluteX = side === 'left' ? randomX : 100 - randomX;

                    randomTop =
                        Math.random() > 0.5
                            ? Math.floor(Math.random() * 25)
                            : Math.floor(Math.random() * 25) + 65;

                    isValid = placedLeaves.every((other) => {
                        const dx = absoluteX - other.x;
                        const dy = randomTop - other.y;
                        const distance = Math.sqrt(dx * dx + dy * dy);
                        return distance > MIN_DISTANCE;
                    });

                    if (isValid) {
                        placedLeaves.push({ x: absoluteX, y: randomTop });
                    }
                    attempts++;
                }

                if (isValid) {
                    const leaf = document.createElement('img');
                    leaf.src = leafAssets[i % 2];
                    leaf.className = 'section-leaf';

                    if (side === 'left') {
                        leaf.style.left = `${randomX}%`;
                    } else {
                        leaf.style.right = `${randomX}%`;
                    }

                    leaf.style.top = `${randomTop}%`;
                    const size = 60 + Math.random() * 60;
                    leaf.style.width = `${size}px`;
                    leaf.dataset.speed = 0.05 + Math.random() * 0.1;
                    leaf.dataset.baseRotation = Math.floor(Math.random() * 360);

                    section.appendChild(leaf);
                    setTimeout(() => leaf.classList.add('leaf-visible'), 100 + i * 150);
                }
            }
        });
    }

    function syncLeavesToViewport() {
        if (mobileLeafQuery.matches) {
            clearSectionLeaves();
            return;
        }
        buildSectionLeaves();
    }

    syncLeavesToViewport();
    if (typeof mobileLeafQuery.addEventListener === 'function') {
        mobileLeafQuery.addEventListener('change', syncLeavesToViewport);
    } else {
        mobileLeafQuery.addListener(syncLeavesToViewport);
    }
}
// Update your existing DOMContentLoaded listener
document.addEventListener('DOMContentLoaded', () => {
    document.documentElement.style.scrollBehavior = 'smooth';

    // Existing Initializations
    initScrollAnimations();
    initParallaxScrolling();
    initFloatingAnimations();
    initHoverEffects();
    initStatsAnimation();
    initTextRevealAnimations();
    initGlowEffects();
    initOptimizedScrollAnimations();

    // New Global Leaf Initialization
    initMultiLeafParallax();
});