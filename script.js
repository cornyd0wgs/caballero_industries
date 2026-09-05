document.addEventListener('DOMContentLoaded', function () {

  /* -------------------------------------------------------------
     1. MOBILE HAMBURGER MENU
     Toggles the slide-in nav on small screens and closes it again
     whenever a link is tapped or the user resizes back to desktop.
  ---------------------------------------------------------------- */
  const hamburgerBtn = document.getElementById('hamburgerBtn');
  const mainNav = document.getElementById('mainNav');

  function closeMenu() {
    mainNav.classList.remove('is-open');
    hamburgerBtn.classList.remove('is-active');
    hamburgerBtn.setAttribute('aria-expanded', 'false');
  }

  function toggleMenu() {
    const isOpen = mainNav.classList.toggle('is-open');
    hamburgerBtn.classList.toggle('is-active', isOpen);
    hamburgerBtn.setAttribute('aria-expanded', String(isOpen));
  }

  if (hamburgerBtn && mainNav) {
    hamburgerBtn.addEventListener('click', toggleMenu);

    // Close the menu after a nav link is tapped (mobile)
    mainNav.querySelectorAll('.nav-link').forEach(function (link) {
      link.addEventListener('click', closeMenu);
    });

    // If the viewport is resized back up to desktop width, reset the menu
    window.addEventListener('resize', function () {
      if (window.innerWidth > 640) {
        closeMenu();
      }
    });
  }


  /* -------------------------------------------------------------
     2. SMOOTH SCROLLING NAVIGATION
     Handles in-page anchor links (e.g. "#gallery") so they scroll
     smoothly and land below the fixed header instead of under it.
  ---------------------------------------------------------------- */
  const header = document.getElementById('siteHeader');

  document.querySelectorAll('a[href^="#"]').forEach(function (link) {
    link.addEventListener('click', function (e) {
      const targetId = this.getAttribute('href');
      if (targetId.length < 2) return; // ignore bare "#" links

      const targetEl = document.querySelector(targetId);
      if (!targetEl) return;

      e.preventDefault();

      const headerHeight = header ? header.offsetHeight : 0;
      const targetPosition = targetEl.getBoundingClientRect().top + window.pageYOffset - headerHeight;

      window.scrollTo({
        top: targetPosition,
        behavior: 'smooth'
      });
    });
  });


  /* -------------------------------------------------------------
     3. ACTIVE NAVIGATION STATE
     Highlights the nav link for whichever section is currently
     in view as the user scrolls down the page.
  ---------------------------------------------------------------- */
  const navLinks = document.querySelectorAll('.nav-link');
  const sections = Array.from(navLinks)
    .map(function (link) {
      const id = link.getAttribute('href');
      return id && id.length > 1 ? document.querySelector(id) : null;
    })
    .filter(Boolean);

  function setActiveLink() {
    const headerHeight = header ? header.offsetHeight : 0;
    const scrollPos = window.pageYOffset + headerHeight + 40;

    let currentSectionId = sections.length ? '#' + sections[0].id : null;

    sections.forEach(function (section) {
      if (section.offsetTop <= scrollPos) {
        currentSectionId = '#' + section.id;
      }
    });

    navLinks.forEach(function (link) {
      link.classList.toggle('active', link.getAttribute('href') === currentSectionId);
    });
  }

  if (sections.length) {
    window.addEventListener('scroll', setActiveLink, { passive: true });
    setActiveLink();
  }


  /* -------------------------------------------------------------
     4. PRODUCT CARD HOVER / INTERACTIONS
     The zoom effect is handled purely in CSS (see .product-card:hover).
     Here we add a small keyboard-accessible focus state so the same
     hover styling applies when a card is tabbed to.
  ---------------------------------------------------------------- */
  document.querySelectorAll('.product-card').forEach(function (card) {
    card.setAttribute('tabindex', '0');

    card.addEventListener('focus', function () {
      card.classList.add('is-focused');
    });

    card.addEventListener('blur', function () {
      card.classList.remove('is-focused');
    });
  });


  /* -------------------------------------------------------------
     5. IMAGE FALLBACK
     If an image fails to load (e.g. a placeholder file was removed
     before the real product photo was added), swap in a simple
     inline SVG placeholder instead of showing a broken image icon.
  ---------------------------------------------------------------- */
  const fallbackSVG = {
    hero: 'data:image/svg+xml;utf8,' + encodeURIComponent(
      '<svg xmlns="http://www.w3.org/2000/svg" width="1920" height="1080">' +
      '<rect width="100%" height="100%" fill="%230B0D0F"/>' +
      '<text x="50%" y="50%" fill="%238A8D90" font-family="monospace" font-size="28" text-anchor="middle">IMAGE NOT FOUND</text>' +
      '</svg>'
    ),
    product: 'data:image/svg+xml;utf8,' + encodeURIComponent(
      '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="1000">' +
      '<rect width="100%" height="100%" fill="%2315181B"/>' +
      '<text x="50%" y="50%" fill="%238A8D90" font-family="monospace" font-size="20" text-anchor="middle">IMAGE NOT FOUND</text>' +
      '</svg>'
    )
  };

  document.querySelectorAll('img[data-fallback]').forEach(function (img) {
    img.addEventListener('error', function () {
      const type = img.getAttribute('data-fallback');
      img.src = fallbackSVG[type] || fallbackSVG.product;
    }, { once: true });
  });

  // Logo images get a simple text fallback rather than an SVG swap
  document.querySelectorAll('.logo-img, .footer-logo').forEach(function (img) {
    img.addEventListener('error', function () {
      const span = document.createElement('span');
      span.textContent = 'CABALLERO INDUSTRIES';
      span.style.fontFamily = "'Orbitron', sans-serif";
      span.style.fontWeight = '700';
      span.style.color = '#F5F5F5';
      span.style.fontSize = '16px';
      span.style.letterSpacing = '0.04em';
      img.replaceWith(span);
    }, { once: true });
  });


  /* -------------------------------------------------------------
     6. CONTACT FORM VALIDATION
     Only runs if a form with id="contactForm" exists on the page.
     Safe to leave in place even before a contact form is added.
  ---------------------------------------------------------------- */
  const contactForm = document.getElementById('contactForm');

  if (contactForm) {
    contactForm.addEventListener('submit', function (e) {
      e.preventDefault();

      let isValid = true;
      const nameField = contactForm.querySelector('[name="name"]');
      const emailField = contactForm.querySelector('[name="email"]');
      const messageField = contactForm.querySelector('[name="message"]');
      const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      [nameField, emailField, messageField].forEach(function (field) {
        if (field) clearFieldError(field);
      });

      if (nameField && nameField.value.trim() === '') {
        showFieldError(nameField, 'Please enter your name.');
        isValid = false;
      }

      if (emailField && !emailPattern.test(emailField.value.trim())) {
        showFieldError(emailField, 'Please enter a valid email address.');
        isValid = false;
      }

      if (messageField && messageField.value.trim() === '') {
        showFieldError(messageField, 'Please enter a message.');
        isValid = false;
      }

      if (isValid) {
        contactForm.reset();
        const successMsg = contactForm.querySelector('.form-success');
        if (successMsg) {
          successMsg.textContent = 'Message sent. We\u2019ll be in touch shortly.';
        }
      }
    });
  }

  function showFieldError(field, message) {
    field.classList.add('field-error');
    const errorEl = document.createElement('p');
    errorEl.className = 'field-error-message';
    errorEl.textContent = message;
    field.insertAdjacentElement('afterend', errorEl);
  }

  function clearFieldError(field) {
    field.classList.remove('field-error');
    const next = field.nextElementSibling;
    if (next && next.classList.contains('field-error-message')) {
      next.remove();
    }
  }

});
