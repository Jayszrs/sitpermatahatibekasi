(function () {
  'use strict';

  var cards = Array.prototype.slice.call(document.querySelectorAll('[data-youtube-card]'));
  if (!cards.length) return;

  function start(card) {
    var frame = card.querySelector('iframe[data-youtube-src]');
    if (frame && frame.dataset.youtubeActive !== '1') {
      frame.dataset.youtubeActive = '1';
      frame.src = frame.dataset.youtubeSrc;
    }
  }

  function stop(card) {
    var frame = card.querySelector('iframe[data-youtube-src]');
    if (frame && frame.dataset.youtubeActive === '1') {
      delete frame.dataset.youtubeActive;
      frame.src = 'about:blank';
    }
  }

  if (!('IntersectionObserver' in window)) {
    cards.forEach(start);
    return;
  }

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) start(entry.target);
      else stop(entry.target);
    });
  }, { rootMargin: '120px 0px', threshold: 0.45 });

  cards.forEach(function (card) { observer.observe(card); });
})();
