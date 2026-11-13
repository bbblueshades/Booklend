<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pageTitle = 'List a Book';
require __DIR__ . '/../includes/header.php';
?>
<section>
    <h1>List a book</h1>

    <div class="row g-4">
        <div class="col-lg-7">
            <form id="bookForm" novalidate>
                <div class="mb-3">
                    <label for="query" class="form-label">ISBN or title</label>
                    <div class="input-group">
                        <input id="query" class="form-control" type="text">
                        <button id="lookupBtn" type="button" class="btn btn-secondary">Lookup</button>
                    </div>
                    <div id="lookupMsg" class="mt-2" role="status"></div>
                </div>

                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input id="title" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="author" class="form-label">Author</label>
                    <input id="author" class="form-control" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <select id="subject" class="form-select" required></select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="condition" class="form-label">Condition</label>
                        <select id="condition" class="form-select">
                            <option>As New</option>
                            <option>Fine</option>
                            <option selected>Good</option>
                            <option>Poor</option>
                        </select>
                    </div>
                </div>

                <input type="hidden" id="isbn">
                <input type="hidden" id="coverUrl">
                <button id="saveBtn" type="submit" class="btn btn-primary w-100">Save Listing</button>
            </form>
        </div>

        <div class="col-lg-5">
            <img id="coverPreview" class="img-fluid mb-3" alt="Book cover" hidden>
            <div id="qrBox"></div>
        </div>
    </div>
</section>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script type="module" src="<?= e(url('assets/js/books.js')) ?>"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>