(() => {
  "use strict";
  const valid = file => {
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 2 * 1024 * 1024)
      throw new Error('Gunakan JPG, PNG atau WebP maksimal 2 MB.');
  };
  window.CbtMedia = {valid, async read(file) {
    if (!file || !file.size) return null;
    valid(file);
    const bitmap = await createImageBitmap(file);
    try {
      const scale = Math.min(1, 1600 / Math.max(bitmap.width, bitmap.height));
      const canvas = document.createElement('canvas');
      canvas.width = Math.max(1, Math.round(bitmap.width * scale));
      canvas.height = Math.max(1, Math.round(bitmap.height * scale));
      canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
      const data = canvas.toDataURL('image/webp', 0.9);
      if (data.length > 650000) throw new Error('Gambar terlalu besar untuk demo. Perkecil gambar lalu coba lagi.');
      return data;
    } finally { bitmap.close(); }
  }};
  document.addEventListener('change', event => {
    const input = event.target;
    if (!input.matches('[data-image-upload]')) return;
    const preview = input.closest('form').querySelector('[data-image-preview]');
    input.setCustomValidity('');
    if (preview?.dataset.previewUrl) URL.revokeObjectURL(preview.dataset.previewUrl);
    if (!input.files[0]) { if (preview) preview.hidden = true; return; }
    try {
      valid(input.files[0]);
      if (preview) { preview.src = preview.dataset.previewUrl = URL.createObjectURL(input.files[0]); preview.hidden = false; }
    } catch (error) { input.setCustomValidity(error.message); input.reportValidity(); if (preview) preview.hidden = true; }
  });
  document.addEventListener('click', event => {
    const button = event.target.closest('[data-enlarge-image]');
    if (!button) return;
    const source = document.getElementById(button.dataset.enlargeImage);
    if (!source?.getAttribute('src')) return;
    const dialog = document.createElement('dialog');
    dialog.className = 'image-dialog';
    const image = document.createElement('img'); image.src = source.src; image.alt = source.alt;
    const close = document.createElement('button'); close.type = 'button'; close.textContent = 'Tutup gambar';
    close.onclick = () => dialog.close(); dialog.addEventListener('close', () => dialog.remove());
    dialog.append(image, close); document.body.append(dialog); dialog.showModal();
  });
})();
