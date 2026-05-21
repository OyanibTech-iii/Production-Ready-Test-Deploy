// --- Lottie Modal Helper Functions ---
function showResult(title, text, type, reload = true) {
    const titleEl = document.getElementById('result-title');
    const textEl = document.getElementById('result-text');
    const btn = document.getElementById('result-close-btn');
    const container = document.getElementById('lottie-container');
    const modal = document.getElementById('result-modal');
    const content = document.getElementById('result-modal-content');

    if (!modal || !container) return;

    container.innerHTML = '';

    const successUrl = 'https://lottie.host/8bd46050-5480-4b02-acc2-9a72e5cf9c7c/RXA1BRWUOM.lottie';
    const errorUrl = 'https://lottie.host/07e9260d-d059-4a09-89bc-c87192446ddb/Mg7YfBWx79.lottie';

    const player = document.createElement('dotlottie-wc');
    player.setAttribute('autoplay', 'true');
    player.setAttribute('loop', 'false');
    player.style.width = '120px';
    player.style.height = '120px';
    player.style.margin = '0 auto';

    if (type === 'success') {
        player.setAttribute('src', successUrl);
        btn.className = "w-full py-3 rounded-xl font-semibold text-white bg-[#03A64A] hover:bg-green-700 transition-all shadow-md";
        titleEl.className = 'text-2xl font-bold mb-2 text-green-600';
        if (reload) {
            btn.onclick = () => location.reload();
        } else {
            btn.onclick = hideResult;
        }
    } else {
        player.setAttribute('src', errorUrl);
        btn.className = "w-full py-3 rounded-xl font-semibold text-white bg-red-500 hover:bg-red-600 transition-all shadow-md";
        titleEl.className = 'text-2xl font-bold mb-2 text-red-600';
        btn.onclick = hideResult;
    }

    container.appendChild(player);
    titleEl.innerText = title;
    textEl.innerText = text;

    modal.classList.remove('hidden');

    setTimeout(() => {
        content.classList.replace('scale-95', 'scale-100');
        content.classList.replace('opacity-0', 'opacity-100');
    }, 10);
}

function hideResult() {
    const modal = document.getElementById('result-modal');
    const content = document.getElementById('result-modal-content');
    content.classList.replace('scale-100', 'scale-95');
    content.classList.replace('opacity-100', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

function showLoading(message) {
    const loadingModal = document.createElement('div');
    loadingModal.id = 'action-loading-modal';
    loadingModal.className = 'fixed inset-0 z-[60] backdrop-blur-md flex items-center justify-center';
    loadingModal.innerHTML = `
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-sm w-full mx-4 text-center relative z-10">
            <div class="flex justify-center mb-4">
                <div class="w-12 h-12 rounded-full border-4 border-gray-200 border-t-[#03A64A] animate-spin"></div>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">${message}</h3>
            <p class="text-sm text-gray-500">Please wait...</p>
        </div>`;
    document.body.appendChild(loadingModal);
}

function hideLoading() {
    document.getElementById('action-loading-modal')?.remove();
}

document.addEventListener('DOMContentLoaded', function () {
    // User management functionality
    const userModal = document.getElementById('user-modal');
    const userViewModal = document.getElementById('user-view-modal');
    const userForm = document.getElementById('user-form');
    const modalTitle = document.getElementById('modal-title');
    const userIdField = document.getElementById('user-id');
    const passwordField = document.getElementById('password-field');
    const closeModal = document.getElementById('close-modal');
    const cancelBtn = document.getElementById('cancel-btn');
    const addUserBtn = document.getElementById('add-user-btn');
    const addFirstUserBtn = document.getElementById('add-first-user-btn');

    // --- Standard Modal Logic ---
    function openAddUserModal() {
        modalTitle.textContent = 'Add User';
        userForm.reset();
        userIdField.value = '';
        passwordField.style.display = 'block';
        document.getElementById('password').required = true;
        userModal.classList.remove('hidden');
        const mc = document.getElementById('user-modal-content');
        mc.classList.remove('modal-animate-in');
        void mc.offsetWidth;
        mc.classList.add('modal-animate-in');
    }

    function closeUserModal() {
        userModal.classList.add('hidden');
        userForm.reset();
    }

    addUserBtn?.addEventListener('click', openAddUserModal);
    addFirstUserBtn?.addEventListener('click', openAddUserModal);
    closeModal.addEventListener('click', closeUserModal);
    cancelBtn.addEventListener('click', closeUserModal);

    userModal.addEventListener('click', function (e) {
        if (e.target === userModal) closeUserModal();
    });

    // Form submission
    userForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(userForm);
        const userData = Object.fromEntries(formData);
        const isEdit = !!userData.id;

        showLoading(isEdit ? 'Updating user...' : 'Creating user...');

        const apiData = {
            firstName: userData.firstName,
            lastName: userData.lastName,
            email: userData.email,
            phone: userData.phone || null,
            role: userData.role,
            isActive: userData.isActive === 'on' || userData.isActive === true
        };

        if (userData.password) apiData.password = userData.password;

        const url = isEdit ? `/admin/users/${userData.id}/update` : '/admin/users/create';
        const method = isEdit ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': userData._token
            },
            body: JSON.stringify(apiData)
        })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showResult('Success!', data.message, 'success');
                } else {
                    showResult('Error!', data.message, 'error');
                }
            })
            .catch(error => {
                hideLoading();
                showResult('Error!', 'An error occurred while processing your request', 'error');
            });
    });

    // View user modal logic

    function viewUserById(userId) {
        showLoading('Loading user details...');

        fetch(`/admin/users/${userId}`, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(response => response.json().then(data => ({ status: response.status, data })))
            .then(({ status, data }) => {
                hideLoading();
                if (data.success) {
                    // Ensure modal is visible before populating
                    userViewModal.classList.remove('hidden');
                    document.documentElement.style.overflow = 'hidden';
                    // Small delay to ensure DOM is ready
                    setTimeout(() => {
                        populateViewModal(data.user);
                    }, 10);
                } else {
                    showResult('Error!', data.message || 'Failed to load user details', 'error');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error loading user details:', error);
                showResult('Error!', 'An error occurred while loading user details', 'error');
            });
    }

    function populateViewModal(user) {
        const name = `${user.firstName || ''} ${user.lastName || ''}`.trim();
        const email = user.email || '';
        const phone = user.phone || 'No phone';
        const role = user.roles.includes('ROLE_ADMIN') ? 'Admin' : (user.roles.includes('ROLE_STAFF') ? 'Staff' : 'User');
        const isActive = (user.isActive === true || user.isActive === 'true' || user.isActive === 1);
        const status = isActive ? 'Active' : 'Inactive';
        const createdAt = user.createdAt || '--';
        const lastLogin = user.lastLoginAt || '--';
        let image = user.profileImage || '';

        // Normalize image path - remove leading slash if present but not if it's part of absolute URL
        if (image && image.startsWith('/') && !image.startsWith('//')) {
            image = image.substring(1);
        }

        // Basic info - with null checks
        const nameEl = document.querySelector('.uv-name');
        const roleEl = document.querySelector('.uv-role');
        const initialsEl = document.querySelector('.uv-initials');

        if (nameEl) nameEl.textContent = name || '';
        if (roleEl) roleEl.textContent = role || 'User';
        if (initialsEl) initialsEl.textContent = (name || '').split(' ').map(n => n[0]).join('').toUpperCase() || 'U';

        // Status badge
        const statusEl = document.querySelector('.uv-status-badge');
        if (statusEl) {
            statusEl.textContent = status;
            statusEl.className = 'uv-status-badge px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider';
            if (isActive) {
                statusEl.classList.add('bg-green-100', 'text-green-700');
            } else {
                statusEl.classList.add('bg-red-100', 'text-red-700');
            }
        }
        // Avatar
        const imgEl = document.querySelector('.uv-image');
        if (imgEl) {
            if (image && image.trim()) {
                // If it's already an absolute URL or starts with /, use it as is
                if (image.startsWith('http') || image.startsWith('//') || image.startsWith('/')) {
                    imgEl.src = image;
                } else {
                    imgEl.src = '/' + image;
                }
                imgEl.classList.remove('hidden');
                if (initialsEl) initialsEl.classList.add('hidden');
            } else {
                imgEl.classList.add('hidden');
                if (initialsEl) initialsEl.classList.remove('hidden');
            }
        }

        // Info sections configuration
        const infoSections = [
            {
                title: 'Contact Information',
                fields: [
                    { key: 'email', label: 'Email Address', icon: 'mail-outline', value: email },
                    { key: 'phone', label: 'Phone Number', icon: 'call-outline', value: phone }
                ]
            },
            {
                title: 'Account Activity',
                fields: [
                    { key: 'createdAt', label: 'Member Since', icon: 'calendar-outline', value: createdAt },
                    { key: 'lastLogin', label: 'Last Login', icon: 'log-in-outline', value: lastLogin }
                ]
            }
        ];

        // Render info sections
        const infosContainer = document.getElementById('uv-info-sections');
        if (infosContainer) {
            infosContainer.innerHTML = '';
            infoSections.forEach(section => {
                const sectionDiv = document.createElement('div');
                sectionDiv.className = 'space-y-4';
                sectionDiv.innerHTML = `
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 pb-1">
                        ${section.title}
                    </h4>
                    ${section.fields.map(field => `
                        <div class="flex items-start gap-3">
                            <div class="mt-1 text-bright-green"><ion-icon name="${field.icon}"></ion-icon></div>
                            <div>
                                <p class="text-[10px] text-light-gray uppercase">${field.label}</p>
                                <p class="text-xs font-medium text-dark-forest-green">${field.value || '--'}</p>
                            </div>
                        </div>
                    `).join('')}
                `;
                infosContainer.appendChild(sectionDiv);
            });
        }
    }

    function closeUserView() {
        userViewModal.classList.add('hidden');
        document.documentElement.style.overflow = '';
    }

    document.getElementById('user-view-close')?.addEventListener('click', closeUserView);
    document.getElementById('user-view-close-2')?.addEventListener('click', closeUserView);
    userViewModal?.addEventListener('click', function (e) { if (e.target === userViewModal) closeUserView(); });

    // Action button listeners
    document.addEventListener('click', function (e) {
        // Toggle Status Button
        if (e.target.closest('.toggle-status-btn')) {
            const btn = e.target.closest('.toggle-status-btn');
            const userId = btn.getAttribute('data-user-id');
            const newStatus = btn.getAttribute('data-new-status');
            toggleUserStatus(userId, newStatus);
        }
        // Delete User Button
        else if (e.target.closest('.delete-user-btn')) {
            const btn = e.target.closest('.delete-user-btn');
            const userId = btn.getAttribute('data-user-id');
            deleteUser(userId);
        }
        // View User Button
        else if (e.target.closest('.view-user-btn')) {
            const btn = e.target.closest('.view-user-btn');
            const userId = btn.getAttribute('data-user-id');
            viewUserById(userId);
        }
        // Edit User Button
        else if (e.target.closest('.edit-user-btn')) {
            const btn = e.target.closest('.edit-user-btn');
            const userId = btn.getAttribute('data-user-id');
            editUser(userId);
        }
    });
});
function editUser(userId) {
    fetch(`/admin/users/${userId}`, {
        method: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.user;
                document.getElementById('user-id').value = user.id;
                document.getElementById('first-name').value = user.firstName;
                document.getElementById('last-name').value = user.lastName;
                document.getElementById('email').value = user.email;
                document.getElementById('phone').value = user.phone || '';
                document.getElementById('role').value = user.roles.includes('ROLE_ADMIN')
                    ? 'ROLE_ADMIN'
                    : (user.roles.includes('ROLE_STAFF') ? 'ROLE_STAFF' : 'ROLE_USER');
                document.getElementById('is-active').checked = user.isActive;

                document.getElementById('password-field').style.display = 'none';
                document.getElementById('password').required = false;

                document.getElementById('user-modal').classList.remove('hidden');
                document.getElementById('modal-title').textContent = 'Edit User';
            }
        });
}

function toggleUserStatus(userId, newStatus) {
    const action = newStatus === 'true' ? 'activate' : 'deactivate';
    const userRow = document.querySelector(`[data-user-id="${userId}"]`);
    const userName = userRow?.getAttribute('data-user-name') || 'this user';
    const userRole = userRow?.getAttribute('data-user-role') || 'User';

    const modal = document.getElementById('confirm-modal-status');
    const messageEl = document.getElementById('confirm-status-message');
    const proceedBtn = document.getElementById('confirm-status-proceed');
    const cancelBtn = document.getElementById('confirm-status-cancel');

    messageEl.textContent = `Are you sure you want to ${action} ${userName}?`;
    modal.classList.remove('hidden');

    // Remove any existing listeners to prevent duplicates
    const newProceedBtn = proceedBtn.cloneNode(true);
    const newCancelBtn = cancelBtn.cloneNode(true);
    proceedBtn.parentNode.replaceChild(newProceedBtn, proceedBtn);
    cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);

    document.getElementById('confirm-status-proceed').addEventListener('click', function () {
        modal.classList.add('hidden');
        performStatusUpdate(userId, newStatus);
    });

    document.getElementById('confirm-status-cancel').addEventListener('click', function () {
        modal.classList.add('hidden');
    });
}

function updateUserRowStatus(userId, isActive) {
    const userRow = document.querySelector(`[data-user-id="${userId}"]`);
    if (!userRow) return;

    // Update data attributes
    userRow.setAttribute('data-user-status', isActive ? 'Active' : 'Inactive');

    // Update status badge
    const statusCell = userRow.cells[3];
    const statusBadge = statusCell.querySelector('.inline-flex');
    if (statusBadge) {
        statusBadge.className = `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${isActive
            ? 'bg-transparent dark:bg-gray-800 text-green-800 dark:text-primary-200'
            : 'bg-red-100 dark:bg-gray-700/50 text-red-800 dark:text-red-400'
            }`;
        statusBadge.textContent = isActive ? 'Active' : 'Inactive';
    }
    // Update toggle button
    const toggleBtn = userRow.querySelector('.toggle-status-btn');
    if (toggleBtn) {
        toggleBtn.setAttribute('data-new-status', isActive ? 'false' : 'true');
        toggleBtn.setAttribute('title', isActive ? 'Deactivate User' : 'Activate User');

        // Update button color
        if (isActive) {
            toggleBtn.classList.remove('text-green-600');
            toggleBtn.classList.add('text-orange-600', 'dark:!text-yellow-200', 'hover:text-yellow-100');
        } else {
            toggleBtn.classList.remove('text-orange-600', 'dark:!text-yellow-200', 'hover:text-yellow-100');
            toggleBtn.classList.add('text-green-600');
        }

        // Update icon
        const icon = toggleBtn.querySelector('ion-icon');
        if (icon) {
            icon.setAttribute('name', isActive ? 'power-outline' : 'checkmark-circle-outline');
        }
    }
}

function performStatusUpdate(userId, newStatus) {
    const csrfToken = document.getElementById('csrf-token').value;

    showLoading(newStatus === 'true' ? 'Activating user...' : 'Deactivating user...');

    fetch(`/admin/users/${userId}/toggle-status`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ isActive: newStatus === 'true' })
    })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {

                const updatedStatus = data.isActive !== undefined ? data.isActive : (newStatus === 'true');
                updateUserRowStatus(userId, updatedStatus);
                showResult('Success!', data.message, 'success', false);
            } else {
                showResult('Error!', data.message, 'error');
            }
        })
        .catch(error => {
            hideLoading();
            showResult('Error!', 'An error occurred while updating user status', 'error');
        });
}

function deleteUser(userId) {
    const userRow = document.querySelector(`[data-user-id="${userId}"]`);
    const userName = userRow?.getAttribute('data-user-name') || 'this user';

    const modal = document.getElementById('confirm-modal-delete');
    const messageEl = document.getElementById('confirm-delete-message');
    const proceedBtn = document.getElementById('confirm-delete-proceed');
    const cancelBtn = document.getElementById('confirm-delete-cancel');

    messageEl.textContent = `You are about to permanently delete ${userName}. All user data will be removed.`;
    modal.classList.remove('hidden');

    // Remove any existing listeners to prevent duplicates
    const newProceedBtn = proceedBtn.cloneNode(true);
    const newCancelBtn = cancelBtn.cloneNode(true);
    proceedBtn.parentNode.replaceChild(newProceedBtn, proceedBtn);
    cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);

    document.getElementById('confirm-delete-proceed').addEventListener('click', function () {
        modal.classList.add('hidden');
        performDeleteUser(userId);
    });

    document.getElementById('confirm-delete-cancel').addEventListener('click', function () {
        modal.classList.add('hidden');
    });
}

function performDeleteUser(userId) {
    const csrfToken = document.getElementById('csrf-token').value;

    showLoading('Deleting user...');

    fetch(`/admin/users/${userId}/delete`, {
        method: 'DELETE',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        }
    })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showResult('User Deleted!', data.message || 'User has been successfully deleted', 'success');
            } else {
                showResult('Error!', data.message || 'Failed to delete user', 'error');
            }
        })
        .catch(error => {
            hideLoading();
            showResult('Error!', 'An error occurred while deleting the user', 'error');
        });
}