/* TDA Currents editor. Vanilla JS, no build step.
 * Everything here is a convenience layer: the server re-sanitizes every
 * field on save, so nothing below is trusted with the design's safety. */
(function () {
  'use strict';

  var form = document.getElementById('issue-form');

  /* ================= Rich text (bold / italic / clear only) ================= */

  // Serialize a contenteditable's inline content to the allowed marks.
  function inlineHTML(node) {
    var out = '';
    node.childNodes.forEach(function (c) {
      if (c.nodeType === Node.TEXT_NODE) {
        out += c.textContent.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
      } else if (c.nodeType === Node.ELEMENT_NODE) {
        var tag = c.tagName;
        if (tag === 'BR') { out += '<br>'; return; }
        var inner = inlineHTML(c);
        var bold = tag === 'B' || tag === 'STRONG' ||
                   (c.style && (c.style.fontWeight === 'bold' || parseInt(c.style.fontWeight, 10) >= 600));
        var italic = tag === 'I' || tag === 'EM' || (c.style && c.style.fontStyle === 'italic');
        if (bold) inner = '<strong>' + inner + '</strong>';
        if (italic) inner = '<em>' + inner + '</em>';
        out += inner;
      }
    });
    return out;
  }

  // Multi-paragraph fields store paragraphs separated by blank lines.
  function serializeRich(area) {
    var multi = area.dataset.multi === '1';
    if (!multi) return inlineHTML(area).replace(/<br>/g, ' ').trim();
    var paras = [];
    var pending = '';
    area.childNodes.forEach(function (c) {
      var isBlock = c.nodeType === Node.ELEMENT_NODE && /^(P|DIV)$/.test(c.tagName);
      if (isBlock) {
        if (pending.trim()) { paras.push(pending.trim()); pending = ''; }
        var t = inlineHTML(c).replace(/<br>/g, ' ').trim();
        if (t) paras.push(t);
      } else {
        pending += c.nodeType === Node.TEXT_NODE
          ? c.textContent.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
          : inlineHTML({ childNodes: [c] });
      }
    });
    if (pending.trim()) paras.push(pending.trim());
    return paras.join('\n\n');
  }

  document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('.rich-btn');
    if (!btn) return;
    ev.preventDefault();
    var area = btn.closest('.rich').querySelector('.rich-area');
    area.focus();
    document.execCommand(btn.dataset.cmd === 'clear' ? 'removeFormat' : btn.dataset.cmd, false, null);
  });

  document.querySelectorAll('.rich-area').forEach(function (area) {
    // Paste as plain text: the single most important safeguard (section 12).
    area.addEventListener('paste', function (ev) {
      ev.preventDefault();
      var text = (ev.clipboardData || window.clipboardData).getData('text/plain');
      document.execCommand('insertText', false, text);
    });
    // Single-line fields have no paragraphs.
    if (area.dataset.multi !== '1') {
      area.addEventListener('keydown', function (ev) {
        if (ev.key === 'Enter') ev.preventDefault();
      });
    }
  });

  if (form) {
    form.addEventListener('submit', function () {
      form.querySelectorAll('.rich').forEach(function (rich) {
        rich.querySelector('.rich-store').value = serializeRich(rich.querySelector('.rich-area'));
      });
    });
  }

  /* ================= Icon picker ================= */

  var palette = document.getElementById('icon-palette');
  var openField = null;

  function closePalette() {
    if (!palette) return;
    palette.hidden = true;
    openField = null;
  }

  document.addEventListener('click', function (ev) {
    if (!palette) return;
    var btn = ev.target.closest('.icon-btn');
    if (btn) {
      var field = btn.closest('.icon-field');
      if (openField === field) { closePalette(); return; }
      openField = field;
      field.appendChild(palette);
      palette.style.top = (btn.offsetTop + btn.offsetHeight + 4) + 'px';
      palette.style.left = '0';
      palette.hidden = false;
      return;
    }
    var choice = ev.target.closest('.icon-choice');
    if (choice && openField) {
      var iconBtn = openField.querySelector('.icon-btn');
      openField.querySelector('input[type="hidden"]').value = choice.dataset.icon;
      iconBtn.innerHTML = choice.innerHTML;
      iconBtn.dataset.icon = choice.dataset.icon;
      closePalette();
      return;
    }
    if (!ev.target.closest('#icon-palette')) closePalette();
  });

  /* ================= Mode toggles ================= */

  function bindModeToggle(sectionId, radioName, panelAttr) {
    var section = document.getElementById(sectionId);
    if (!section) return;
    function apply() {
      var val = section.querySelector('input[name="' + radioName + '"]:checked').value;
      section.querySelectorAll('[' + panelAttr + ']').forEach(function (p) {
        p.hidden = p.getAttribute(panelAttr) !== val;
      });
    }
    section.querySelectorAll('input[name="' + radioName + '"]').forEach(function (r) {
      r.addEventListener('change', apply);
    });
    apply();
  }

  bindModeToggle('flex-section', 'flex[mode]', 'data-mode');
  bindModeToggle('sponsor-section', 'sponsor[mode]', 'data-mode');
  bindModeToggle('sponsor-section', 'sponsor[ad][sub_mode]', 'data-sub');

  /* ================= Photo widgets ================= */

  document.querySelectorAll('.photo-widget').forEach(function (widget) {
    var toggle = widget.querySelector('.photo-toggle input');
    var controls = widget.querySelector('.photo-controls');
    var frame = widget.querySelector('.photo-frame');
    var img = frame.querySelector('img');
    var srcInput = widget.querySelector('.photo-src');
    var posInput = widget.querySelector('.photo-pos');
    var treatment = widget.querySelector('.photo-treatment');
    var fileInput = widget.querySelector('.photo-upload');
    var status = widget.querySelector('.photo-upload-status');

    function applyToggle() { controls.hidden = !toggle.checked; }
    toggle.addEventListener('change', applyToggle);
    applyToggle();

    treatment.addEventListener('change', function () {
      frame.className = 'photo-frame treatment-' + treatment.value;
    });

    fileInput.addEventListener('change', function () {
      if (!fileInput.files.length) return;
      var fd = new FormData();
      fd.append('photo', fileInput.files[0]);
      status.textContent = 'Uploading…';
      status.classList.remove('err');
      fetch('?action=upload&issue=' + encodeURIComponent(form.dataset.issue), { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (!res.ok) throw new Error(res.error || 'Upload failed.');
          srcInput.value = res.src;
          img.src = 'uploads/' + res.src;
          frame.dataset.empty = '0';
          posInput.value = '50% 50%';
          img.style.objectPosition = '50% 50%';
          toggle.checked = true;
          applyToggle();
          status.textContent = 'Uploaded. Remember to Save.';
        })
        .catch(function (err) {
          status.textContent = err.message;
          status.classList.add('err');
        });
    });

    // Drag to frame: adjusts object-position percentages.
    var drag = null;
    frame.addEventListener('pointerdown', function (ev) {
      if (frame.dataset.empty === '1') return;
      var m = /^(\d+)% (\d+)%$/.exec(posInput.value) || [0, 50, 50];
      drag = { x: ev.clientX, y: ev.clientY, px: +m[1], py: +m[2] };
      frame.setPointerCapture(ev.pointerId);
    });
    frame.addEventListener('pointermove', function (ev) {
      if (!drag) return;
      // Dragging right shows more of the image's left side (position % falls).
      var nx = Math.max(0, Math.min(100, drag.px - (ev.clientX - drag.x) / frame.clientWidth * 100));
      var ny = Math.max(0, Math.min(100, drag.py - (ev.clientY - drag.y) / frame.clientHeight * 100));
      posInput.value = Math.round(nx) + '% ' + Math.round(ny) + '%';
      img.style.objectPosition = posInput.value;
    });
    ['pointerup', 'pointercancel'].forEach(function (evName) {
      frame.addEventListener(evName, function () { drag = null; });
    });
  });

  /* ================= All Hands: 1–3 items ================= */

  var ahWrap = document.getElementById('all-hands-items');
  var ahAdd = document.getElementById('ah-add');

  function ahItems() { return ahWrap ? ahWrap.querySelectorAll('.ah-item') : []; }

  function ahReindex() {
    ahItems().forEach(function (item, i) {
      item.querySelectorAll('[name]').forEach(function (el) {
        el.name = el.name.replace(/all_hands\[items\]\[\d+\]/, 'all_hands[items][' + i + ']');
      });
    });
    var n = ahItems().length;
    if (ahAdd) ahAdd.hidden = n >= 3;
    ahItems().forEach(function (item) {
      item.querySelector('.ah-remove').hidden = n <= 1;
    });
  }

  if (ahWrap && ahAdd) {
    ahAdd.addEventListener('click', function () {
      if (ahItems().length >= 3) return;
      var clone = ahItems()[0].cloneNode(true);
      clone.querySelectorAll('input[type="text"]').forEach(function (el) { el.value = ''; });
      clone.querySelectorAll('.rich-area').forEach(function (el) { el.innerHTML = ''; });
      clone.querySelectorAll('.rich-store').forEach(function (el) { el.value = ''; });
      // Fresh items start on the fallback icon.
      var iconBtn = clone.querySelector('.icon-btn');
      var anchor = document.querySelector('#icon-palette .icon-choice[data-icon="anchor"]');
      if (iconBtn && anchor) { iconBtn.innerHTML = anchor.innerHTML; iconBtn.dataset.icon = 'anchor'; }
      clone.querySelector('.icon-field input[type="hidden"]').value = 'anchor';
      // The cloned single-line rich area needs its Enter/paste handlers back.
      clone.querySelectorAll('.rich-area').forEach(function (area) {
        area.addEventListener('paste', function (ev) {
          ev.preventDefault();
          document.execCommand('insertText', false, (ev.clipboardData || window.clipboardData).getData('text/plain'));
        });
        area.addEventListener('keydown', function (ev) {
          if (ev.key === 'Enter') ev.preventDefault();
        });
      });
      ahWrap.appendChild(clone);
      ahReindex();
    });

    ahWrap.addEventListener('click', function (ev) {
      var btn = ev.target.closest('.ah-remove');
      if (!btn || ahItems().length <= 1) return;
      btn.closest('.ah-item').remove();
      ahReindex();
    });

    ahReindex();
  }

  /* ================= Overflow check (soft warning, section 12) ================= */

  window.addEventListener('message', function (ev) {
    if (!ev.data || ev.data.tda !== 'fit') return;
    var report = document.getElementById('fit-report');
    if (!report) return;
    var limit = ev.data.limit || 1056;
    var overs = [];
    ev.data.pages.forEach(function (h, i) {
      if (h > limit) overs.push({ page: i + 1, px: h - limit });
    });
    report.hidden = false;
    if (!overs.length) {
      report.className = 'fit-report fit-ok';
      report.textContent = 'Both pages fit the printed sheet.';
    } else {
      report.className = 'fit-report fit-over';
      report.innerHTML = overs.map(function (o) {
        var lines = Math.max(1, Math.round(o.px / 20));
        return '⚠️ Page ' + o.page + ' is too tall by about ' + lines +
               (lines === 1 ? ' line' : ' lines') + ' — the bottom will be cut off in print.';
      }).join('<br>') +
      '<small>Trim text in the longest section, then Save to re-check. Saving is still allowed.</small>';
    }
  });
})();
