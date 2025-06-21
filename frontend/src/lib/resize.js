import { resizeHeading } from './h1.js';
import { onScrollEffect } from './scroll.js';

let resizeHandler;
let cleanupScroll;

export function setupResize() {
  const headings = document.querySelectorAll('main h1');

  const resizeAll = () => headings.forEach(h1 => resizeHeading(h1));

  resizeAll();

  resizeHandler = () => resizeAll();
  window.addEventListener('resize', resizeHandler);

  cleanupScroll = onScrollEffect();
}

export function cleanupResize() {
  window.removeEventListener('resize', resizeHandler);
  cleanupScroll?.();
}
