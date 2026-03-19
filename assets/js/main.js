/**
 * VivekCMS — Premium Frontend JavaScript v2.0
 * Scroll animations, parallax, smooth interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ================================================
    // NAVBAR — Scroll Effect + Transparent to Solid
    // ================================================
    const navbar = document.getElementById('navbar');
    if (navbar) {
        let lastScroll = 0;
        window.addEventListener('scroll', function() {
            const currentScroll = window.scrollY;
            navbar.classList.toggle('scrolled', currentScroll > 30);
            lastScroll = currentScroll;
        }, { passive: true });
    }

    // ================================================
    // MOBILE NAV TOGGLE
    // ================================================
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            const spans = navToggle.querySelectorAll('span');
            const isOpen = navMenu.classList.contains('active');
            spans[0].style.transform = isOpen ? 'rotate(45deg) translate(5px, 5px)' : '';
            spans[1].style.opacity = isOpen ? '0' : '';
            spans[2].style.transform = isOpen ? 'rotate(-45deg) translate(5px, -5px)' : '';
        });
        // Close on link click
        navMenu.querySelectorAll('.nav-link').forEach(function(link) {
            link.addEventListener('click', function() {
                navMenu.classList.remove('active');
            });
        });
    }

    // ================================================
    // SCROLL REVEAL ANIMATION
    // ================================================
    const revealElements = document.querySelectorAll(
        '.post-card, .category-card, .stat-card, .sidebar-widget, ' +
        '.section-header, .hero-stats, .cta-card, .page-feature-card, ' +
        '.contact-info-card, .auth-card, .page-content, .comment-item'
    );

    // Set initial styles
    revealElements.forEach(function(el, index) {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s cubic-bezier(0.4, 0, 0.2, 1) ' + (index % 6) * 0.08 + 's, transform 0.6s cubic-bezier(0.4, 0, 0.2, 1) ' + (index % 6) * 0.08 + 's';
    });

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -30px 0px' });

    revealElements.forEach(function(el) { observer.observe(el); });

    // ================================================
    // HERO COUNTER ANIMATION
    // ================================================
    const statNumbers = document.querySelectorAll('.hero-stat-number');
    if (statNumbers.length) {
        const counterObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    counterObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        statNumbers.forEach(function(el) { counterObserver.observe(el); });
    }

    function animateCounter(element) {
        const text = element.textContent;
        const number = parseInt(text);
        if (isNaN(number)) return;
        const suffix = text.replace(number.toString(), '');
        const duration = 1500;
        const steps = 40;
        const increment = number / steps;
        let current = 0;
        const timer = setInterval(function() {
            current += increment;
            if (current >= number) {
                current = number;
                clearInterval(timer);
            }
            element.textContent = Math.floor(current) + suffix;
        }, duration / steps);
    }

    // ================================================
    // AUTO-REMOVE FLASH MESSAGES
    // ================================================
    const flash = document.getElementById('flashMessage');
    if (flash) {
        setTimeout(function() { flash.remove(); }, 5500);
    }

    // ================================================
    // SMOOTH ANCHOR SCROLLING
    // ================================================
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // ================================================
    // TILT EFFECT ON CARDS (subtle)
    // ================================================
    if (window.innerWidth > 768) {
        document.querySelectorAll('.post-card, .category-card, .page-feature-card').forEach(function(card) {
            card.addEventListener('mousemove', function(e) {
                const rect = card.getBoundingClientRect();
                const x = (e.clientX - rect.left) / rect.width;
                const y = (e.clientY - rect.top) / rect.height;
                const tiltX = (y - 0.5) * 6;
                const tiltY = (x - 0.5) * -6;
                card.style.transform = 'translateY(-8px) perspective(1000px) rotateX(' + tiltX + 'deg) rotateY(' + tiltY + 'deg)';
            });
            card.addEventListener('mouseleave', function() {
                card.style.transform = '';
            });
        });
    }

    // ================================================
    // TYPED EFFECT ON HERO (optional subtle glow)
    // ================================================
    const heroTitle = document.querySelector('.hero-title');
    if (heroTitle) {
        heroTitle.style.opacity = '0';
        heroTitle.style.transform = 'translateY(20px)';
        setTimeout(function() {
            heroTitle.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
            heroTitle.style.opacity = '1';
            heroTitle.style.transform = 'translateY(0)';
        }, 200);
    }

    const heroSubtitle = document.querySelector('.hero-subtitle');
    if (heroSubtitle) {
        heroSubtitle.style.opacity = '0';
        heroSubtitle.style.transform = 'translateY(15px)';
        setTimeout(function() {
            heroSubtitle.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
            heroSubtitle.style.opacity = '1';
            heroSubtitle.style.transform = 'translateY(0)';
        }, 400);
    }

    const heroActions = document.querySelector('.hero-actions');
    if (heroActions) {
        heroActions.style.opacity = '0';
        heroActions.style.transform = 'translateY(15px)';
        setTimeout(function() {
            heroActions.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
            heroActions.style.opacity = '1';
            heroActions.style.transform = 'translateY(0)';
        }, 600);
    }
});
