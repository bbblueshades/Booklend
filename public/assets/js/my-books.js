const $ = (id) => document.getElementById(id);
const API = window.API_BASE ?? 'api/';

const qrModal = new bootstrap.Modal($('qrModal'));
const deleteModal = new bootstrap.Modal($('deleteModal'));
let pendingId = null;

document.addEventListener('click', (e) => {
  const qrBtn = e.target.closest('.js-qr');
  if (qrBtn) {
$('qrTitle').textContent = `${qrBtn.dataset.title} (code: ${qrBtn.dataset.token})`;    $('qrBox').innerHTML = '';
    new QRCode($('qrBox'), { text: qrBtn.dataset.token, width: 200, height: 200 });
    qrModal.show();
    return;
  }

  const delBtn = e.target.closest('.js-delete');
  if (delBtn) {
    pendingId = Number(delBtn.dataset.bookId);
    $('deleteText').textContent = `“${delBtn.dataset.title}” will be removed permanently.`;
    $('deleteMsg').classList.add('d-none');
    $('confirmDelete').disabled = false;
    deleteModal.show();
  }
});

$('confirmDelete').addEventListener('click', async () => {
  const msg = $('deleteMsg');
  $('confirmDelete').disabled = true;
  try {
    const res = await fetch(`${API}books/delete.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ book_id: pendingId }),
    });
    const result = await res.json();

    if (res.ok) {
      location.href = 'my-books.php?deleted=1';
      return;
    }
    // 409 = active request, 404 = not yours / not found, etc.
    msg.textContent = result.error ?? 'Could not delete.';
    msg.classList.remove('d-none');
  } catch (err) {
    console.error(err);
    msg.textContent = 'Unable to connect to the server.';
    msg.classList.remove('d-none');
  } finally {
    $('confirmDelete').disabled = false;
  }
});