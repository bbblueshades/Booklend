const $ = (id) => document.getElementById(id);
const API = window.API_BASE ?? 'api/';
const root = $('notifRoot');

let shown = Number(root.dataset.unread);      // unread items currently on screen
const maxId = Number(root.dataset.maxId);     // newest notification currently on screen

const showMsg = (text, type) => {
  const box = $('notifMsg');
  box.textContent = text;
  box.className = `alert alert-${type}`;
};

// The poller (notif-poll.js) announces every new count. If it's higher than what this
// page shows, something new arrived, so offer a refresh instead of moving the list around.
window.addEventListener('notif:count', (e) => {
  if (e.detail > shown) $('newBanner').classList.remove('d-none');
});

$('markAll').addEventListener('click', async () => {
  const btn = $('markAll');
  btn.disabled = true;
  try {
    const res = await fetch(`${API}notifications/mark-read.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ up_to: maxId }),
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error ?? `HTTP ${res.status}`);

    document.querySelectorAll('.notif--unread').forEach((card) => {
      card.classList.remove('notif--unread');
      card.querySelector('.js-unread-badge')?.remove();
    });
    shown = 0;
    $('newBanner').classList.add('d-none');
    showMsg('All notifications marked as read.', 'success');
    // Updates the bell; also re-shows the banner if something newer is still unread
    window.dispatchEvent(new CustomEvent('notif:count', { detail: data.count }));
  } catch (err) {
    console.error(err);
    showMsg('Unable to connect to the server.', 'danger');
    btn.disabled = false;
  }
});