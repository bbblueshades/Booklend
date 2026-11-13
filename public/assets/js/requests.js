const $ = (id) => document.getElementById(id);
const API = window.API_BASE ?? 'api/';

const showMsg = (box, text, type = 'danger') => {
  box.textContent = text;
  box.className = `alert alert-${type}`;
};
const clearMsg = (box) => { box.textContent = ''; box.className = ''; };

async function post(endpoint, body) {
  const res = await fetch(`${API}requests/${endpoint}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  return { ok: res.ok, data: await res.json() };
}

// ---------- Approve / Reject ----------
async function respond(btn) {
  btn.disabled = true;
  try {
    const { ok, data } = await post('respond.php', {
      request_id: Number(btn.dataset.id),
      decision: btn.dataset.decision,
    });
    if (ok) {
      location.href = `requests.php?done=${data.status.toLowerCase()}`;
      return;
    }
    showMsg($('pageMsg'), data.error ?? 'Could not update the request.');
  } catch (err) {
    console.error(err);
    showMsg($('pageMsg'), 'Unable to connect to the server.');
  }
  btn.disabled = false;
}

// ---------- QR scanner ----------
const scanModalEl = $('scanModal');
const scanModal = new bootstrap.Modal(scanModalEl);
let scanner = null;
let requestId = null;
let pendingToken = null;   // token that passed the server preview, waiting for Confirm
let busy = false;

const showView = (view) => {
  $('scanView').classList.toggle('d-none', view !== 'scan');
  $('confirmPanel').classList.toggle('d-none', view !== 'confirm');
};

async function stopScanner() {
  if (!scanner) return;
  try { await scanner.stop(); scanner.clear(); } catch { /* it wasn't running */ }
  scanner = null;
}

async function startScanner() {
  showView('scan');
  scanner = new Html5Qrcode('reader');
  try {
    await scanner.start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: { width: 220, height: 220 } },
      (text) => checkToken(text.trim()),
      () => {}                                   // per-frame "no QR found", ignore
    );
  } catch (err) {
    console.error(err);
    scanner = null;
    showMsg($('scanMsg'), 'Camera unavailable. Type the code below instead.', 'warning');
  }
}

// Step 1: the server validates the token and returns a preview. Nothing changes yet.
async function checkToken(token) {
  if (busy || !token) return;
  busy = true;
  await stopScanner();
  showMsg($('scanMsg'), 'Checking…', 'info');
  try {
    const { ok, data } = await post('scan.php', { request_id: requestId, token, confirm: false });
    if (ok) {
      pendingToken = token;
      const handover = data.mode === 'handover';
      $('confirmText').textContent = handover
        ? 'Handover: status becomes Borrowed'
        : 'Return: status becomes Returned';
      $('confirmDetail').textContent = handover
        ? `Due date: ${data.due_label}`
        : 'The book becomes available again.';
      clearMsg($('scanMsg'));
      showView('confirm');
    } else {
      showMsg($('scanMsg'), data.error ?? 'Scan failed.');
      startScanner();                            // let them try again
    }
  } catch (err) {
    console.error(err);
    showMsg($('scanMsg'), 'Unable to connect to the server.');
    startScanner();
  }
  busy = false;
}

// Step 2: the owner pressed Confirm, so commit
$('confirmScan').addEventListener('click', async () => {
  if (busy || !pendingToken) return;
  busy = true;
  $('confirmScan').disabled = true;
  try {
    const { ok, data } = await post('scan.php', {
      request_id: requestId, token: pendingToken, confirm: true,
    });
    if (ok) {
      location.href = `requests.php?done=${data.status.toLowerCase()}`;
      return;
    }
    showMsg($('scanMsg'), data.error ?? 'Could not save.');
    pendingToken = null;
    startScanner();
  } catch (err) {
    console.error(err);
    showMsg($('scanMsg'), 'Unable to connect to the server.');
  }
  $('confirmScan').disabled = false;
  busy = false;
});

// Cancel on the confirmation panel: go back to scanning
$('cancelScan').addEventListener('click', () => {
  pendingToken = null;
  clearMsg($('scanMsg'));
  startScanner();
});

function openScanner(btn) {
  requestId = Number(btn.dataset.id);
  pendingToken = null;
  busy = false;
  $('scanTitle').textContent = btn.dataset.mode === 'return' ? 'Scan QR: return' : 'Scan QR: handover';
  $('scanBook').textContent = btn.dataset.title;
  $('manualToken').value = '';
  clearMsg($('scanMsg'));
  showView('scan');
  scanModal.show();
}

scanModalEl.addEventListener('shown.bs.modal', startScanner);   // start once the box has a size
scanModalEl.addEventListener('hidden.bs.modal', stopScanner);   // always release the camera

$('manualSubmit').addEventListener('click', () => checkToken($('manualToken').value.trim()));
$('manualToken').addEventListener('keydown', (e) => {
  if (e.key === 'Enter') checkToken($('manualToken').value.trim());
});

// ---------- One click handler for both layouts (table and cards) ----------
document.addEventListener('click', (e) => {
  const respondBtn = e.target.closest('.js-respond');
  if (respondBtn) return respond(respondBtn);

  const scanBtn = e.target.closest('.js-scan');
  if (scanBtn) openScanner(scanBtn);
});