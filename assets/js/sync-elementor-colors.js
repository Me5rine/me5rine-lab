// File: js/sync-elementor-colors.js

function getElementorColors() {
  const styles = getComputedStyle(document.documentElement);
  const colors = {};

  for (let i = 0; i < styles.length; i++) {
    const prop = styles[i];
    if (prop.startsWith('--e-global-color-')) {
      colors[prop] = styles.getPropertyValue(prop).trim();
    }
  }

  return colors;
}

document.addEventListener('DOMContentLoaded', () => {
  const iframe = document.querySelector('iframe[src*="rafflepress"]');
  if (!iframe) return;

  iframe.addEventListener('load', () => {
    const colors = getElementorColors();

    iframe.contentWindow.postMessage({
      type: 'syncElementorColors',
      colors: colors
    }, '*');
  });
});
