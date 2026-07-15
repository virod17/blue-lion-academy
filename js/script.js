// Menú móvil
  const menuToggle = document.getElementById('menuToggle');
  const navMenu = document.getElementById('navMenu');

  menuToggle.addEventListener('click', () => {
    const isOpen = navMenu.classList.toggle('active');
    menuToggle.setAttribute('aria-expanded', isOpen);
    menuToggle.setAttribute('aria-label', isOpen ? 'Cerrar menú' : 'Abrir menú');
  });

  navMenu.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
      navMenu.classList.remove('active');
      menuToggle.setAttribute('aria-expanded', 'false');
      menuToggle.setAttribute('aria-label', 'Abrir menú');
    });
  });

  // Revelado progresivo al hacer scroll
  const revealEls = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    revealEls.forEach(el => observer.observe(el));
  } else {
    revealEls.forEach(el => el.classList.add('is-visible'));
  }

  // Carrusel de instalaciones
  const carousel = document.getElementById('facilitiesCarousel');
  if (carousel) {
    const track = document.getElementById('carouselTrack');
    const slides = Array.from(track.children);
    const prevBtn = document.getElementById('carouselPrev');
    const nextBtn = document.getElementById('carouselNext');
    const dotsWrap = document.getElementById('carouselDots');
    let current = 0;
    let autoplayTimer = null;

    slides.forEach((_, i) => {
      const dot = document.createElement('button');
      dot.type = 'button';
      dot.setAttribute('role', 'tab');
      dot.setAttribute('aria-label', 'Ir a la foto ' + (i + 1));
      dot.addEventListener('click', () => goTo(i, true));
      dotsWrap.appendChild(dot);
    });
    const dots = Array.from(dotsWrap.children);

    function render() {
      track.style.transform = 'translateX(-' + (current * 100) + '%)';
      dots.forEach((dot, i) => dot.classList.toggle('is-active', i === current));
    }

    function goTo(index, userAction) {
      current = (index + slides.length) % slides.length;
      render();
      if (userAction) restartAutoplay();
    }

    function next(userAction) { goTo(current + 1, userAction); }
    function prev(userAction) { goTo(current - 1, userAction); }

    function startAutoplay() {
      autoplayTimer = setInterval(() => next(false), 5000);
    }
    function restartAutoplay() {
      clearInterval(autoplayTimer);
      startAutoplay();
    }

    prevBtn.addEventListener('click', () => prev(true));
    nextBtn.addEventListener('click', () => next(true));

    carousel.addEventListener('mouseenter', () => clearInterval(autoplayTimer));
    carousel.addEventListener('mouseleave', startAutoplay);

    carousel.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowRight') next(true);
      if (e.key === 'ArrowLeft') prev(true);
    });

    // Deslizar con el dedo en móvil
    let touchStartX = null;
    track.addEventListener('touchstart', (e) => {
      touchStartX = e.touches[0].clientX;
      clearInterval(autoplayTimer);
    }, { passive: true });
    track.addEventListener('touchend', (e) => {
      if (touchStartX === null) return;
      const delta = e.changedTouches[0].clientX - touchStartX;
      if (delta > 40) prev(true);
      else if (delta < -40) next(true);
      else restartAutoplay();
      touchStartX = null;
    });

    render();
    startAutoplay();
  }