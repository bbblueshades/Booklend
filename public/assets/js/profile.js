const $ = (id) => document.getElementById(id);
const API = window.API_BASE ?? 'api/';

const showMsg = (text, type) => {
  const box = $('profileMsg');
  box.textContent = text;
  box.className = `mb-3 alert alert-${type}`;
};

// ---------- Password strength ----------
const LEVELS = [
  { label: 'Too short',   cls: 'bg-danger',  pct: 20  },
  { label: 'Weak',        cls: 'bg-danger',  pct: 35  },
  { label: 'Fair',        cls: 'bg-warning', pct: 55  },
  { label: 'Good',        cls: 'bg-info',    pct: 75  },
  { label: 'Strong',      cls: 'bg-success', pct: 100 },
];

function scorePassword(pw) {
  if (pw.length < 8) return 0;                       // below the minimum, always "Too short"
  let score = 1;                                     // meets the minimum length
  if (pw.length >= 12) score++;                      // longer is better
  const kinds = [/[a-z]/, /[A-Z]/, /\d/, /[^A-Za-z0-9]/].filter((re) => re.test(pw)).length;
  if (kinds >= 3) score++;                           // mixes letters, numbers, symbols
  if (kinds === 4 && pw.length >= 12) score++;       // everything, and long
  return Math.min(score, 4);
}

function updateStrength() {
  const pw = $('newPassword').value;
  const wrap = $('strengthWrap');
  wrap.classList.toggle('d-none', pw === '');
  if (pw === '') return;

  const { label, cls, pct } = LEVELS[scorePassword(pw)];
  const bar = $('strengthBar');
  bar.style.width = `${pct}%`;
  bar.className = `progress-bar ${cls}`;
  bar.setAttribute('aria-valuenow', String(pct));
  $('strengthLabel').textContent = `Password strength: ${label}`;
}

// "input" fires on every keystroke (what keyup gives you) and also on paste and autofill
$('newPassword').addEventListener('input', updateStrength);
$('newPassword').addEventListener('keyup', updateStrength);

// ---------- Submit ----------
$('profileForm').addEventListener('submit', async (e) => {
  e.preventDefault();

  const payload = {
    full_name: $('fullName').value.trim(),
    email: $('email').value.trim(),
    new_password: $('newPassword').value,
    confirm_password: $('confirmPassword').value,
  };

  // Quick checks for fast feedback (the server repeats all of them)
  if (!payload.full_name || !payload.email) {
    return showMsg('Please complete all required fields.', 'warning');
  }
  if (payload.new_password !== payload.confirm_password) {
    return showMsg('Passwords do not match.', 'warning');
  }

  const btn = $('saveProfile');
  btn.disabled = true;
  try {
    const res = await fetch(`${API}profile/update.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const data = await res.json();
    if (!res.ok) {
      showMsg(data.error ?? 'Could not save.', 'danger');
      return;
    }
    showMsg(data.message ?? 'Record successfully saved.', 'success');
    $('fullName').value = data.full_name;
    $('email').value = data.email;
    $('newPassword').value = '';
    $('confirmPassword').value = '';
    updateStrength();
  } catch (err) {
    console.error(err);
    showMsg('Unable to connect to the server.', 'danger');
  } finally {
    btn.disabled = false;
  }
});