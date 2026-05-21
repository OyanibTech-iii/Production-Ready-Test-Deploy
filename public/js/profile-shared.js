const ProfileHandler = {
    init: function (config) {
        this.config = config;
        this.cacheElements();
        this.bindEvents();
        this.cropper = null;
        this.croppedBlob = null;
    },

    cacheElements: function () {
        this.uploadBtn = document.getElementById(this.config.uploadBtnId);
        this.fileInput = document.getElementById(this.config.fileInputId);
        this.profileForm = document.getElementById(this.config.profileFormId);
        this.passwordForm = document.getElementById(this.config.passwordFormId);
        
        // Lottie Modal Elements
        this.modal = document.getElementById('result-modal');
        this.modalContent = document.getElementById('result-modal-content');
        this.lottieContainer = document.getElementById('lottie-container');
        this.resultTitle = document.getElementById('result-title');
        this.resultText = document.getElementById('result-text');
        this.closeBtn = document.getElementById('result-close-btn');

        // Cropper Elements
        this.cropperModal = document.getElementById('cropper-modal');
        this.cropperImage = document.getElementById('cropper-image');
        this.applyCropBtn = document.getElementById('apply-crop-btn');
        this.cancelCropBtn = document.getElementById('cancel-crop-btn');
        this.closeCropperBtn = document.getElementById('close-cropper-btn');
    },

    bindEvents: function () {
        this.uploadBtn?.addEventListener('click', () => this.fileInput?.click());

        this.fileInput?.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (event) => {
                if (!this.cropperModal || !this.cropperImage) {
                    // Fallback to non-crop preview if modal elements missing
                    this.updatePreview(event.target.result);
                    return;
                }

                this.cropperImage.src = event.target.result;
                this.cropperModal.classList.remove('hidden');
                
                if (this.cropper) {
                    this.cropper.destroy();
                }
                
                this.cropper = new Cropper(this.cropperImage, {
                    aspectRatio: 1,
                    viewMode: 2,
                    guides: true,
                    background: false,
                    autoCropArea: 1,
                    responsive: true,
                });
            };
            reader.readAsDataURL(file);
        });

        // Cropper events
        this.closeCropperBtn?.addEventListener('click', () => this.closeCropper());
        this.cancelCropBtn?.addEventListener('click', () => this.closeCropper());
        this.applyCropBtn?.addEventListener('click', () => this.applyCrop());

        this.profileForm?.addEventListener('submit', (e) => this.handleProfileSubmit(e));
        this.passwordForm?.addEventListener('submit', (e) => this.handlePasswordSubmit(e));
    },

    closeCropper: function () {
        this.cropperModal.classList.add('hidden');
        if (this.cropper) {
            this.cropper.destroy();
            this.cropper = null;
        }
        this.fileInput.value = '';
    },

    applyCrop: function () {
        if (!this.cropper) return;
        
        const canvas = this.cropper.getCroppedCanvas({
            width: 400,
            height: 400,
        });
        
        canvas.toBlob((blob) => {
            this.croppedBlob = blob;
            const croppedUrl = URL.createObjectURL(blob);
            this.updatePreview(croppedUrl);
            
            this.cropperModal.classList.add('hidden');
            this.cropper.destroy();
            this.cropper = null;
        }, 'image/jpeg');
    },

    updatePreview: function (src) {
        const currentPreview = document.getElementById(this.config.previewId);
        const currentInitials = document.getElementById(this.config.initialsId);

        if (currentPreview) {
            currentPreview.src = src;
        } else if (currentInitials) {
            const img = document.createElement('img');
            img.id = this.config.previewId;
            img.src = src;
            img.className = 'w-full h-full object-cover';
            img.alt = 'Profile';
            currentInitials.replaceWith(img);
        }
    },

    // --- Lottie Modal Methods ---
    showResult: function (title, text, type) {
        if (!this.modal || !this.lottieContainer) return;

        this.lottieContainer.innerHTML = '';
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
            this.closeBtn.className = "w-full py-3 rounded-xl font-semibold text-white bg-[#03A64A] hover:bg-green-700 transition-all shadow-md";
            this.resultTitle.className = 'text-2xl font-bold mb-2 text-green-600';
            this.closeBtn.onclick = () => window.location.reload();
        } else {
            player.setAttribute('src', errorUrl);
            this.closeBtn.className = "w-full py-3 rounded-xl font-semibold text-white bg-red-500 hover:bg-red-600 transition-all shadow-md";
            this.resultTitle.className = 'text-2xl font-bold mb-2 text-red-600';
            this.closeBtn.onclick = () => this.hideResult();
        }

        this.lottieContainer.appendChild(player);
        this.resultTitle.innerText = title;
        this.resultText.innerText = text;

        this.modal.classList.remove('hidden');
        this.modal.classList.add('flex');
        
        setTimeout(() => {
            this.modalContent.classList.replace('scale-95', 'scale-100');
            this.modalContent.classList.replace('opacity-0', 'opacity-100');
        }, 10);
    },

    hideResult: function () {
        this.modalContent.classList.replace('scale-100', 'scale-95');
        this.modalContent.classList.replace('opacity-100', 'opacity-0');
        setTimeout(() => {
            this.modal.classList.replace('flex', 'hidden');
        }, 200);
    },

    setStatus: function (form, selector, message, success) {
        const statusEl = form.querySelector(selector);
        if (!statusEl) return;
        statusEl.textContent = message;
        statusEl.className = 'text-sm ' + (success === null ? 'text-light-gray' : success ? 'text-bright-green' : 'text-red-600');
    },

    handleProfileSubmit: async function (e) {
        e.preventDefault();
        const form = e.target;
        const statusSel = this.config.profileStatusSelector || '[data-profile-status]';

        this.setStatus(form, statusSel, 'Saving profile…', null);

        try {
            const formData = new FormData(form);
            if (this.croppedBlob) {
                formData.set('profileImage', this.croppedBlob, 'profile.jpg');
            }

            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            const contentType = response.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                const data = await response.json();
                if (data.success) {
                    this.setStatus(form, statusSel, data.message || 'Profile updated.', true);
                    this.showResult('Success!', data.message || 'Profile updated successfully.', 'success');
                } else {
                    this.setStatus(form, statusSel, data.message || 'Update failed.', false);
                    this.showResult('Error', data.message || 'Could not update profile.', 'error');
                }
            } else {
                throw new Error('Unexpected response format');
            }
        } catch (err) {
            this.setStatus(form, statusSel, 'Connection error.', false);
            this.showResult('Error', 'Connection error. Please try again.', 'error');
        }
    },

    handlePasswordSubmit: async function (e) {
        e.preventDefault();
        const form = e.target;
        const statusSel = this.config.passwordStatusSelector || '[data-password-status]';

        this.setStatus(form, statusSel, 'Updating password…', null);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            const data = await response.json();
            if (data.success) {
                this.setStatus(form, statusSel, data.message || 'Password updated.', true);
                form.reset();
                this.showResult('Success!', data.message || 'Password updated successfully.', 'success');
            } else {
                this.setStatus(form, statusSel, data.message || 'Update failed.', false);
                this.showResult('Error', data.message || 'Could not update password.', 'error');
            }
        } catch (err) {
            this.setStatus(form, statusSel, 'Connection error.', false);
            this.showResult('Error', 'Connection error. Please try again.', 'error');
        }
    },
};