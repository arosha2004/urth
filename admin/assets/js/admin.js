/**
 * URTH Admin Panel Client-Side Logic
 */

document.addEventListener('DOMContentLoaded', () => {

  // --- Password Visibility Toggle ---
  const togglePassBtn = document.getElementById('togglePassword');
  if (togglePassBtn) {
    togglePassBtn.addEventListener('click', () => {
      const passInput = document.getElementById('password');
      if (passInput) {
        const type = passInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passInput.setAttribute('type', type);
        
        // Update SVG Icon
        togglePassBtn.innerHTML = type === 'password' 
          ? `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`
          : `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`;
      }
    });
  }

  // --- Live Table Search & Filter ---
  const searchInput = document.getElementById('tableSearch');
  const categoryFilter = document.getElementById('categoryFilter');
  const projectRows = document.querySelectorAll('.project-row');
  const emptyState = document.getElementById('noResultsRow');

  function filterProjects() {
    const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const selectedCategory = categoryFilter ? categoryFilter.value.toLowerCase().trim() : '';
    let visibleCount = 0;

    projectRows.forEach(row => {
      const title = row.getAttribute('data-title') || '';
      const category = row.getAttribute('data-category') || '';
      const id = row.getAttribute('data-id') || '';

      const matchesSearch = !query || title.includes(query) || category.includes(query) || id.includes(query);
      const matchesCategory = !selectedCategory || category === selectedCategory;

      if (matchesSearch && matchesCategory) {
        row.style.display = '';
        visibleCount++;
      } else {
        row.style.display = 'none';
      }
    });

    if (emptyState) {
      emptyState.style.display = visibleCount === 0 ? '' : 'none';
    }
  }

  if (searchInput) searchInput.addEventListener('input', filterProjects);
  if (categoryFilter) categoryFilter.addEventListener('change', filterProjects);

  // --- Modals Management ---
  const addModal = document.getElementById('addProjectModal');
  const editModal = document.getElementById('editProjectModal');
  const deleteModal = document.getElementById('deleteProjectModal');

  // Helper to open modal
  window.openModal = function(modal) {
    if (modal) modal.classList.add('active');
  };

  // Helper to close modal
  window.closeModal = function(modal) {
    if (modal) modal.classList.remove('active');
  };

  // Event listeners for close buttons
  document.querySelectorAll('.modal-close, [data-modal-close]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const modal = e.target.closest('.modal-overlay');
      closeModal(modal);
    });
  });

  // Open Add Modal
  const openAddBtn = document.getElementById('openAddModal');
  if (openAddBtn) {
    openAddBtn.addEventListener('click', () => {
      openModal(addModal);
    });
  }



  // Open Edit Modal
  document.querySelectorAll('.edit-project-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-id');
      const title = btn.getAttribute('data-title');
      const category = btn.getAttribute('data-category');
      const description = btn.getAttribute('data-description');
      const image = btn.getAttribute('data-image');
      const galleryData = btn.getAttribute('data-gallery');
      let gallery = [];
      try {
        gallery = JSON.parse(galleryData || '[]');
      } catch (e) {}

      document.getElementById('edit_id').value = id;
      document.getElementById('edit_title').value = title;
      document.getElementById('edit_category').value = category;
      const descField = document.getElementById('edit_description');
      if (descField) descField.value = description || '';
      
      const editPreview = document.getElementById('edit_hero_preview');
      const editContainer = document.getElementById('edit_hero_preview_container');
      if (editPreview && editContainer) {
        editPreview.src = image || '';
        editContainer.style.display = image ? 'block' : 'none';
      }

      document.getElementById('deleted_gallery_images').value = '';
      const wrapper = document.getElementById('edit_gallery_inputs_wrapper');
      if (wrapper) {
        wrapper.innerHTML = ''; 
        if (gallery.length === 0) {
          wrapper.innerHTML = `
            <div class="gallery-input-row" style="display: flex; gap: 10px; align-items: flex-start; margin-bottom: 10px;">
                <div style="flex-grow: 1;">
                    <input type="file" name="gallery_images[]" class="form-control gallery-file-input" accept="image/*">
                </div>
                <div class="preview-slot" style="width: 42px; height: 42px; border-radius: 4px; border: 1px solid #ddd; background: #f9f9f9; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                    <span style="color: #bbb; font-size: 10px;">No img</span>
                </div>
                <button type="button" class="btn btn-danger btn-sm remove-gallery-row" style="padding: 0 10px; height: 42px; display: none;" title="Remove">&times;</button>
            </div>
          `;
        } else {
          gallery.forEach((img) => {
            wrapper.innerHTML += `
              <div class="gallery-input-row" data-existing-id="${img.id}" style="display: flex; gap: 10px; align-items: flex-start; margin-bottom: 10px;">
                  <div style="flex-grow: 1;">
                      <div class="existing-image-info" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9; font-size: 13px; color: #555;">
                        Existing Image
                      </div>
                  </div>
                  <div class="preview-slot" style="width: 42px; height: 42px; border-radius: 4px; border: 1px solid #ddd; background: #f9f9f9; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                      <img src="${img.path}" style="width: 100%; height: 100%; object-fit: cover;">
                  </div>
                  <button type="button" class="btn btn-danger btn-sm remove-gallery-row" style="padding: 0 10px; height: 42px; display: block;" title="Remove">&times;</button>
              </div>
            `;
          });
          if (gallery.length < 12) {
             wrapper.innerHTML += `
              <div class="gallery-input-row" style="display: flex; gap: 10px; align-items: flex-start; margin-bottom: 10px;">
                  <div style="flex-grow: 1;">
                      <input type="file" name="gallery_images[]" class="form-control gallery-file-input" accept="image/*">
                  </div>
                  <div class="preview-slot" style="width: 42px; height: 42px; border-radius: 4px; border: 1px solid #ddd; background: #f9f9f9; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                      <span style="color: #bbb; font-size: 10px;">No img</span>
                  </div>
                  <button type="button" class="btn btn-danger btn-sm remove-gallery-row" style="padding: 0 10px; height: 42px; display: block;" title="Remove">&times;</button>
              </div>
            `;
          }
        }
      }

      const addGalleryBtn = document.getElementById('edit_add_gallery_btn');
      if (addGalleryBtn) {
        if (gallery.length >= 12) {
          addGalleryBtn.style.display = 'none';
        } else {
          addGalleryBtn.style.display = 'inline-block';
        }
      }

      openModal(editModal);
    });
  });

  // Open Delete Modal
  document.querySelectorAll('.delete-project-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-id');
      const title = btn.getAttribute('data-title');

      document.getElementById('delete_project_id').value = id;
      document.getElementById('delete_project_name').textContent = title;

      openModal(deleteModal);
    });
  });

  function setupFilePreview(inputId, previewImgId, containerId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewImgId);
    const container = document.getElementById(containerId);
    
    if (input && preview && container) {
      input.addEventListener('change', function() {
        if (this.files && this.files[0]) {
          const reader = new FileReader();
          reader.onload = function(e) {
            preview.src = e.target.result;
            container.style.display = 'block';
          }
          reader.readAsDataURL(this.files[0]);
        } else {
          // If no new file selected for Edit, don't clear the preview (it might show existing image)
          if (inputId === 'add_image_file') {
            preview.src = '';
            container.style.display = 'none';
          }
        }
      });
    }
  }

  setupFilePreview('add_image_file', 'add_hero_preview', 'add_hero_preview_container');
  setupFilePreview('edit_image_file', 'edit_hero_preview', 'edit_hero_preview_container');

  function setupGalleryInputs(wrapperId, addBtnId) {
    const galleryInputsWrapper = document.getElementById(wrapperId);
    const addGalleryBtn = document.getElementById(addBtnId);

    if (galleryInputsWrapper && addGalleryBtn) {

      function getRowCount() {
        return galleryInputsWrapper.querySelectorAll('.gallery-input-row').length;
      }

      function updateButtons() {
        const rows = galleryInputsWrapper.querySelectorAll('.gallery-input-row');
        const count = rows.length;
        rows.forEach(row => {
          const btn = row.querySelector('.remove-gallery-row');
          if (count > 1) {
            btn.style.display = 'block';
          } else {
            btn.style.display = 'none';
          }
        });
        
        if (count >= 12) {
          addGalleryBtn.style.display = 'none';
        } else {
          addGalleryBtn.style.display = 'inline-block';
        }
      }

      addGalleryBtn.addEventListener('click', function() {
        if (getRowCount() >= 12) {
          alert('You can only add up to 12 gallery images.');
          return;
        }
        
        const newRow = document.createElement('div');
        newRow.className = 'gallery-input-row';
        newRow.style.cssText = 'display: flex; gap: 10px; align-items: flex-start; margin-bottom: 10px;';
        newRow.innerHTML = `
            <div style="flex-grow: 1;">
                <input type="file" name="gallery_images[]" class="form-control gallery-file-input" accept="image/*">
            </div>
            <div class="preview-slot" style="width: 42px; height: 42px; border-radius: 4px; border: 1px solid #ddd; background: #f9f9f9; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                <span style="color: #bbb; font-size: 10px;">No img</span>
            </div>
            <button type="button" class="btn btn-danger btn-sm remove-gallery-row" style="padding: 0 10px; height: 42px; display: block;" title="Remove">&times;</button>
        `;
        galleryInputsWrapper.appendChild(newRow);
        updateButtons();
      });

      galleryInputsWrapper.addEventListener('click', function(e) {
        if (e.target.closest('.remove-gallery-row') || e.target.classList.contains('remove-gallery-row')) {
          const row = e.target.closest('.gallery-input-row');
          const existingId = row.dataset.existingId;
          if (existingId) {
             const deletedInput = document.getElementById('deleted_gallery_images');
             if (deletedInput) {
                 let deleted = deletedInput.value ? deletedInput.value.split(',') : [];
                 deleted.push(existingId);
                 deletedInput.value = deleted.join(',');
             }
          }
          if (getRowCount() > 1) {
            row.remove();
            updateButtons();
          }
        }
      });

      galleryInputsWrapper.addEventListener('change', function(e) {
        if (e.target.classList.contains('gallery-file-input')) {
          const file = e.target.files[0];
          const previewSlot = e.target.closest('.gallery-input-row').querySelector('.preview-slot');
          if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(event) {
              previewSlot.innerHTML = `<img src="${event.target.result}" style="width: 100%; height: 100%; object-fit: cover;">`;
            }
            reader.readAsDataURL(file);
          } else {
            previewSlot.innerHTML = '<span style="color: #bbb; font-size: 10px;">No img</span>';
          }
        }
      });

      // Initial state
      updateButtons();
    }
  }

  setupGalleryInputs('gallery_inputs_wrapper', 'add_gallery_btn');
  setupGalleryInputs('edit_gallery_inputs_wrapper', 'edit_add_gallery_btn');

});
