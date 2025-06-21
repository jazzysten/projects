export function onScrollEffect(currentPath) {
  const elements = document.querySelectorAll('main p, main a');
  const mainImg = document.querySelector('.main-img');

  const onScroll = () => {
    elements.forEach(el => {
      if (!(el instanceof HTMLElement)) return;

      if (window.scrollY > 10) {
        el.style.opacity = '0';
        el.style.pointerEvents = 'none';
      } else {
        el.style.opacity = '1';
        el.style.pointerEvents = 'auto';
      }
    });

    if (mainImg instanceof HTMLElement && currentPath === '/') {
      if (window.scrollY > 10) {
        mainImg.style.opacity = '0';
        mainImg.style.pointerEvents = 'none';
      } else {
        mainImg.style.opacity = '1';
        mainImg.style.pointerEvents = 'auto';
      }
    }
  };

  onScroll();

  window.addEventListener('scroll', onScroll);

  return () => {
    window.removeEventListener('scroll', onScroll);
  };
}

export function scrollToId(id) {
  const el = document.getElementById(id);
  if (el) {
    el.scrollIntoView({ behavior: 'smooth' });
  }
}
  