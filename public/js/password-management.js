document.addEventListener('DOMContentLoaded', function () {
    // Wait for dotlottie-wc to be defined before initializing
    if (!customElements.get('dotlottie-wc')) {
        customElements.whenDefined('dotlottie-wc').then(() => {
            initPasswordManagement();
        });
    } else {
        initPasswordManagement();
    }
});

function initPasswordManagement() {
    let pendingPasswordData = null;
    const passwordConfirmModal = document.getElementById('password-confirm-modal');
    const resultModal = document.getElementById('result-modal');
    const resultModalContent = document.getElementById('result-modal-content');
 
    function showResult(title, text, type) {
        const titleEl = document.getElementById('result-title');
        const textEl = document.getElementById('result-text');
        const btn = document.getElementById('result-close-btn');
        const container = document.getElementById('lottie-container');
        const modal = document.getElementById('result-modal');
        const content = document.getElementById('result-modal-content');

        // 1. Clean up previous animation
        container.innerHTML = '';

        // 2. Define Stable URLs (Direct Asset Links, NOT Embed links)
        const successUrl = 'https://lottie.host/8bd46050-5480-4b02-acc2-9a72e5cf9c7c/RXA1BRWUOM.lottie';
        const errorUrl = 'https://lottie.host/07e9260d-d059-4a09-89bc-c87192446ddb/Mg7YfBWx79.lottie';

        // 3. Create the dotLottie Web Component
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
        } else {
            player.setAttribute('src', errorUrl);
            btn.className = "w-full py-3 rounded-xl font-semibold text-white bg-red-500 hover:bg-red-600 transition-all shadow-md";
            titleEl.classList.add('text-red-600');
            titleEl.classList.remove('text-green-600');
        }

        container.appendChild(player);

        // 4. Update text and show modal
        titleEl.innerText = title;
        textEl.innerText = text;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        // Trigger Tailwind transitions
        setTimeout(() => {
            content.classList.replace('scale-95', 'scale-100');
            content.classList.replace('opacity-0', 'opacity-100');
        }, 10);
    }

    function hideResult() {
        resultModalContent.classList.replace('scale-100', 'scale-95');
        resultModalContent.classList.replace('opacity-100', 'opacity-0');
        setTimeout(() => {
            resultModal.classList.replace('flex', 'hidden');
        }, 200);
    }

    document.getElementById('result-close-btn')?.addEventListener('click', hideResult);

    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', function () {
            const input = document.getElementById(this.dataset.target);
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            const icon = this.querySelector('ion-icon');
            if (icon) icon.name = isPassword ? 'eye-outline' : 'eye-off-outline';
        });
    });

    // Main Form Submit
    document.getElementById('password-form-main')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const currentPassword = document.getElementById('current-password-main').value;
        const newPassword = document.getElementById('new-password-main').value;
        const confirmPassword = document.getElementById('confirm-password-main').value;

        if (!currentPassword || !newPassword) {
            showResult('Oops! Error', 'All fields are required', 'error');
            return;
        }
        if (newPassword !== confirmPassword) {
            showResult('Mismatch', 'New passwords do not match', 'error');
            return;
        }
        if (newPassword.length < 8) {
            showResult('Too Short', 'Password must be at least 8 characters', 'error');
            return;
        }

        pendingPasswordData = { currentPassword, newPassword };
        if (passwordConfirmModal) passwordConfirmModal.style.display = 'flex';
    });

    // Confirm Action
    document.getElementById('password-confirm-btn')?.addEventListener('click', function () {
        if (!pendingPasswordData) return;
        if (passwordConfirmModal) passwordConfirmModal.style.display = 'none';

        const formData = new FormData();
        formData.append('currentPassword', pendingPasswordData.currentPassword);
        formData.append('newPassword', pendingPasswordData.newPassword);

        fetch('/admin/api/profile/password', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('password-loading-modal')?.remove();
            if (data.success) {
                showResult('Success!', data.message, 'success');
                document.getElementById('password-form-main').reset();
                pendingPasswordData = null;
            } else {
                showResult('Error', data.message, 'error');
            }
        })
        .catch(() => {
            document.getElementById('password-loading-modal')?.remove();
            showResult('Error', 'An unexpected error occurred', 'error');
        });
    });
}