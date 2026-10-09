// Copia el runtime WASM de @mediapipe/tasks-vision a public/vendor para servirlo desde la tienda
// (probador virtual). Se ejecuta antes de `vite` y `vite build`; la carpeta destino no se versiona.
import { cpSync, existsSync, mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const source = join(root, 'node_modules', '@mediapipe', 'tasks-vision', 'wasm');
const target = join(root, 'public', 'vendor', 'mediapipe', 'wasm');

if (!existsSync(source)) {
    console.error('No se encontró @mediapipe/tasks-vision. Ejecuta `npm install`.');
    process.exit(1);
}

mkdirSync(target, { recursive: true });

for (const file of ['vision_wasm_internal.js', 'vision_wasm_internal.wasm', 'vision_wasm_nosimd_internal.js', 'vision_wasm_nosimd_internal.wasm']) {
    cpSync(join(source, file), join(target, file));
}

console.log('MediaPipe WASM copiado a public/vendor/mediapipe/wasm');
