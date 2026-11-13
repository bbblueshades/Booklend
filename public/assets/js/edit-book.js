const $ = (id) => document.getElementById(id);
const API = window.API_BASE ?? 'api/';

new QRCode($('qrBox'), { text: window.QR_TOKEN, width: 160, height: 160 });

const showMsg = (text, type) => {
  const box = $('editMsg');
  box.textContent = text;
  box.className = `mb-3 alert alert-${type}`;
};

$('editForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const payload = {
    book_id: Number($('bookId').value),
    title: $('title').value.trim(),
    author: $('author').value.trim(),
    isbn: $('isbn').value.trim(),
    subject_id: Number($('subject').value),
    book_condition: $('condition').value,
  };

  if (!payload.title || !payload.author) {
    return showMsg('Please complete all required fields.', 'warning');
  }

  try {
    const res = await fetch(`${API}books/update.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const result = await res.json();
    if (!res.ok) return showMsg(result.error ?? 'Could not save.', 'danger');
    location.href = 'my-books.php?updated=1';
  } catch (err) {
    console.error(err);
    showMsg('Unable to connect to the server.', 'danger');
  }
});