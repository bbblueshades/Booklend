const $ = (id) => document.getElementById(id);

const isIsbn = (s) => /^(\d{9}[\dX]|\d{13})$/i.test(s);

const showMsg = (text, type) => {
  const box = $('lookupMsg');
  box.textContent = text;                       // textContent = safe from XSS
  box.className = `mt-2 alert alert-${type}`;
};

async function lookupByIsbn(isbn) {
  const url = `https://openlibrary.org/api/books?bibkeys=ISBN:${isbn}&format=json&jscmd=data`;
  const res = await fetch(url);
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  const data = await res.json();
  const book = data[`ISBN:${isbn}`];
  if (!book) return null;                       // Open Library returns {} when not found
  return {
    title: book.title,
    author: book.authors?.map((a) => a.name).join(', ') ?? '',
    cover: book.cover?.medium ?? `https://covers.openlibrary.org/b/isbn/${isbn}-M.jpg`,
    isbn,
  };
}

async function lookupByTitle(title) {
  const url = `https://openlibrary.org/search.json?title=${encodeURIComponent(title)}&limit=1`;
  const res = await fetch(url);
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  const { docs } = await res.json();
  if (!docs.length) return null;
  const [d] = docs;                             // destructuring (ES6 requirement)
  return {
    title: d.title,
    author: d.author_name?.join(', ') ?? '',
    cover: d.cover_i ? `https://covers.openlibrary.org/b/id/${d.cover_i}-M.jpg` : '',
    isbn: d.isbn?.[0] ?? '',
  };
}

$('lookupBtn').addEventListener('click', async () => {
  const q = $('query').value.trim().replace(/-/g, '');
  if (!q) return showMsg('Please enter an ISBN or title.', 'warning');

  showMsg('Searching…', 'info');
  try {
    const book = isIsbn(q) ? await lookupByIsbn(q) : await lookupByTitle(q);
    if (!book) return showMsg('Book not found. Please enter the details manually.', 'warning');

    $('title').value = book.title;
    $('author').value = book.author;
    $('isbn').value = book.isbn;
    $('coverUrl').value = book.cover;
    if (book.cover) { $('coverPreview').src = book.cover; $('coverPreview').hidden = false; }
    showMsg('Book found, details auto-filled.', 'success');
  } catch (err) {
    console.error(err);
    showMsg('Unable to connect. Please enter the details manually.', 'danger');
  }
});

function renderQr(token) {
  const box = $('qrBox');
  box.innerHTML = '';                           // clear any previous QR
  new QRCode(box, { text: token, width: 160, height: 160 });
}

async function loadSubjects() {
  const res = await fetch('api/subjects/list.php');
  const subjects = await res.json();
  $('subject').innerHTML = '<option value="">Select</option>' +
    subjects.map(({ subject_id, subject_name }) =>
      `<option value="${subject_id}">${subject_name}</option>`).join('');
}
loadSubjects();

$('bookForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const payload = {
    title: $('title').value.trim(),
    author: $('author').value.trim(),
    isbn: $('isbn').value.trim(),
    cover_url: $('coverUrl').value,
    subject_id: Number($('subject').value),
    book_condition: $('condition').value,
  };

  // quick client-side check (the real validation is on the server)
  if (!payload.title || !payload.author || !payload.subject_id) {
    return showMsg('Please complete all required fields.', 'warning');
  }

  try {
    const res = await fetch('api/books/create.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const result = await res.json();
    if (!res.ok) return showMsg(result.error ?? 'Could not save.', 'danger');

    showMsg('Record successfully saved.', 'success');
    renderQr(result.book.qr_code);
  } catch {
    showMsg('Unable to connect to the server.', 'danger');
  }
});