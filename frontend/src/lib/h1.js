export function resizeHeading(h1) {
  const container = h1.closest('main') || h1.parentElement;

  const maxFont = parseInt(h1.dataset.maxFont, 10) ||900;
  const minFont = 20;

  let low = minFont;
  let high = maxFont;
  let fontSize = minFont;

  while (low <= high) {
    const mid = Math.floor((low + high) / 2);
    h1.style.fontSize = mid + 'px';
    h1.offsetWidth;

    const fitsWidth = h1.scrollWidth <= container.clientWidth;
    const fitsHeight = h1.scrollHeight <= container.clientHeight;

    if (fitsWidth && fitsHeight) {
      fontSize = mid;
      low = mid + 1;
    } else {
      high = mid - 1;
    }
  }

  h1.style.fontSize = fontSize + 'px';
}