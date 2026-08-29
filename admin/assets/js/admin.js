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

  const addGalleryInput = document.getElementById('add_gallery');
  const galleryContainer = document.getElementById('add_gallery_preview_container');
  
  if (addGalleryInput && galleryContainer) {
    addGalleryInput.addEventListener('change', function() {
      galleryContainer.innerHTML = ''; // clear previous
      
      if (this.files.length > 12) {
        alert('You can only select up to 12 gallery images.');
        this.value = ''; // Reset selection
        return;
      }
      
      Array.from(this.files).forEach(file => {
        if (file.type.startsWith('image/')) {
          const reader = new FileReader();
          reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.width = '80px';
            img.style.height = '80px';
            img.style.objectFit = 'cover';
            img.style.borderRadius = '4px';
            img.style.border = '1px solid #ddd';
            galleryContainer.appendChild(img);
          }
          reader.readAsDataURL(file);
        }
      });
    });
  }

});
