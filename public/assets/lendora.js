/*! Lendora UI runtime — progressive enhancement only (no framework, ~5kb) */
(function () {
  'use strict';

  const $ = (sel, root) => (root || document).querySelector(sel);
  const $$ = (sel, root) => Array.from((root || document).querySelectorAll(sel));
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const nfID = new Intl.NumberFormat('id-ID');

  /* ── Dropdown menus ─────────────────────────────────────────────────── */
  function initMenus() {
    const triggers = $$('[data-menu]');
    triggers.forEach((trigger) => {
      trigger.setAttribute('aria-haspopup', 'true');
      trigger.setAttribute('aria-expanded', 'false');
      trigger.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const menu = document.getElementById(trigger.dataset.menu);
        if (!menu) return;
        const open = !menu.hasAttribute('hidden');
        closeAllMenus();
        if (!open) {
          menu.removeAttribute('hidden');
          trigger.setAttribute('aria-expanded', 'true');
          const first = menu.querySelector('a, button');
          if (first) first.focus({ preventScroll: true });
        }
      });
    });
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.menu')) closeAllMenus();
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeAllMenus();
    });
  }
  function closeAllMenus() {
    $$('.menu').forEach((m) => m.setAttribute('hidden', ''));
    $$('[data-menu]').forEach((t) => t.setAttribute('aria-expanded', 'false'));
  }

  /* ── Mobile rail ────────────────────────────────────────────────────── */
  function initRail() {
    const rail = $('[data-mobile-rail]');
    const scrim = $('[data-scrim]');
    if (!rail || !scrim) return;
    const open = () => {
      rail.classList.add('is-open');
      scrim.removeAttribute('hidden');
      document.body.style.overflow = 'hidden';
    };
    const close = () => {
      rail.classList.remove('is-open');
      scrim.setAttribute('hidden', '');
      document.body.style.overflow = '';
    };
    $$('[data-rail-open]').forEach((b) => b.addEventListener('click', open));
    $$('[data-rail-close]').forEach((b) => b.addEventListener('click', close));
    scrim.addEventListener('click', close);
    rail.addEventListener('click', (e) => { if (e.target.closest('a[href]')) close(); });
    document.addEventListener('keydown', (e) => e.key === 'Escape' && close());
  }

  /* ── Flash messages: auto-dismiss + close ───────────────────────────── */
  function initFlash() {
    $$('.alert[data-autoclose]').forEach((el) => {
      const wait = parseInt(el.dataset.autoclose, 10) || 7000;
      const kill = () => {
        el.classList.add('is-leaving');
        el.addEventListener('animationend', () => el.remove(), { once: true });
        setTimeout(() => el.remove(), 600);
      };
      setTimeout(kill, wait);
      const btn = el.querySelector('[data-close]');
      if (btn) btn.addEventListener('click', kill);
    });
  }

  /* ── Animated counters ──────────────────────────────────────────────── */
  function initCounters() {
    const nodes = $$('[data-count]');
    if (!nodes.length) return;
    if (reduced) {
      nodes.forEach((n) => (n.textContent = nfID.format(parseFloat(n.dataset.count) || 0)));
      return;
    }
    const run = (node) => {
      const target = parseFloat(node.dataset.count) || 0;
      const dur = 780;
      const t0 = performance.now();
      const tick = (t) => {
        const p = Math.min(1, (t - t0) / dur);
        const eased = 1 - Math.pow(1 - p, 3);
        node.textContent = nfID.format(Math.round(target * eased));
        if (p < 1) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    };
    const io = new IntersectionObserver(
      (entries) => entries.forEach((e) => {
        if (e.isIntersecting) { run(e.target); io.unobserve(e.target); }
      }),
      { threshold: 0.3 }
    );
    nodes.forEach((n) => io.observe(n));
  }

  /* ── Confirm dialog (replaces window.confirm) ───────────────────────── */
  function initConfirm() {
    const host = document.createElement('div');
    host.className = 'modal';
    host.hidden = true;
    host.innerHTML =
      '<div class="modal__scrim" data-cancel></div>' +
      '<div class="modal__panel" role="alertdialog" aria-modal="true" aria-labelledby="cf-t">' +
      '<h3 id="cf-t">Konfirmasi</h3><p data-text></p>' +
      '<div class="modal__actions">' +
      '<button type="button" class="btn btn--ghost" data-cancel>Batal</button>' +
      '<button type="button" class="btn btn--danger" data-ok>Lanjutkan</button>' +
      '</div></div>';
    document.body.appendChild(host);
    const text = host.querySelector('[data-text]');
    let armed = null;

    const close = () => { host.hidden = true; armed = null; };
    host.querySelectorAll('[data-cancel]').forEach((b) => b.addEventListener('click', close));
    host.querySelector('[data-ok]').addEventListener('click', () => { const f = armed; close(); if (f) f(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !host.hidden) close(); });

    document.addEventListener('submit', (e) => {
      const msg = e.target.dataset.confirm;
      if (!msg || e.defaultPrevented) return;
      e.preventDefault();
      armed = () => e.target.submit();
      text.textContent = msg;
      host.hidden = false;
      host.querySelector('[data-ok]').focus();
    });
    document.addEventListener('click', (e) => {
      const a = e.target.closest('a[data-confirm]');
      if (!a) return;
      e.preventDefault();
      armed = () => { window.location.href = a.href; };
      text.textContent = a.dataset.confirm;
      host.hidden = false;
      host.querySelector('[data-ok]').focus();
    });
  }

  /* ── Auto-submit filters ────────────────────────────────────────────── */
  function initAutoSubmit() {
    $$('[data-autosubmit]').forEach((el) => el.addEventListener('change', () => el.form && el.form.submit()));
  }

  /* ── File input preview ─────────────────────────────────────────────── */
  function initFilePreview() {
    $$('input[type=file][data-preview]').forEach((input) => {
      const out = document.getElementById(input.dataset.preview);
      if (!out) return;
      input.addEventListener('change', () => {
        const f = input.files && input.files[0];
        if (!f) { out.innerHTML = ''; return; }
        const size = (f.size / 1048576).toFixed(2);
        out.innerHTML =
          '<div class="inline-alert tone-brand" style="margin-top:8px">' +
          '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7V5a1 1 0 011-1h3l2 2h7a1 1 0 011 1v2M4 7h16a1 1 0 011 1v10a1 1 0 01-1 1H4a1 1 0 01-1-1V8a1 1 0 011-1z"/></svg>' +
          '<div><b>' + f.name + '</b> — ' + size + ' MB</div></div>';
      });
    });
  }

  /* ── Search shortcut "/" ────────────────────────────────────────────── */
  function initShortcuts() {
    document.addEventListener('keydown', (e) => {
      const tag = (e.target.tagName || '').toLowerCase();
      if (e.key === '/' && !/input|textarea|select/.test(tag) && !e.metaKey && !e.ctrlKey) {
        const s = $('[data-search-input]');
        if (s) { e.preventDefault(); s.focus(); s.select(); }
      }
    });
  }

  /* ── Tabs ───────────────────────────────────────────────────────────── */
  function initTabs() {
    $$('[data-tabs]').forEach((group) => {
      const tabs = $$('[data-tab]', group);
      tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
          const name = tab.dataset.tab;
          tabs.forEach((t) => {
            const on = t === tab;
            t.classList.toggle('is-active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
          });
          $$('[data-panel]', group.parentNode).forEach((p) => {
            p.hidden = p.dataset.panel !== name;
          });
        });
      });
    });
  }

  /* ── Submit loading state ───────────────────────────────────────────── */
  function initSubmitState() {
    document.addEventListener('submit', (e) => {
      const btn = e.target.querySelector('[type=submit]');
      if (!btn || btn.dataset.busy === '1') return;
      const original = btn.innerHTML;
      btn.dataset.busy = '1';
      btn.innerHTML = '<span class="spinner"></span> Memproses…';
      btn.disabled = true;
      setTimeout(() => { btn.disabled = false; btn.innerHTML = original; delete btn.dataset.busy; }, 8000);
    });
  }

  /* ── Fade tepi strip horizontal ───────────────────────────────────────
     Menjawab satu masalah nyata di tempat kerja: workbar (status tiket /
     antrean staff) digeser ke samping, dan tanpa isyarat visual chip terakhir
     terpotong. Atribut data-scroll diisi dari ukuran elemen, jadi fade hanya
     muncul kalau strip benar-benar bisa digeser. Nilainya:
     none | start | middle | end.                                            */
  function initScrollShadow() {
    const nodes = $$('.workbar, .table-wrap');
    if (!nodes.length) return;
    const update = (el) => {
      const max = el.scrollWidth - el.clientWidth;
      if (max <= 2) { el.dataset.scroll = 'none'; return; }
      const x = Math.round(el.scrollLeft);
      el.dataset.scroll = x <= 2 ? 'start' : x >= max - 2 ? 'end' : 'middle';
    };
    const ro = new ResizeObserver((entries) => entries.forEach((e) => update(e.target)));
    nodes.forEach((el) => {
      update(el);
      ro.observe(el);
      el.addEventListener('scroll', () => update(el), { passive: true });
    });
  }

  /* ── Show/hide password ────────────────────────────────────────────────
     Dipakai di login dan di form ganti-password (/profile). Handler-nya
     dipasang global di file ini, bukan inline di tiap blade, supaya tidak
     ada duplikasi dan aria-pressed selalu sinkron dengan state input. */
  function initPasswordToggle() {
    $$('[data-pw-toggle]').forEach((btn) => {
      const input = document.getElementById(btn.dataset.pwToggle);
      if (!input) return;
      btn.addEventListener('click', () => {
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.classList.toggle('is-visible', show);
        btn.setAttribute('aria-pressed', String(show));
        btn.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
        // Fokus tetap di input supaya keyboard tidak terlempar ke body.
        input.focus({ preventScroll: true });
      });
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    initMenus();
    initRail();
    initFlash();
    initCounters();
    initConfirm();
    initAutoSubmit();
    initFilePreview();
    initShortcuts();
    initTabs();
    initSubmitState();
    initScrollShadow();
    initPasswordToggle();
  });
})();
