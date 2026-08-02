/* ============================================================
   LakbayCash — Shared JS
   ============================================================ */

// ── Toast ────────────────────────────────────────────────────
function showToast(msg, type) {
  var t = document.createElement('div');
  t.className = 'toast' + (type ? ' ' + type : '');
  t.textContent = msg;
  document.body.appendChild(t);
  requestAnimationFrame(function(){ t.classList.add('show'); });
  setTimeout(function(){
    t.classList.remove('show');
    setTimeout(function(){ t.remove(); }, 300);
  }, 3000);
}

// ── Modal ────────────────────────────────────────────────────
function openModal(id) {
  var m = document.getElementById(id);
  if (m) m.classList.add('show');
}
function closeModal(id) {
  var m = document.getElementById(id);
  if (m) m.classList.remove('show');
}

// ── OTP Inputs auto-advance ──────────────────────────────────
function setupOtpInputs(container) {
  var inputs = container.querySelectorAll('input');
  inputs.forEach(function(inp, i) {
    inp.addEventListener('input', function() {
      this.value = this.value.replace(/[^0-9]/g, '');
      if (this.value.length === 1 && i < inputs.length - 1) inputs[i + 1].focus();
    });
    inp.addEventListener('keydown', function(e) {
      if (e.key === 'Backspace' && !this.value && i > 0) inputs[i - 1].focus();
    });
    inp.addEventListener('paste', function(e) {
      e.preventDefault();
      var data = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g,'');
      data.split('').forEach(function(c, j) { if (inputs[j]) { inputs[j].value = c; } });
      var last = Math.min(data.length, inputs.length) - 1;
      if (inputs[last]) inputs[last].focus();
    });
  });
}

// ── Copy to clipboard ────────────────────────────────────────
function copyText(text, btnEl) {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(text).then(function(){ showToast('Copied to clipboard'); });
  } else {
    var ta = document.createElement('textarea');
    ta.value = text; document.body.appendChild(ta); ta.select();
    try { document.execCommand('copy'); showToast('Copied to clipboard'); } catch(e){}
    document.body.removeChild(ta);
  }
}

// ── Upload preview ───────────────────────────────────────────
function setupUpload(box) {
  var input = box.querySelector('input[type="file"]');
  var preview = box.querySelector('.upload-preview');
  if (!input) return;
  box.addEventListener('click', function(e) {
    if (e.target.tagName !== 'INPUT') input.click();
  });
  input.addEventListener('change', function() {
    var file = this.files[0];
    if (!file) return;
    box.classList.add('has-file');
    var label = box.querySelector('.upload-label');
    if (label) label.textContent = file.name;
    if (preview && file.type.startsWith('image/')) {
      var reader = new FileReader();
      reader.onload = function(e) {
        preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
      };
      reader.readAsDataURL(file);
    }
  });
}
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.upload-box').forEach(setupUpload);
  document.querySelectorAll('.otp-inputs').forEach(setupOtpInputs);
});

// ── Range slider fill ────────────────────────────────────────
function updateSliderFill(slider) {
  var min = parseFloat(slider.min) || 0;
  var max = parseFloat(slider.max) || 100;
  var val = parseFloat(slider.value);
  var pct = ((val - min) / (max - min)) * 100;
  slider.style.setProperty('--pct', pct + '%');
}

// ── Admin sidebar toggle ─────────────────────────────────────
function toggleAdminSidebar() {
  var sb = document.getElementById('adminSidebar');
  if (sb) sb.classList.toggle('open');
}

// ── Dynamic dependents ───────────────────────────────────────
function renderDependents(count, container) {
  container.innerHTML = '';
  for (var i = 1; i <= count; i++) {
    var html = '<div class="card mb-4" style="border-color:var(--primary-200)">' +
      '<h4 style="font-size:.9rem;font-weight:700;color:var(--primary-700);margin-bottom:var(--space-3)">Dependent ' + i + '</h4>' +
      '<div class="field"><label class="field-label">Name <span class="pct">+1% Profile Completion</span></label>' +
      '<input type="text" name="dep_name[]" class="field-input" required></div>' +
      '<div class="field"><label class="field-label">Birthday <span class="pct">+1% Profile Completion</span></label>' +
      '<input type="date" name="dep_birthday[]" class="field-input" required></div>' +
      '<div class="field"><label class="field-label">Phone Number <span class="pct">+1% Profile Completion</span></label>' +
      '<input type="tel" name="dep_phone[]" class="field-input" required></div>' +
      '<div class="field"><label class="field-label">Facebook Name <span class="pct">+1% Profile Completion</span></label>' +
      '<input type="text" name="dep_facebook[]" class="field-input" required></div>' +
      '</div>';
    container.insertAdjacentHTML('beforeend', html);
  }
}
