document.addEventListener('DOMContentLoaded', function () {
    const uploadPhotoBtn = document.getElementById('upload-photo-btn-large');
    const changePhotoBtn = document.getElementById('change-photo-btn');
    const profileImageInput = document.getElementById('profile-image-input-large');
    
    // Cropper elements
    const cropperModal = document.getElementById('cropper-modal');
    const cropperImage = document.getElementById('cropper-image');
    const applyCropBtn = document.getElementById('apply-crop-btn');
    const cancelCropBtn = document.getElementById('cancel-crop-btn');
    const closeCropperBtn = document.getElementById('close-cropper-btn');
    
    let cropper = null;
    let croppedBlob = null;

    // --- Lottie Modal Helper Functions ---
    function showResult(title, text, type) {
        const titleEl = document.getElementById('result-title');
        const textEl = document.getElementById('result-text');
        const btn = document.getElementById('result-close-btn');
        const container = document.getElementById('lottie-container');
        const modal = document.getElementById('result-modal');
        const content = document.getElementById('result-modal-content');

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
            titleEl.classList.add('text-green-600');
            titleEl.classList.remove('text-red-600');
            
            // Rebind button to reload on success
            btn.onclick = () => location.reload();
        } else {
            player.setAttribute('src', errorUrl);
            btn.className = "w-full py-3 rounded-xl font-semibold text-white bg-red-500 hover:bg-red-600 transition-all shadow-md";
            titleEl.classList.add('text-red-600');
            titleEl.classList.remove('text-green-600');
            
            // Rebind button to just hide modal on error
            btn.onclick = hideResult;
        }

        container.appendChild(player);
        titleEl.innerText = title;
        textEl.innerText = text;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
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
            modal.classList.replace('flex', 'hidden');
        }, 200);
    }

    // --- Image Preview Logic ---
    uploadPhotoBtn?.addEventListener('click', () => profileImageInput.click());
    changePhotoBtn?.addEventListener('click', () => profileImageInput.click());

    profileImageInput?.addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (event) {
                // Open cropper modal
                cropperImage.src = event.target.result;
                cropperModal.classList.remove('hidden');
                
                if (cropper) {
                    cropper.destroy();
                }
                
                cropper = new Cropper(cropperImage, {
                    aspectRatio: 1,
                    viewMode: 2,
                    guides: true,
                    background: false,
                    autoCropArea: 1,
                    responsive: true,
                });
            };
            reader.readAsDataURL(file);
        }
    });

    // Close cropper logic
    const closeCropper = () => {
        cropperModal.classList.add('hidden');
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        profileImageInput.value = ''; // Reset input so same image can be picked again
    };

    closeCropperBtn?.addEventListener('click', closeCropper);
    cancelCropBtn?.addEventListener('click', closeCropper);

    applyCropBtn?.addEventListener('click', () => {
        if (!cropper) return;
        
        const canvas = cropper.getCroppedCanvas({
            width: 400,
            height: 400,
        });
        
        canvas.toBlob((blob) => {
            croppedBlob = blob;
            const croppedUrl = URL.createObjectURL(blob);
            
            const previewLarge = document.getElementById('profile-preview-large');
            const initialsLarge = document.getElementById('profile-initials-large');

            if (previewLarge) {
                previewLarge.src = croppedUrl;
            } else {
                const img = document.createElement('img');
                img.id = 'profile-preview-large';
                img.src = croppedUrl;
                img.className = 'w-full h-full object-cover';
                img.alt = 'Profile';

                const profileDiv = uploadPhotoBtn.parentElement;
                if (initialsLarge) initialsLarge.remove();
                profileDiv.appendChild(img);
            }
            
            cropperModal.classList.add('hidden');
            cropper.destroy();
            cropper = null;
        }, 'image/jpeg');
    });

    // --- Form Submission ---
    document.getElementById('profile-form-main')?.addEventListener('submit', function (e) {
        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);

        if (croppedBlob) {
            formData.append('profileImage', croppedBlob, 'profile.jpg');
        } else if (profileImageInput?.files[0]) {
            formData.append('profileImage', profileImageInput.files[0]);
        }

        // Show loading state
        const loadingModal = document.createElement('div');
        loadingModal.id = 'profile-loading-modal';
        loadingModal.className = 'fixed inset-0 z-50 backdrop-blur-md flex items-center justify-center';
        loadingModal.innerHTML = `
            <div class="absolute inset-0 bg-black/40"></div>
            <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-sm w-full mx-4 text-center relative z-10">
                <div class="flex justify-center mb-4">
                    <div class="w-12 h-12 rounded-full border-4 border-gray-200 border-t-[#03A64A] animate-spin"></div>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Updating Profile</h3>
                <p class="text-sm text-gray-500">Please wait while we save your changes...</p>
            </div>`;
        document.body.appendChild(loadingModal);

        fetch('/admin/api/profile/update', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('profile-loading-modal')?.remove();
            if (data.success) {
                showResult('Success!', data.message, 'success');
            } else {
                showResult('Error', data.message, 'error');
            }
        })
        .catch(error => {
            document.getElementById('profile-loading-modal')?.remove();
            showResult('Error', 'An unexpected error occurred', 'error');
        });
    });

    document.getElementById('cancel-profile-main')?.addEventListener('click', () => window.history.back());
});
