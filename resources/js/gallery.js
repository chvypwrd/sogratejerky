document.addEventListener('DOMContentLoaded', function () {
    const galleryData = window.__galleryData;
    if (!galleryData || !galleryData.images || galleryData.images.length === 0) {
        return;
    }

    const images = galleryData.images;
    let currentIndex = 0;
    let triggerElement = null;

    // Create modal
    const modal = document.createElement('div');
    modal.id = 'gallery-viewer';
    modal.className = 'gallery-viewer';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-label', 'Image viewer');
    modal.innerHTML = `
        <div class="gallery-viewer-backdrop"></div>
        <div class="gallery-viewer-content">
            <button class="gallery-viewer-close" type="button" aria-label="Close">&times;</button>
            <button class="gallery-viewer-prev" type="button" aria-label="Previous image">&lsaquo;</button>
            <button class="gallery-viewer-next" type="button" aria-label="Next image">&rsaquo;</button>
            <div class="gallery-viewer-image-wrap">
                <img class="gallery-viewer-image" src="" alt="">
            </div>
            <div class="gallery-viewer-caption"></div>
            <div class="gallery-viewer-counter"></div>
        </div>
    `;
    document.body.appendChild(modal);

    const img = modal.querySelector('.gallery-viewer-image');
    const caption = modal.querySelector('.gallery-viewer-caption');
    const counter = modal.querySelector('.gallery-viewer-counter');
    const closeBtn = modal.querySelector('.gallery-viewer-close');
    const prevBtn = modal.querySelector('.gallery-viewer-prev');
    const nextBtn = modal.querySelector('.gallery-viewer-next');
    const backdrop = modal.querySelector('.gallery-viewer-backdrop');

    function showImage(index) {
        currentIndex = index;
        const image = images[index];
        img.src = image.full;
        img.alt = image.alt;
        caption.textContent = image.caption || '';
        caption.style.display = image.caption ? '' : 'none';
        counter.textContent = (index + 1) + ' / ' + images.length;
        prevBtn.style.display = index > 0 ? '' : 'none';
        nextBtn.style.display = index < images.length - 1 ? '' : 'none';
    }

    function openViewer(index, trigger) {
        triggerElement = trigger || null;
        currentIndex = index;
        showImage(index);
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        closeBtn.focus();
    }

    function closeViewer() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        if (triggerElement) {
            triggerElement.focus();
            triggerElement = null;
        }
    }

    function navigate(direction) {
        const newIndex = currentIndex + direction;
        if (newIndex >= 0 && newIndex < images.length) {
            showImage(newIndex);
        }
    }

    // Event listeners
    closeBtn.addEventListener('click', closeViewer);
    backdrop.addEventListener('click', closeViewer);
    prevBtn.addEventListener('click', function () { navigate(-1); });
    nextBtn.addEventListener('click', function () { navigate(1); });

    document.addEventListener('keydown', function (e) {
        if (!modal.classList.contains('active')) return;
        if (e.key === 'Escape') closeViewer();
        if (e.key === 'ArrowLeft') navigate(-1);
        if (e.key === 'ArrowRight') navigate(1);
    });

    // Attach click handlers to gallery links
    document.querySelectorAll('.gallery-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            var index = parseInt(this.getAttribute('data-gallery-index'), 10);
            openViewer(index, this);
        });
    });
});
