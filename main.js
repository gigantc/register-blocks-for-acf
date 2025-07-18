document.addEventListener('DOMContentLoaded', () => {

  // Helper to handle media uploads (images, SVGs, etc.)
  function attachUploadHandler(selector, preview = false) {
    document.querySelectorAll(selector).forEach(button => {
      button.addEventListener('click', (e) => {
        e.preventDefault();
        const targetId = button.getAttribute('data-target');

        // Open WordPress media uploader frame
        const frame = wp.media({
          title: 'Select or Upload Media',
          button: { text: 'Use this file' },
          multiple: false
        });

        // Handle selected media
        frame.on('select', () => {
          const attachment = frame.state().get('selection').first().toJSON();
          const target = document.getElementById(targetId);
          if (target) target.value = attachment.url;

          // Optionally preview uploaded SVG
          if (preview && attachment.url.endsWith('.svg')) {
            const svgPreview = document.getElementById('svg-preview');
            if (svgPreview) {
              svgPreview.innerHTML = `<object type="image/svg+xml" data="${attachment.url}" width="40" height="40"></object>`;
            }
          }
        });

        frame.open();
      });
    });
  }

  // Activate media upload buttons with optional SVG preview
  attachUploadHandler('.upload-media-button', true);


  // Sync and sanitize block slug from post title or manual edits
  const titleInput = document.getElementById('df-title');
  const slugInput = document.getElementById('block_slug');
  let slugManuallyEdited = false;

  if (slugInput && titleInput) {
    // When slug is manually edited, let's keep it slug-like
    slugInput.addEventListener('input', () => {
      slugManuallyEdited = true;
      const raw = slugInput.value;
      const slug = raw.toLowerCase().trim()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-');
      slugInput.value = slug;
    });

    // Auto-fill slug from title unless manually overridden
    titleInput.addEventListener('input', () => {
      if (slugManuallyEdited) return;
      const raw = titleInput.value;
      const slug = raw.toLowerCase().trim()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-');
      slugInput.value = slug;
    });
  }
  

  //select the icon
  const choices = document.querySelectorAll('.dashicon-choice');
  const hiddenInput = document.getElementById('block_icon_dashicon');

  choices.forEach(choice => {
    choice.addEventListener('click', () => {
      const selected = choice.getAttribute('data-icon');
      hiddenInput.value = selected;

      // Remove previous selection
      choices.forEach(c => c.classList.remove('selected'));
      choice.classList.add('selected');
    });
  });
});
