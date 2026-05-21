/**
 * Drag-and-drop for lesson content: .twig / .html → textarea; .pdf → file input only.
 * Rejects any other extension.
 */
(function () {
    'use strict';

    var ALLOWED = { twig: true, html: true, htm: true, pdf: true };

    function ext(name) {
        var i = name.lastIndexOf('.');
        return i >= 0 ? name.slice(i + 1).toLowerCase() : '';
    }

    function showMsg(el, text, isError) {
        if (!el) {
            return;
        }
        el.textContent = text || '';
        el.classList.toggle('text-red-600', !!isError);
        el.classList.toggle('text-bright-green', !!text && !isError);
        if (text) {
            window.clearTimeout(showMsg._t);
            showMsg._t = window.setTimeout(function () {
                el.textContent = '';
            }, 6000);
        }
    }

    function handleOneFile(file, textarea, fileInput, msgEl) {
        if (!file) {
            return;
        }
        var e = ext(file.name);
        if (!ALLOWED[e]) {
            showMsg(msgEl, 'Only .twig, .html, and .pdf files are allowed.', true);
            return;
        }
        showMsg(msgEl, '', false);
        if (e === 'pdf') {
            var dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
            showMsg(msgEl, 'PDF attached: ' + file.name + ' — save the form to upload.', false);
            return;
        }
        var reader = new FileReader();
        reader.onload = function () {
            textarea.value = reader.result || '';
            showMsg(msgEl, 'Loaded ' + file.name + ' into the content field.', false);
        };
        reader.onerror = function () {
            showMsg(msgEl, 'Could not read this file.', true);
        };
        reader.readAsText(file);
    }

    function init() {
        var root = document.getElementById('lesson-content-dropzone');
        if (!root) {
            return;
        }
        var textareaId = root.getAttribute('data-textarea-id');
        var fileId = root.getAttribute('data-file-id');
        var textarea = textareaId ? document.getElementById(textareaId) : null;
        var fileInput = fileId ? document.getElementById(fileId) : null;
        var msgEl = document.getElementById('lesson-content-drop-msg');
        var browseBtn = document.getElementById('lesson-content-browse-btn');

        if (!textarea || !fileInput) {
            return;
        }

        if (browseBtn) {
            browseBtn.addEventListener('click', function () {
                fileInput.click();
            });
        }

        fileInput.addEventListener('change', function () {
            if (fileInput.files && fileInput.files[0]) {
                handleOneFile(fileInput.files[0], textarea, fileInput, msgEl);
            }
        });

        ['dragenter', 'dragover'].forEach(function (type) {
            root.addEventListener(type, function (e) {
                e.preventDefault();
                e.stopPropagation();
                root.classList.add('border-bright-green', 'bg-primary-50/50');
            });
        });

        ['dragleave', 'drop'].forEach(function (type) {
            root.addEventListener(type, function (e) {
                e.preventDefault();
                e.stopPropagation();
                root.classList.remove('border-bright-green', 'bg-primary-50/50');
            });
        });

        root.addEventListener('drop', function (e) {
            var files = e.dataTransfer && e.dataTransfer.files;
            if (!files || !files.length) {
                return;
            }
            handleOneFile(files[0], textarea, fileInput, msgEl);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
