// Reduce una foto en el navegador antes de subirla (máx. maxSide px por lado, WebP) para que entre en el
// límite de subida del servidor. Si no se puede convertir o no gana peso, devuelve el archivo original.
export const shrinkImage = (file, maxSide = 2000, quality = 0.86) => new Promise((resolve) => {
    if (!file.type.startsWith('image/')) {
        resolve(file);
        return;
    }

    const url = URL.createObjectURL(file);
    const img = new Image();
    img.onload = () => {
        URL.revokeObjectURL(url);
        const scale = Math.min(1, maxSide / Math.max(img.naturalWidth, img.naturalHeight));
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(img.naturalWidth * scale);
        canvas.height = Math.round(img.naturalHeight * scale);
        canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
        canvas.toBlob((blob) => {
            // Si no se pudo convertir o no gana peso, se envía la original.
            if (!blob || blob.size >= file.size) {
                resolve(file);
                return;
            }
            resolve(new File([blob], file.name.replace(/\.[^.]+$/, '') + '.webp', { type: 'image/webp' }));
        }, 'image/webp', quality);
    };
    img.onerror = () => {
        URL.revokeObjectURL(url);
        resolve(file);
    };
    img.src = url;
});
