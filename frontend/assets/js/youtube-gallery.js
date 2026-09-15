(function () {
  'use strict';

  var cards = Array.prototype.slice.call(document.querySelectorAll('[data-youtube-card]'));
  if (!cards.length) return;

  function start(card) {
    var frame = card.querySelector('iframe[data-youtube-src]');
    if (frame && frame.dataset.youtubeActive !== '1') {
      frame.dataset.youtubeActive = '1';
      frame.src = frame.dataset.youtubeSrc;
      card.classList.add('is-playing');
    }
  }

  cards.forEach(function (card) {
    var play = card.querySelector('[data-youtube-play]');
    if (play) play.addEventListener('click', function () { start(card); });
  });
})();
