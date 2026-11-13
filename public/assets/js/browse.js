const $ = (id) => document.getElementById(id);
const API = window.API_BASE ?? 'api/';
const grid = $('bookGrid');
const msg = $('resultsMsg');

// Small helper: build elements with textContent, so book data can never inject HTML
const el = (tag, cls = '', text) => {
  const node = document.createElement(tag);
  if (cls) node.className = cls;
  if (text !== undefined) node.textContent = text;
  return node;
};

const debounce = (fn, ms = 300) => {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), ms);
  };
};

function buildCard({ book_id, title, author, subject_name, availability, cover_url, is_own }) {
  const col = el('div', 'col');
  const card = el('article', 'card h-100 book-card');
  const detailUrl = `${window.BOOK_URL}?book_id=${book_id}`;

  // Cover
  if (cover_url) {
    const img = el('img', 'browse-cover');
    img.src = cover_url;
    img.alt = `Cover of ${title}`;
    img.loading = 'lazy';
    card.append(img);
  } else {
    card.append(el('div', 'browse-cover browse-cover--empty', 'No cover'));
  }

  // Body
  const body = el('div', 'card-body d-flex flex-column');
  const heading = el('h2', 'h5 card-title');
  const link = el('a', 'text-reset text-decoration-none', title);
  link.href = detailUrl;
  heading.append(link);

  const meta = el('p', 'small text-muted mb-2', `${author}, ${subject_name}`);
  const isAvailable = availability === 'available';
  const badge = el('span', `badge align-self-start mb-3 ${isAvailable ? 'text-bg-success' : 'text-bg-secondary'}`,
    isAvailable ? 'Available' : 'Borrowed');

  // Action: own book -> no request button; borrowed -> "Unavailable"; else request
  let action;
  if (is_own) {
    action = el('span', 'badge text-bg-light border align-self-start mt-auto', 'Your book');
  } else if (!isAvailable) {
    action = el('button', 'btn btn-secondary mt-auto', 'Unavailable');
    action.type = 'button';
    action.disabled = true;
  } else {
    action = el('a', 'btn btn-primary mt-auto', 'Request to borrow');
    action.href = detailUrl;
  }

  body.append(heading, meta, badge, action);
  card.append(body);
  col.append(card);
  return col;
}

let controller = null;   // lets us cancel an older request when a newer one starts

async function search() {
  controller?.abort();
  controller = new AbortController();

  const params = new URLSearchParams({
    q: $('q').value.trim(),
    subject: $('subject').value,
    availability: $('availability').value,
  });

  msg.textContent = 'Searching…';
  try {
    const res = await fetch(`${API}books/search.php?${params}`, { signal: controller.signal });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error ?? `HTTP ${res.status}`);

    const { books } = data;
    grid.replaceChildren(...books.map(buildCard));
    msg.textContent = books.length
      ? `${books.length} book${books.length === 1 ? '' : 's'} found.`
      : 'No records found.';
  } catch (err) {
    if (err.name === 'AbortError') return;   // superseded by a newer search, ignore
    console.error(err);
    grid.replaceChildren();
    msg.textContent = 'Unable to connect to the server.';
  }
}

const debouncedSearch = debounce(search, 300);
$('q').addEventListener('input', debouncedSearch);
$('subject').addEventListener('change', search);        // dropdowns need no delay
$('availability').addEventListener('change', search);

search();   // initial load