/**
 * Pet Lovers Community – Client-side JavaScript
 */

/* ─────────────────────────────────────────────────────────────────
   Image preview before upload
   ───────────────────────────────────────────────────────────────── */

/**
 * Preview a selected image file in an <img> element.
 *
 * @param {HTMLInputElement} input       The file input element.
 * @param {string}           previewId  ID of the <img> to update.
 */
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;

    if (input.files && input.files[0]) {
        const file   = input.files[0];
        const allowed = ['image/jpeg', 'image/png', 'image/gif'];

        if (!allowed.includes(file.type)) {
            showToast('Please select a JPG, PNG, or GIF image.', 'danger');
            input.value = '';
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            showToast('Image must be under 5 MB.', 'danger');
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}

/* ─────────────────────────────────────────────────────────────────
   Dynamic pet forms
   ───────────────────────────────────────────────────────────────── */

/** Counter used to give each pet entry a stable sequential label. */
// petCount is managed via getPetCount() which reads the live DOM

/**
 * Return the current number of pet entry blocks in the DOM.
 */
function getPetCount() {
    const container = document.getElementById('petsContainer');
    if (!container) return 0;
    return container.querySelectorAll('.pet-entry').length;
}

/**
 * Add a new blank pet form block to #petsContainer.
 */
function addPetForm() {
    const container = document.getElementById('petsContainer');
    if (!container) return;

    const index = getPetCount() + 1;

    const html = `
    <div class="pet-entry card border-0 bg-light p-3 mb-3">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h6 class="fw-semibold mb-0">Pet #${index}</h6>
            <button type="button" class="btn btn-outline-danger btn-sm"
                    onclick="removePet(this)">
                <i class="bi bi-trash"></i>
            </button>
        </div>
        <div class="row g-2">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Name <span class="text-danger">*</span></label>
                <input type="text" name="pet_name[]" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Breed</label>
                <input type="text" name="pet_breed[]" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Age (yrs)</label>
                <input type="number" name="pet_age[]" class="form-control form-control-sm" min="0" max="100">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Photo</label>
                <input type="file" name="pet_photo[]" class="form-control form-control-sm"
                       accept="image/jpeg,image/png,image/gif">
            </div>
        </div>
    </div>`;

    container.insertAdjacentHTML('beforeend', html);
    renumberPets();
}

/**
 * Remove a pet entry block when the trash button is clicked.
 *
 * @param {HTMLButtonElement} btn  The remove button inside the entry.
 */
function removePet(btn) {
    const entry = btn.closest('.pet-entry');
    if (!entry) return;

    // Always keep at least one pet entry
    const container = document.getElementById('petsContainer');
    if (container && container.querySelectorAll('.pet-entry').length <= 1) {
        showToast('You need at least one pet entry.', 'warning');
        return;
    }

    entry.remove();
    renumberPets();
}

/**
 * Re-label all pet headings after add/remove.
 */
function renumberPets() {
    const container = document.getElementById('petsContainer');
    if (!container) return;
    container.querySelectorAll('.pet-entry').forEach(function (entry, i) {
        const heading = entry.querySelector('h6');
        if (heading) heading.textContent = 'Pet #' + (i + 1);
    });
}

/* ─────────────────────────────────────────────────────────────────
   Inline toast helper (Bootstrap 5)
   ───────────────────────────────────────────────────────────────── */

/**
 * Show a temporary Bootstrap toast-style alert at the top of the page.
 *
 * @param {string} message
 * @param {string} type     Bootstrap color: danger | warning | success | info
 */
function showToast(message, type) {
    type = type || 'info';

    // Remove any existing toast
    const existing = document.getElementById('js-toast');
    if (existing) existing.remove();

    const div = document.createElement('div');
    div.id        = 'js-toast';
    div.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow`;
    div.style.zIndex = '9999';
    div.style.minWidth = '280px';
    div.innerHTML = message +
        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';

    document.body.appendChild(div);

    // Auto-dismiss after 4 s
    setTimeout(function () {
        div.classList.remove('show');
        setTimeout(function () { div.remove(); }, 300);
    }, 4000);
}

/* ─────────────────────────────────────────────────────────────────
   Password match helper
   ───────────────────────────────────────────────────────────────── */

(function () {
    document.addEventListener('DOMContentLoaded', function () {

        // Live password-match feedback
        const pw1 = document.getElementById('password') ||
                    document.getElementById('new_password');
        const pw2 = document.getElementById('confirm_password');

        if (pw1 && pw2) {
            function checkMatch() {
                if (pw2.value === '') {
                    pw2.classList.remove('is-valid', 'is-invalid');
                    return;
                }
                if (pw1.value === pw2.value) {
                    pw2.classList.remove('is-invalid');
                    pw2.classList.add('is-valid');
                } else {
                    pw2.classList.remove('is-valid');
                    pw2.classList.add('is-invalid');
                }
            }
            pw1.addEventListener('input', checkMatch);
            pw2.addEventListener('input', checkMatch);
        }

        // Bootstrap client-side form validation for all .needs-validation forms
        document.querySelectorAll('form.needs-validation').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    });
})();
