import { create as FilePondCreate, registerPlugin } from 'filepond';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';
import 'filepond/dist/filepond.min.css';

registerPlugin(FilePondPluginFileValidateType, FilePondPluginFileValidateSize);

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const activeTokens = new Map();

function getBaseUrl() {
    return window.filepond_upload_url ?? '/admin/upload/tmp';
}

function getRevertUrl() {
    return window.filepond_revert_url ?? '/admin/upload/tmp';
}

async function revertAllPondTokens() {
    const requests = [];
    activeTokens.forEach((token) => {
        if (!token) return;
        requests.push(
            fetch(`${getRevertUrl()}/${token}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                keepalive: true,
            }).catch(() => {})
        );
    });
    await Promise.allSettled(requests);
    activeTokens.clear();
}

function createPond(inputEl, { type = 'audio', required = false, tokenInputName = null } = {}) {
    const pondId    = tokenInputName ?? type + '_' + Math.random().toString(36).slice(2);
    const tokenInput = tokenInputName
        ? document.querySelector(`input[name="${tokenInputName}"]`)
        : null;

    activeTokens.set(pondId, null);

    const maxSize = type === 'audio' ? '200MB' : '4MB';

    const acceptedTypes = type === 'audio'
        ? ['audio/mpeg', 'audio/mp3', 'audio/ogg', 'audio/wav']
        : ['image/jpeg', 'image/png', 'image/webp'];

    const pond = FilePondCreate(inputEl, {
        allowMultiple: false,
        maxFileSize: maxSize,
        acceptedFileTypes: acceptedTypes,
        name: 'file',
        labelIdle: `<span class="filepond-label-idle">Drag & drop or <span class="filepond--label-action">browse</span></span>`,
        server: {
            process: {
                url: `${getBaseUrl()}?type=${type}`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                withCredentials: false,
                onload: (response) => {
                    const token = response.trim();
                    activeTokens.set(pondId, token);
                    if (tokenInput) tokenInput.value = token;
                    return token;
                },
                onerror: (response) => {
                    return response;
                },
            },
            revert: {
                url: getRevertUrl(),
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                },
            },
        },
        onremovefile: () => {
            activeTokens.set(pondId, null);
            if (tokenInput) tokenInput.value = '';
        },
    });

    return pond;
}

window.createFilePond       = createPond;
window.revertAllPondTokens  = revertAllPondTokens;

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-filepond]').forEach((el) => {
        const type      = el.dataset.pondType     ?? 'audio';
        const tokenName = el.dataset.pondToken    ?? null;
        const req       = el.dataset.pondRequired === 'true';
        createPond(el, { type, required: req, tokenInputName: tokenName });
    });
});
