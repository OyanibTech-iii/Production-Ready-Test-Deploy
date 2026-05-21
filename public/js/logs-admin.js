 (function () {
        const modal = document.getElementById('log-details-modal');
        const closeButtons = [
            document.getElementById('log-modal-close'),
            document.getElementById('log-modal-close-2')
        ];

        function openModal(button) {
            const timestamp = button.getAttribute('data-log-timestamp');
            const userId = button.getAttribute('data-log-userid');
            const username = button.getAttribute('data-log-username');
            const role = button.getAttribute('data-log-role');
            const action = button.getAttribute('data-log-action');
            const target = button.getAttribute('data-log-target');
            const ip = button.getAttribute('data-log-ip');
            const changesJson = button.getAttribute('data-log-changes');

            document.getElementById('ld-timestamp').textContent = timestamp || '';
            document.getElementById('ld-userid').textContent = userId || 'N/A';
            document.getElementById('ld-username').textContent = username || '';
            document.getElementById('ld-action').textContent = action || '';
            document.getElementById('ld-target').textContent = target || '';

            const roleSpan = document.querySelector('#ld-role span');
            roleSpan.textContent = role || '';

            // Parse and render changes
            const changesDiv = document.getElementById('ld-changes');
            const oldImageImg = document.getElementById('ld-profile-image-old');
            const newImageImg = document.getElementById('ld-profile-image-new');
            const oldImageWrap = document.getElementById('ld-old-image-wrap');
            const newImageWrap = document.getElementById('ld-new-image-wrap');

            if (changesJson) {
                try {
                    const changes = JSON.parse(changesJson);
                    if (Object.keys(changes).length > 0) {
                        let changesHTML = '<ul class="space-y-2">';
                        for (const [field, change] of Object.entries(changes)) {
                            const from = change.from !== undefined && change.from !== null ? change.from : '—';
                            const to = change.to !== undefined && change.to !== null ? change.to : '—';

                            if (['profileImage', 'avatar', 'profileImageUrl'].includes(field)) {
                                changesHTML += `<li><span class="font-medium">${field}</span>: (image updated)</li>`;
                                // Show old and new images
                                if (change.from && change.from !== 'none' && (field === 'profileImage' || field === 'profileImageUrl')) {
                                    oldImageImg.src = change.from;
                                    oldImageImg.onerror = function () {
                                        this.src = '/me/asking.png';
                                    };
                                    oldImageWrap.classList.remove('hidden');
                                } else {
                                    // Show fallback when no old image
                                    oldImageImg.src = '/me/asking.png';
                                    oldImageWrap.classList.remove('hidden');
                                }
                                if (change.to && (field === 'profileImage' || field === 'profileImageUrl')) {
                                    newImageImg.src = change.to;
                                    newImageImg.onerror = function () {
                                        this.src = '/me/asking.png';
                                    };
                                    newImageWrap.classList.remove('hidden');
                                }
                            } else {
                                changesHTML += `<li><span class="font-medium">${field}</span>: <span class="text-gray-600">${from}</span> <span class="mx-1">→</span> <span class="text-green-600">${to}</span></li>`;
                            }
                        }
                        changesHTML += '</ul>';
                        changesDiv.innerHTML = changesHTML;
                    } else {
                        changesDiv.innerHTML = '<p class="text-gray-500">No changes recorded</p>';
                        oldImageWrap.classList.add('hidden');
                        newImageWrap.classList.add('hidden');
                    }
                } catch (e) {
                    changesDiv.textContent = changesJson || 'No changes data';
                    oldImageWrap.classList.add('hidden');
                    newImageWrap.classList.add('hidden');
                }
            } else {
                changesDiv.innerHTML = '<p class="text-gray-500">No changes recorded</p>';
                oldImageWrap.classList.add('hidden');
                newImageWrap.classList.add('hidden');
            }

            modal.classList.remove('hidden');
            document.documentElement.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.classList.add('hidden');
            document.documentElement.style.overflow = '';
        }

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.view-log-details');
            if (btn) openModal(btn);
        });

        closeButtons.forEach(btn => btn && btn.addEventListener('click', closeModal));
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });
    })();