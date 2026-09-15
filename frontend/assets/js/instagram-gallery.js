(function () {
  'use strict';

  var cards = Array.prototype.slice.call(document.querySelectorAll('[data-ig-card]'));
  if (!cards.length) return;

  function play(video) {
    if (!video) return;
    if (!video.getAttribute('src')) {
      var source = video.dataset.igVideoSrc;
      if (!source) return;
      video.src = source;
      video.preload = 'metadata';
      video.load();
    }
    video.muted = true;
    video.defaultMuted = true;
    video.playsInline = true;
    video.play().catch(function () {
      video.addEventListener('canplay', function retryAutoplay() {
        var card = video.closest('[data-ig-card]');
        if (card && card.dataset.igVisible === '1') video.play().catch(function () {});
      }, { once: true });
    });
  }

  function hydrate(card) {
    if (card.dataset.igState || !card.dataset.previewUrl) return Promise.resolve();
    card.dataset.igState = 'loading';
    return fetch(card.dataset.previewUrl, { headers: { Accept: 'application/json' } })
      .then(function (response) { return response.json(); })
      .then(function (payload) {
        if (!payload.ok || !payload.media || !payload.media.image) throw new Error('Preview unavailable');

        var media = payload.media;
        var poster = card.querySelector('.ig-media-poster');
        var video = card.querySelector('.ig-media-video');
        var username = card.querySelector('[data-ig-username]');
        var caption = card.querySelector('[data-ig-caption]');
        var kind = card.querySelector('[data-ig-kind]');
        poster.src = media.image;
        if (media.username && username) username.textContent = '@' + media.username;
        if (media.caption && caption) caption.textContent = media.caption;

        if (media.video && video) {
          video.poster = media.image;
          video.dataset.igVideoSrc = media.video;
          video.hidden = false;
          kind.textContent = 'REEL';
          card.dataset.igState = 'video';
          if (card.dataset.igVisible === '1') play(video);
        } else {
          kind.textContent = media.is_video ? 'REEL' : 'POST';
          card.dataset.igState = 'image';
        }
      })
      .catch(function () {
        var kind = card.querySelector('[data-ig-kind]');
        if (kind) kind.textContent = 'INSTAGRAM';
        card.dataset.igState = 'fallback';
      });
  }

  function observe(card, observer) {
    if (card.hidden) return;
    observer.observe(card);
  }

  if ('IntersectionObserver' in window) {
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        var card = entry.target;
        var video = card.querySelector('.ig-media-video');
        card.dataset.igVisible = entry.isIntersecting ? '1' : '0';
        if (entry.isIntersecting) {
          hydrate(card);
          play(video);
        } else if (video && !video.paused) {
          video.pause();
        }
      });
    }, { rootMargin: '180px 0px', threshold: .35 });
    cards.forEach(function (card) { observe(card, observer); });
    window.observeInstagramCard = function (card) { observe(card, observer); };
  } else {
    cards.forEach(hydrate);
  }
})();
