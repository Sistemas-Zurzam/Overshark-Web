// Nombre de producto tal como se muestra en la web (mismo criterio que App\Support\ProductName):
// formato título y nombres de Zazu corregidos, p. ej. "BABY TY MANGA" → "Baby Tee Manga".
export const productTitle = (s) => String(s ?? '')
    .toLowerCase()
    .replace(/(^|\s)\S/g, (c) => c.toUpperCase())
    .replace(/\bBaby\s+Ty\b/g, 'Baby Tee');
