/* ==========================================================================
   SOCIO NINJAS — MODERN INTERACTION & CURSOR ENGINE
   Inspired by wsocial.news & modern agency interactions
   ========================================================================== */

document.addEventListener('DOMContentLoaded', () => {
  initWsocialCursor();
  initNavbarScroll();
  initServiceDrawers();
  initRoiCalculator();
  initContactForm();
  initMobileNav();
  initFaqAccordions();
});

/* ==========================================================================
   1. WSOCIAL.NEWS STYLE CURSOR ENGINE (Difference blending & magnetic feel)
   ========================================================================== */
function initWsocialCursor() {
  const cursor = document.querySelector('.custom-cursor');
  const follower = document.querySelector('.cursor-follower-ring');
  const cursorTag = document.querySelector('.cursor-tag');

  if (!cursor || !follower) return;

  let mouseX = window.innerWidth / 2;
  let mouseY = window.innerHeight / 2;
  let followerX = mouseX;
  let followerY = mouseY;

  window.addEventListener('mousemove', (e) => {
    mouseX = e.clientX;
    mouseY = e.clientY;

    cursor.style.transform = `translate(${mouseX}px, ${mouseY}px) translate(-50%, -50%)`;
  });

  function renderFollower() {
    followerX += (mouseX - followerX) * 0.18;
    followerY += (mouseY - followerY) * 0.18;

    follower.style.transform = `translate(${followerX}px, ${followerY}px) translate(-50%, -50%)`;
    if (cursorTag) {
      cursorTag.style.transform = `translate(${mouseX}px, ${mouseY}px) translate(-50%, -50%)`;
    }

    requestAnimationFrame(renderFollower);
  }
  renderFollower();

  // Hover states on links, buttons, inputs
  const hoverables = document.querySelectorAll('a, button, input, select, textarea, .service-row-item, .niche-chip-btn, .client-brand-chip');
  hoverables.forEach((el) => {
    el.addEventListener('mouseenter', () => {
      document.body.classList.add('is-hovering');
    });
    el.addEventListener('mouseleave', () => {
      document.body.classList.remove('is-hovering');
    });
  });

  // Expand with view tag on case studies & media
  const viewables = document.querySelectorAll('.work-card, .client-brand-chip');
  viewables.forEach((card) => {
    card.addEventListener('mouseenter', () => {
      document.body.classList.add('is-viewing');
      if (cursorTag) cursorTag.textContent = 'VIEW';
    });
    card.addEventListener('mouseleave', () => {
      document.body.classList.remove('is-viewing');
    });
  });
}

/* ==========================================================================
   2. NAVBAR SCROLL DETECTOR
   ========================================================================== */
function initNavbarScroll() {
  const navbar = document.querySelector('.agency-navbar');
  if (!navbar) return;

  window.addEventListener('scroll', () => {
    if (window.scrollY > 50) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
  });
}

/* ==========================================================================
   3. EDITORIAL SERVICE ROWS EXPANDER (Red Dash Media style)
   ========================================================================== */
function initServiceDrawers() {
  const serviceRows = document.querySelectorAll('.service-row-item');

  serviceRows.forEach((row) => {
    row.addEventListener('click', (e) => {
      // Don't toggle if clicking internal direct link
      if (e.target.closest('a')) return;

      const isActive = row.classList.contains('is-active');
      serviceRows.forEach((r) => r.classList.remove('is-active'));

      if (!isActive) {
        row.classList.add('is-active');
      }
    });
  });
}

/* ==========================================================================
   4. INTERACTIVE ROI & GROWTH CALCULATOR
   ========================================================================== */
function initRoiCalculator() {
  const slider = document.getElementById('budget-range');
  const budgetVal = document.getElementById('budget-val-text');
  const nicheBtns = document.querySelectorAll('.niche-chip-btn');

  const metricReach = document.getElementById('metric-reach');
  const metricClicks = document.getElementById('metric-clicks');
  const metricLeads = document.getElementById('metric-leads');
  const metricRoas = document.getElementById('metric-roas');
  const claimBtn = document.getElementById('claim-strategy-btn');

  if (!slider || !metricReach) return;

  let currentBudget = parseInt(slider.value, 10);
  let currentNicheMultiplier = 1.0;
  let currentNicheTitle = 'E-Commerce / D2C';

  const nicheData = {
    'ecom': { mult: 1.25, roas: '4.8x – 6.5x', leadRate: 0.048, label: 'E-Commerce / D2C' },
    'b2b': { mult: 0.85, roas: '3.6x – 5.2x', leadRate: 0.029, label: 'B2B & Enterprise' },
    'realestate': { mult: 0.95, roas: '5.2x – 8.0x', leadRate: 0.038, label: 'Real Estate & Luxury' },
    'personal': { mult: 1.4, roas: '4.2x – 6.0x', leadRate: 0.065, label: 'Personal Branding' },
    'healthcare': { mult: 0.9, roas: '4.0x – 5.8x', leadRate: 0.04, label: 'Healthcare & Wellness' },
    'local': { mult: 1.15, roas: '4.5x – 6.2x', leadRate: 0.052, label: 'Local Business' }
  };

  function updateMetrics() {
    currentBudget = parseInt(slider.value, 10);
    budgetVal.textContent = `₹${currentBudget.toLocaleString('en-IN')}`;

    const impressions = Math.round((currentBudget / 25) * 1000 * currentNicheMultiplier);
    const clicks = Math.round(impressions * 0.026);
    const selected = Object.values(nicheData).find((n) => n.label === currentNicheTitle) || nicheData['ecom'];
    const leads = Math.round(clicks * selected.leadRate);

    metricReach.textContent = formatCompact(impressions);
    metricClicks.textContent = formatCompact(clicks);
    metricLeads.textContent = `${leads.toLocaleString('en-IN')}+`;
    metricRoas.textContent = selected.roas;

    if (claimBtn) {
      const msg = encodeURIComponent(
        `Hi! I used the Socio Ninjas Growth Simulator for ${currentNicheTitle} with a monthly budget of ₹${currentBudget.toLocaleString('en-IN')}. Let's discuss our scaling blueprint!`
      );
      claimBtn.href = `https://wa.me/919354927966?text=${msg}`;
    }
  }

  function formatCompact(num) {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M+';
    if (num >= 1000) return (num / 1000).toFixed(0) + 'K+';
    return num.toLocaleString('en-IN');
  }

  slider.addEventListener('input', updateMetrics);

  nicheBtns.forEach((btn) => {
    btn.addEventListener('click', () => {
      nicheBtns.forEach((b) => b.classList.remove('active'));
      btn.classList.add('active');
      const key = btn.getAttribute('data-niche');
      if (nicheData[key]) {
        currentNicheMultiplier = nicheData[key].mult;
        currentNicheTitle = nicheData[key].label;
      }
      updateMetrics();
    });
  });

  updateMetrics();
}

/* ==========================================================================
   5. CONTACT FORM TO WHATSAPP DISPATCH
   ========================================================================== */
function initContactForm() {
  const form = document.getElementById('agency-contact-form');
  if (!form) return;

  form.addEventListener('submit', (e) => {
    e.preventDefault();

    const name = document.getElementById('client-name')?.value || 'Client';
    const phone = document.getElementById('client-phone')?.value || '';
    const email = document.getElementById('client-email')?.value || '';
    const service = document.getElementById('client-service')?.value || 'Digital Marketing';
    const budget = document.getElementById('client-budget')?.value || 'Not specified';
    const notes = document.getElementById('client-notes')?.value || '';

    const payload = encodeURIComponent(
      `*New Project Brief — Socio Ninjas*\n\n` +
      `*Name:* ${name}\n` +
      `*Phone:* ${phone}\n` +
      `*Email:* ${email}\n` +
      `*Service Required:* ${service}\n` +
      `*Monthly Budget:* ${budget}\n` +
      `*Brief / Goals:* ${notes}\n\n` +
      `Sent via Socio Ninjas Portal`
    );

    window.open(`https://wa.me/919354927966?text=${payload}`, '_blank');
  });
}

/* ==========================================================================
   6. MOBILE NAVIGATION
   ========================================================================== */
function initMobileNav() {
  const toggle = document.querySelector('.mobile-menu-toggle');
  const menu = document.querySelector('.nav-links-menu');
  const links = document.querySelectorAll('.nav-item a:not(.nav-link-dropdown-toggle)');
  const dropdownToggle = document.querySelector('.nav-link-dropdown-toggle');
  const dropdownParent = document.querySelector('.nav-item.has-dropdown');

  if (!toggle || !menu) return;

  function closeMenu() {
    menu.classList.remove('active');
    document.body.classList.remove('mobile-nav-open');
    if (dropdownParent) dropdownParent.classList.remove('mobile-open');
    const icon = toggle.querySelector('i');
    if (icon) icon.className = 'fas fa-bars';
  }

  toggle.addEventListener('click', (e) => {
    e.stopPropagation();
    const isActive = menu.classList.toggle('active');
    document.body.classList.toggle('mobile-nav-open', isActive);
    const icon = toggle.querySelector('i');
    if (icon) {
      icon.className = isActive ? 'fas fa-times' : 'fas fa-bars';
    }
  });

  if (dropdownToggle && dropdownParent) {
    dropdownToggle.addEventListener('click', (e) => {
      if (window.innerWidth <= 768) {
        e.preventDefault();
        e.stopPropagation();
        dropdownParent.classList.toggle('mobile-open');
      }
    });
  }

  links.forEach((link) => {
    link.addEventListener('click', closeMenu);
  });

  document.addEventListener('click', (e) => {
    if (!menu.contains(e.target) && !toggle.contains(e.target)) {
      closeMenu();
    }
  });
}

/* ==========================================================================
   7. SERVICE FAQ ACCORDIONS
   ========================================================================== */
function initFaqAccordions() {
  const faqItems = document.querySelectorAll('.service-faq-item');
  faqItems.forEach((item) => {
    const header = item.querySelector('.faq-header-btn');
    if (!header) return;

    header.addEventListener('click', () => {
      const isOpen = item.classList.contains('is-open');
      faqItems.forEach((other) => other.classList.remove('is-open'));
      if (!isOpen) {
        item.classList.add('is-open');
      }
    });
  });
}


