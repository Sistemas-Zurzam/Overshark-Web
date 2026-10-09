// Prepara la vista 3D del probador: copia el runtime ONNX (onnxruntime-web, MIT) a public/vendor/onnx
// y descarga una vez el modelo de profundidad Depth Anything V2 Small (Apache 2.0) a public/models.
// Se ejecuta antes de `vite` y `vite build`; ambas carpetas destino no se versionan.
import { cpSync, existsSync, mkdirSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');

const ortSource = join(root, 'node_modules', 'onnxruntime-web', 'dist');
const ortTarget = join(root, 'public', 'vendor', 'onnx');
mkdirSync(ortTarget, { recursive: true });
for (const file of ['ort-wasm-simd-threaded.asyncify.mjs', 'ort-wasm-simd-threaded.asyncify.wasm']) {
    cpSync(join(ortSource, file), join(ortTarget, file));
}

const repo = 'https://huggingface.co/onnx-community/depth-anything-v2-small/resolve/main';
const modelTarget = join(root, 'public', 'models', 'depth-anything-v2-small');

for (const file of ['config.json', 'preprocessor_config.json', 'onnx/model_quantized.onnx']) {
    const target = join(modelTarget, file);

    if (existsSync(target)) {
        continue;
    }

    mkdirSync(dirname(target), { recursive: true });
    const response = await fetch(`${repo}/${file}`);

    if (!response.ok) {
        console.error(`No se pudo descargar ${file} (${response.status}).`);
        process.exit(1);
    }

    writeFileSync(target, Buffer.from(await response.arrayBuffer()));
    console.log(`Descargado ${file}`);
}

console.log('Modelo de profundidad y runtime ONNX listos.');
