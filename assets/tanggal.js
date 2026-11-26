// Isian tanggal dd-mm-yyyy (paket ci4-tabel-form-helper).
// Semua input type="date" diubah menjadi teks dd-mm-yyyy (kelas
// isian-tanggal) bersebelahan tombol kalender (pemilih tanggal bawaan
// peramban lewat input date tersembunyi + showPicker()); nilai yang
// dikirim formulir dikonversi balik ke format baku yyyy-mm-dd saat
// submit (fase capture, sebelum handler lain berjalan). Penyimpanan
// di server tetap yyyy-mm-dd; yang berubah hanya tampilan isian.
(function () {
  const isoKeDmy = function (v) {
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(v || '');
    return m ? m[3] + '-' + m[2] + '-' + m[1] : '';
  };
  const dmyKeIso = function (v) {
    const m = /^(\d{2})-(\d{2})-(\d{4})$/.exec((v || '').trim());
    if (!m) return '';
    const d = new Date(+m[3], +m[2] - 1, +m[1]);
    if (d.getFullYear() !== +m[3] || d.getMonth() !== +m[2] - 1 || d.getDate() !== +m[1]) return '';
    return m[3] + '-' + m[2] + '-' + m[1];
  };
  // Diekspor agar skrip lain (mis. form-modal.js) dapat memakai pola sama.
  window.isoKeDmy = isoKeDmy;
  window.dmyKeIso = dmyKeIso;

  function siapkan(inp) {
    if (inp.classList.contains('isian-tanggal') || inp.classList.contains('tanggal-pilih')) return;
    const awal = inp.value;
    inp.type = 'text';
    inp.classList.add('isian-tanggal');
    inp.setAttribute('inputmode', 'numeric');
    inp.setAttribute('placeholder', 'dd-mm-yyyy');
    inp.setAttribute('autocomplete', 'off');
    inp.value = isoKeDmy(awal);
    inp.addEventListener('input', function () {
      const a = inp.value.replace(/\D/g, '').slice(0, 8);
      inp.value = a.length > 4 ? a.slice(0, 2) + '-' + a.slice(2, 4) + '-' + a.slice(4)
        : a.length > 2 ? a.slice(0, 2) + '-' + a.slice(2) : a;
    });
    // Bungkus + tombol kalender + input pemilih tersembunyi.
    const bungkus = document.createElement('span');
    bungkus.className = 'tanggal-bungkus';
    inp.parentNode.insertBefore(bungkus, inp);
    bungkus.appendChild(inp);
    const pik = document.createElement('input');
    pik.type = 'date';
    pik.className = 'tanggal-pilih';
    pik.tabIndex = -1;
    pik.setAttribute('aria-hidden', 'true');
    bungkus.appendChild(pik);
    const tmbl = document.createElement('button');
    tmbl.type = 'button';
    tmbl.className = 'tmbl-kalender';
    tmbl.title = 'Pilih tanggal dari kalender';
    tmbl.setAttribute('aria-label', 'Pilih tanggal dari kalender');
    tmbl.innerHTML = '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>';
    bungkus.appendChild(tmbl);
    tmbl.addEventListener('click', function () {
      pik.value = dmyKeIso(inp.value) || '';
      if (typeof pik.showPicker === 'function') {
        try { pik.showPicker(); return; } catch (err) { /* lanjut ke cadangan */ }
      }
      pik.focus();
      pik.click();
    });
    pik.addEventListener('change', function () {
      if (!pik.value) return;
      inp.value = isoKeDmy(pik.value);
      inp.dispatchEvent(new Event('input', { bubbles: true }));
      inp.dispatchEvent(new Event('change', { bubbles: true }));
    });
  }

  document.querySelectorAll('input[type="date"]').forEach(siapkan);
  // Isian tanggal yang muncul belakangan (mis. konten hasil AJAX) ikut disiapkan.
  if (window.MutationObserver) {
    new MutationObserver(function (muts) {
      muts.forEach(function (m) {
        m.addedNodes.forEach(function (n) {
          if (n.nodeType !== 1) return;
          if (n.matches && n.matches('input[type="date"]')) siapkan(n);
          if (n.querySelectorAll) n.querySelectorAll('input[type="date"]').forEach(siapkan);
        });
      });
    }).observe(document.body, { childList: true, subtree: true });
  }

  document.addEventListener('submit', function (e) {
    e.target.querySelectorAll('input.isian-tanggal').forEach(function (inp) {
      if (!inp.readOnly && !inp.disabled) inp.value = dmyKeIso(inp.value);
    });
  }, true);
})();
