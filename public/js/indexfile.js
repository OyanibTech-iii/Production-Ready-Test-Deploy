const modal = document.getElementById('chat-modal');
const header = document.getElementById('chat-header');
const input = document.getElementById('chat-input');
const box = document.getElementById('chat-box');
const ficobotAvatarSrc = '/logos/ficoBot.png';

let isDragging = false;
let offsetX, offsetY;
function sendSuggestion(text) {
	const input = document.getElementById('chat-input');
	const suggestions = document.getElementById('chat-suggestions');
	input.value = text;
	sendMessage();

	if (suggestions) {
		suggestions.classList.add('hidden');
	}
}

function appendThinking() {
	const id = 'thinking-' + Date.now();
	const html = `
    <div id="${id}" class="flex flex-row items-end gap-2 w-full mb-1 max-w-[95%]" role="status" aria-live="polite" aria-label="ficoBot is typing">
        <img src="${ficobotAvatarSrc}" alt="" width="28" height="28" class="w-7 h-7 rounded-full object-cover shrink-0 border border-gray-100 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-800" />
        <div class="inline-block py-1.5 px-3 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-2xl rounded-bl-none border border-gray-100 dark:border-gray-600 shadow-sm">
            <div class="typing-dots flex items-center h-4 gap-0.5" aria-hidden="true">
                <span class="dot"></span><span class="dot"></span><span class="dot"></span>
            </div>
        </div>
    </div>`;
	box.insertAdjacentHTML('beforeend', html);
	const startLabel = document.getElementById('start-label');
	if (startLabel) startLabel.classList.add('hidden');
	box.scrollTo({ top: box.scrollHeight, behavior: 'smooth' });
	return id;
}

async function sendMessage() {
	const text = input.value.trim();
	if (!text) return;

	// 1. Show User Message
	appendMessage(text, 'user');
	input.value = '';

	// 2. Show Thinking Dots
	const thinkingId = appendThinking();

	const loaderEl = () => document.getElementById(thinkingId);

	try {
		const response = await fetch('/chat-api', {
			method: 'POST',
			body: JSON.stringify({ message: text }),
			headers: { 'Content-Type': 'application/json' }
		});

		const raw = await response.text();
		let data = {};
		try {
			data = raw ? JSON.parse(raw) : {};
		} catch (parseErr) {
			console.error('chat-api: response is not JSON', parseErr, raw.slice(0, 200));
		}

		if (loaderEl()) loaderEl().remove();

		const answer =
			typeof data.answer === 'string' && data.answer.trim() !== ''
				? data.answer
				: "Sorry, I'm having trouble connecting right now.";
		appendMessage(answer, 'bot');
	} catch (e) {
		console.error('Support API Error', e);
		if (loaderEl()) loaderEl().remove();
		appendMessage("Sorry, I'm having trouble connecting right now.", 'bot');
	}
}

function isMobileViewport() {
	return window.innerWidth <= 640;
}

function resetModalPosition() {
	modal.style.top = '';
	if (isMobileViewport()) {
		modal.style.left = 'auto';
		modal.style.right = '';
		modal.style.bottom = '';
	} else {
		modal.style.right = '';
		modal.style.left = '';
		modal.style.bottom = '';
	}
}

header.addEventListener('mousedown', (e) => {
	if (isMobileViewport()) return;
	if (e.target.closest('div[onclick]')) return;
	if (e.target.closest('[data-ficobot-clear-open]')) return;
	isDragging = true;
	const rect = modal.getBoundingClientRect();
	offsetX = e.clientX - rect.left;
	offsetY = e.clientY - rect.top;
	modal.style.bottom = 'auto';
	modal.style.right = 'auto';
	modal.style.left = rect.left + 'px';
	modal.style.top = rect.top + 'px';
	modal.style.transition = 'none';
	document.body.style.userSelect = 'none';
});

document.addEventListener('mousemove', (e) => {
	if (!isDragging) return;
	let x = e.clientX - offsetX;
	let y = e.clientY - offsetY;
	x = Math.max(0, Math.min(x, window.innerWidth - modal.offsetWidth));
	y = Math.max(0, Math.min(y, window.innerHeight - modal.offsetHeight));
	modal.style.left = x + 'px';
	modal.style.top = y + 'px';
});

document.addEventListener('mouseup', () => {
	if (isDragging) {
		isDragging = false;
		modal.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
		document.body.style.userSelect = 'auto';
	}
});

function toggleChat() {
	if (modal.classList.contains('hidden')) {
		resetModalPosition();
		modal.classList.remove('hidden');
		modal.classList.add('flex');
		input.focus();
		box.scrollTop = box.scrollHeight;
	} else {
		modal.classList.add('hidden');
		modal.classList.remove('flex');
	}
}

function appendMessage(text, side) {
	const isUser = side === 'user';
	const bubbleClass = isUser
		? 'bg-bright-green text-white rounded-br-none shadow-sm'
		: 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-bl-none border border-gray-100 dark:border-gray-600';

	const messageHtml = isUser
		? `
        <div class="flex flex-col items-end w-full mb-1">
            <div class="inline-block py-1.5 px-3 text-xs ${bubbleClass} rounded-2xl max-w-[85%] break-words leading-relaxed">
                ${text}
            </div>
        </div>`
		: `
        <div class="flex flex-row items-end gap-2 w-full mb-1 max-w-[95%]">
            <img src="${ficobotAvatarSrc}" alt="" width="28" height="28" class="w-7 h-7 rounded-full object-cover shrink-0 border border-gray-100 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-800" />
            <div class="inline-block py-1.5 px-3 text-xs ${bubbleClass} rounded-2xl max-w-[85%] break-words leading-relaxed">
                ${text}
            </div>
        </div>`;

	box.insertAdjacentHTML('beforeend', messageHtml);

	const startLabel = document.getElementById('start-label');
	if (startLabel) startLabel.classList.add('hidden');

	box.scrollTo({ top: box.scrollHeight, behavior: 'smooth' });
}

input.addEventListener('keypress', (e) => {
	if (e.key === 'Enter') sendMessage();
});

window.addEventListener('resize', () => {
	if (!modal.classList.contains('hidden')) {
		resetModalPosition();
	}
});
document.addEventListener('light-mode-set', () => {
	document.documentElement.classList.remove('dark');
	localStorage.setItem('theme', 'light');
});

document.addEventListener('dark-mode-set', () => {
	document.documentElement.classList.add('dark');
	localStorage.setItem('theme', 'dark');
});

document.addEventListener('alpine:init', () => {
	Alpine.store('theme', {
		isDark: document.documentElement.classList.contains('dark'),
	});
});