document.addEventListener('DOMContentLoaded', function () {
    let courseIdToDelete = null;
    let courseCsrfToken = null;

    const deleteCourseModal = document.getElementById('delete-course-modal');
    const deleteCourseCancelBtn = document.getElementById('delete-course-cancel-btn');
    const deleteCourseConfirmBtn = document.getElementById('delete-course-confirm-btn');

    if (!deleteCourseModal) {
        return;
    }

    const baseUrl = deleteCourseModal.getAttribute('data-base-delete-url') || '';

    // Open delete modal when clicking delete buttons
    document.addEventListener('click', function (e) {
        const deleteBtn = e.target.closest('.delete-course-btn');
        if (!deleteBtn) {
            return;
        }

        courseIdToDelete = deleteBtn.getAttribute('data-course-id');
        courseCsrfToken = deleteBtn.getAttribute('data-csrf-token');

        deleteCourseModal.style.display = 'flex';
        deleteCourseModal.classList.remove('hidden');
        document.documentElement.style.overflow = 'hidden';
    });

    // Close helpers
    function closeDeleteModal() {
        deleteCourseModal.style.display = 'none';
        deleteCourseModal.classList.add('hidden');
        document.documentElement.style.overflow = '';
        courseIdToDelete = null;
        courseCsrfToken = null;
    }

    // Cancel button
    deleteCourseCancelBtn?.addEventListener('click', function () {
        closeDeleteModal();
    });

    // Confirm delete
    deleteCourseConfirmBtn?.addEventListener('click', function () {
        if (!courseIdToDelete || !baseUrl) {
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = baseUrl.replace('/0', '/' + courseIdToDelete);

        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = courseCsrfToken || '';

        form.appendChild(tokenInput);
        document.body.appendChild(form);
        form.submit();
    });

    // Close when clicking outside the modal content
    deleteCourseModal.addEventListener('click', function (e) {
        if (e.target === deleteCourseModal) {
            closeDeleteModal();
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && deleteCourseModal.style.display !== 'none') {
            closeDeleteModal();
        }
    });
});

