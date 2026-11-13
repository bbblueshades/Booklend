const ENDPOINT = window.NOTIF_API;
const INTERVAL = 20000;                       // 20 s, within the wireframe's 15 to 30 s

const badges = ['notifBadge', 'menuBadge']
  .map((id) => document.getElementById(id))
  .filter(Boolean);

const render = (count) => {
  badges.forEach((b) => {
    b.textContent = count;
    b.classList.toggle('d-none', count === 0);
  });
};

// Anyone (including notifications.js) can announce a new count and the bell follows
window.addEventListener('notif:count', (e) => render(e.detail));

let timer = null;
let expired = false;

async function poll() {
  try {
    const res = await fetch(ENDPOINT, { cache: 'no-store' });
    if (res.status === 401) {                 // session ended: stop asking
      expired = true;
      stop();
      return;
    }
    if (!res.ok) return;
    const { count } = await res.json();
    window.dispatchEvent(new CustomEvent('notif:count', { detail: count }));
  } catch {
    /* offline or server down: just try again on the next tick */
  }
}

const start = () => { if (!timer && !expired) timer = setInterval(poll, INTERVAL); };
const stop  = () => { clearInterval(timer); timer = null; };

// Don't poll hidden tabs; catch up immediately when the tab is shown again
document.addEventListener('visibilitychange', () => {
  if (document.hidden) {
    stop();
  } else if (!expired) {
    poll();
    start();
  }
});

poll();      // update right away on page load (also creates any due-soon notices)
start();