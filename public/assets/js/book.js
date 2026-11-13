const $ = (id) => document.getElementById(id);
const API = window.API_BASE ?? 'api/';

new QRCode($('qrBox'), { text: window.QR_TOKEN, width: 140, height: 140 });

const requestBtn = $('requestBtn');
if (requestBtn) {
  const modal = new bootstrap.Modal($('requestModal'));
  const msg = $('requestMsg');

  requestBtn.addEventListener('click', () => {
    msg.classList.add('d-none');
    modal.show();
  });

  $('sendRequest').addEventListener('click', async () => {
    const sendBtn = $('sendRequest');
    sendBtn.disabled = true;
    try {
      const res = await fetch(`${API}requests/create.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ book_id: window.BOOK_ID }),
      });
      const result = await res.json();
      if (res.ok) {
        location.href = 'requests.php?requested=1';
        return;
      }
      msg.textContent = result.error ?? 'Could not send the request.';
      msg.className = 'alert alert-danger mb-0';
    } catch (err) {
      console.error(err);
      msg.textContent = 'Unable to connect to the server.';
      msg.className = 'alert alert-danger mb-0';
    } finally {
      sendBtn.disabled = false;
    }
  });
}