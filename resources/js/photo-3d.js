// Vista 3D de la foto del cliente (con la prenda puesta), hecha en su navegador y sin costo:
// 1. Depth Anything V2 Small (Apache 2.0) vía transformers.js estima la profundidad de cada píxel.
// 2. La foto se convierte en una malla en relieve (Three.js) recortada a la silueta de la persona.
// 3. Gira ±30° (con allowFullTurn, 360° con una espalda simulada a partir de la silueta; desactivado
//    porque de costado la figura se ve deformada: una foto no contiene el perfil ni la espalda).

import * as THREE from 'three';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';

const MAX_AZIMUTH = THREE.MathUtils.degToRad(30);
const RELIEF = 0.16; // profundidad máxima del relieve, relativa al alto de la foto
const SEGMENTS = 220;

let estimatorPromise = null;

async function depthEstimator() {
    estimatorPromise ??= (async () => {
        const { pipeline, env } = await import('@huggingface/transformers');
        // Todo se sirve desde la tienda: modelo en /models y runtime ONNX en /vendor/onnx.
        env.allowRemoteModels = false;
        env.allowLocalModels = true;
        env.localModelPath = '/models/';
        env.backends.onnx.wasm.wasmPaths = '/vendor/onnx/';

        return pipeline('depth-estimation', 'depth-anything-v2-small', { dtype: 'q8', device: 'wasm' });
    })();

    estimatorPromise.catch(() => {
        estimatorPromise = null;
    });

    return estimatorPromise;
}

/**
 * Mapa de profundidad (0–255, más claro = más cerca) del tamaño de `canvas`, como canvas en escala de grises.
 */
export async function estimateDepth(canvas) {
    const { RawImage } = await import('@huggingface/transformers');
    const estimator = await depthEstimator();
    const { depth } = await estimator(RawImage.fromCanvas(canvas));
    const resized = await depth.resize(canvas.width, canvas.height);

    const out = document.createElement('canvas');
    out.width = canvas.width;
    out.height = canvas.height;
    const ctx = out.getContext('2d');
    const image = ctx.createImageData(out.width, out.height);

    for (let i = 0; i < resized.data.length; i += 1) {
        const value = resized.data[i * resized.channels];
        image.data[i * 4] = value;
        image.data[i * 4 + 1] = value;
        image.data[i * 4 + 2] = value;
        image.data[i * 4 + 3] = 255;
    }

    ctx.putImageData(image, 0, 0);
    return out;
}

/**
 * @param container elemento donde se monta el canvas WebGL
 * @param photo canvas con la foto (y las prendas) que se mostrará como textura
 * @param depthCanvas mapa de profundidad del mismo tamaño
 * @param maskCanvas silueta de la persona (alfa) o null para mostrar la foto completa
 *
 * Con allowFullTurn y silueta, gira 360°: el frente es la foto y la espalda es el mismo volumen reflejado,
 * pintado con los colores de la foto muy difuminados (silueta y color de la prenda, sin detalles inventados).
 * Por defecto (o sin silueta fiable) se limita a ±30°: más allá la figura se ve deformada.
 */
export function createPhoto3dViewer(container, { photo, depthCanvas, maskCanvas, reducedMotion = false, allowFullTurn = false }) {
    const width = photo.width;
    const height = photo.height;
    const aspect = width / height;
    // La vuelta completa (espalda simulada) queda desactivada por defecto: de costado se ve deformada.
    const fullTurn = allowFullTurn && Boolean(maskCanvas);

    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.domElement.className = 'h-full w-full touch-none';
    renderer.domElement.setAttribute('role', 'img');
    renderer.domElement.setAttribute('aria-label', 'Tu foto en 3D con el outfit; arrástrala para girarla');
    container.append(renderer.domElement);

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(30, 1, 0.05, 20);
    // Margen extra para que la figura entre completa (con la cabeza y los pies) incluso girada.
    const distance = 0.5 / Math.tan(THREE.MathUtils.degToRad(15)) * 1.42;
    camera.position.set(0, 0, distance);

    scene.add(new THREE.HemisphereLight(0xe6eeff, 0x0b1630, 1.4));
    const key = new THREE.DirectionalLight(0xffffff, 1.3);
    key.position.set(1.5, 2, -2.5);
    scene.add(key);

    // En la vuelta completa el frente usa la foto con el fondo sustituido por los colores de la persona,
    // para que el contorno no muestre una línea blanca del fondo al verlo de costado.
    const frontCanvas = document.createElement('canvas');
    const texture = new THREE.CanvasTexture(fullTurn ? frontCanvas : photo);
    texture.colorSpace = THREE.SRGBColorSpace;
    const alphaTexture = maskCanvas ? new THREE.CanvasTexture(alphaToGray(maskCanvas, width, height)) : null;

    const geometry = reliefGeometry(aspect, readDepth(depthCanvas, maskCanvas, width, height), Boolean(maskCanvas));
    const material = new THREE.MeshBasicMaterial({
        map: texture,
        alphaMap: alphaTexture,
        alphaTest: alphaTexture ? 0.78 : 0,
        side: THREE.FrontSide,
    });
    const figure = new THREE.Group();
    figure.add(new THREE.Mesh(geometry, material));

    // Espalda: el mismo relieve reflejado hacia atrás (la escala negativa invierte las caras).
    const backCanvas = document.createElement('canvas');
    const backTexture = new THREE.CanvasTexture(backCanvas);
    backTexture.colorSpace = THREE.SRGBColorSpace;
    const backMaterial = new THREE.MeshLambertMaterial({ map: backTexture, alphaMap: alphaTexture, alphaTest: 0.78 });
    const paintBack = () => {
        // Solo los píxeles de la persona (sin el fondo de la foto), difuminados sobre su color medio,
        // para que el borde de la espalda no se aclare con el fondo.
        const person = document.createElement('canvas');
        person.width = width;
        person.height = height;
        const pctx = person.getContext('2d', { willReadFrequently: true });
        pctx.drawImage(photo, 0, 0);
        pctx.globalCompositeOperation = 'destination-in';
        pctx.drawImage(maskCanvas, 0, 0, width, height);

        const sample = document.createElement('canvas');
        sample.width = 24;
        sample.height = 36;
        const sctx = sample.getContext('2d', { willReadFrequently: true });
        sctx.drawImage(person, 0, 0, 24, 36);
        const pixels = sctx.getImageData(0, 0, 24, 36).data;
        let r = 0;
        let g = 0;
        let b = 0;
        let weight = 0;
        for (let i = 0; i < pixels.length; i += 4) {
            const a = pixels[i + 3] / 255;
            r += pixels[i] * a;
            g += pixels[i + 1] * a;
            b += pixels[i + 2] * a;
            weight += a;
        }
        weight ||= 1;

        backCanvas.width = width;
        backCanvas.height = height;
        const ctx = backCanvas.getContext('2d');
        ctx.fillStyle = `rgb(${Math.round(r / weight)} ${Math.round(g / weight)} ${Math.round(b / weight)})`;
        ctx.fillRect(0, 0, width, height);
        ctx.filter = `blur(${Math.max(8, Math.round(width / 28))}px) brightness(0.92)`;
        ctx.drawImage(person, 0, 0);
        ctx.filter = 'none';
        backTexture.needsUpdate = true;

        frontCanvas.width = width;
        frontCanvas.height = height;
        const fctx = frontCanvas.getContext('2d');
        fctx.drawImage(backCanvas, 0, 0);
        fctx.drawImage(person, 0, 0);
        texture.needsUpdate = true;
    };

    if (fullTurn) {
        paintBack();
        const back = new THREE.Mesh(geometry, backMaterial);
        back.scale.z = -0.85;
        figure.add(back);
    }

    scene.add(figure);
    scene.add(platformRing(aspect));
    // La figura sube un poco para dejar sitio al aro y que los pies no queden en el borde.
    scene.position.y = 0.04;

    const controls = new OrbitControls(camera, renderer.domElement);
    controls.enablePan = false;
    controls.minDistance = distance * 0.7;
    controls.maxDistance = distance * 1.3;
    if (!fullTurn) {
        controls.minAzimuthAngle = -MAX_AZIMUTH;
        controls.maxAzimuthAngle = MAX_AZIMUTH;
    }
    controls.minPolarAngle = Math.PI / 2 - 0.25;
    controls.maxPolarAngle = Math.PI / 2 + 0.2;
    controls.enableDamping = !reducedMotion;
    controls.update();

    let sway = !reducedMotion;
    let swayPhase = 0;
    let dragging = false;
    controls.addEventListener('start', () => {
        dragging = true;
    });
    controls.addEventListener('end', () => {
        dragging = false;
    });

    const resize = () => {
        const { clientWidth: w, clientHeight: h } = container;
        if (w && h) {
            renderer.setSize(w, h, false);
            camera.aspect = w / h;
            // Si el panel es más angosto que la foto, se aleja la cámara para que entre completa.
            camera.position.setLength(distance * Math.max(1, aspect / camera.aspect));
            camera.updateProjectionMatrix();
        }
    };
    const observer = new ResizeObserver(resize);
    observer.observe(container);
    resize();

    const azimuth = () => Math.atan2(camera.position.x, camera.position.z);
    const setAzimuth = (angle) => {
        const radius = Math.hypot(camera.position.x, camera.position.z);
        const clamped = fullTurn ? angle : THREE.MathUtils.clamp(angle, -MAX_AZIMUTH, MAX_AZIMUTH);
        camera.position.x = Math.sin(clamped) * radius;
        camera.position.z = Math.cos(clamped) * radius;
        camera.lookAt(controls.target);
    };

    let last = performance.now();
    let frame = 0;
    const loop = (now) => {
        frame = requestAnimationFrame(loop);
        const dt = Math.min(0.05, (now - last) / 1000);
        last = now;

        if (sway && !dragging) {
            if (fullTurn) {
                // Vuelta completa continua.
                setAzimuth(azimuth() + dt * 0.6);
            } else {
                // Balanceo suave de lado a lado dentro del rango disponible.
                swayPhase += dt * 0.7;
                setAzimuth(Math.sin(swayPhase) * MAX_AZIMUTH * 0.9);
            }
        }

        controls.update();
        renderer.render(scene, camera);
    };
    frame = requestAnimationFrame(loop);

    return {
        fullTurn,
        updatePhoto() {
            texture.needsUpdate = true;
            if (fullTurn) {
                paintBack();
            }
        },
        rotateBy(radians) {
            sway = false;
            setAzimuth(azimuth() + radians);
            controls.update();
        },
        setAutoRotate(enabled) {
            sway = enabled;
            if (!fullTurn) {
                swayPhase = Math.asin(THREE.MathUtils.clamp(azimuth() / (MAX_AZIMUTH * 0.9), -1, 1));
            }
        },
        get autoRotate() {
            return sway;
        },
        dispose() {
            cancelAnimationFrame(frame);
            observer.disconnect();
            controls.dispose();
            geometry.dispose();
            material.dispose();
            backMaterial.dispose();
            texture.dispose();
            backTexture.dispose();
            alphaTexture?.dispose();
            renderer.dispose();
            renderer.domElement.remove();
        },
    };
}

/**
 * Profundidad normalizada (0–1) por píxel, suavizada y escalada solo con los valores de la persona.
 */
function readDepth(depthCanvas, maskCanvas, width, height) {
    const smooth = document.createElement('canvas');
    smooth.width = width;
    smooth.height = height;
    const sctx = smooth.getContext('2d', { willReadFrequently: true });
    sctx.filter = `blur(${Math.max(2, Math.round(width / 90))}px)`;
    sctx.drawImage(depthCanvas, 0, 0, width, height);
    const depth = sctx.getImageData(0, 0, width, height).data;

    let mask = null;
    if (maskCanvas) {
        const mctx = document.createElement('canvas').getContext('2d', { willReadFrequently: true });
        mctx.canvas.width = width;
        mctx.canvas.height = height;
        mctx.drawImage(maskCanvas, 0, 0, width, height);
        mask = mctx.getImageData(0, 0, width, height).data;
    }

    const values = [];
    for (let i = 0; i < width * height; i += 7) {
        if (!mask || mask[i * 4 + 3] > 127) {
            values.push(depth[i * 4]);
        }
    }
    values.sort((a, b) => a - b);
    const low = values[Math.floor(values.length * 0.03)] ?? 0;
    const high = values[Math.floor(values.length * 0.98)] ?? 255;
    const range = Math.max(1, high - low);

    const index = (u, v) => {
        const x = Math.min(width - 1, Math.max(0, Math.round(u * (width - 1))));
        const y = Math.min(height - 1, Math.max(0, Math.round(v * (height - 1))));
        return y * width + x;
    };

    return {
        width,
        height,
        at(u, v) {
            return Math.min(1, Math.max(0, (depth[index(u, v) * 4] - low) / range));
        },
        inside(u, v) {
            return !mask || mask[index(u, v) * 4 + 3] > 90;
        },
    };
}

/**
 * Malla en relieve. Con silueta, el volumen sale sobre todo de la distancia al borde (como inflar la
 * figura: el centro del cuerpo más cerca, los bordes curvados hacia atrás) y la IA de profundidad solo
 * aporta detalle; así brazos, piernas y cabeza quedan redondeados en vez de deformados por sus errores.
 */
function reliefGeometry(aspect, depth, hasMask) {
    const segX = SEGMENTS;
    const segY = Math.max(2, Math.round(SEGMENTS / aspect));
    const geometry = new THREE.PlaneGeometry(aspect, 1, segX, segY);
    const position = geometry.attributes.position;
    const uv = geometry.attributes.uv;
    const cols = segX + 1;
    const count = position.count;
    const neighbours = (i) => {
        const col = i % cols;
        const list = [];
        if (col > 0) list.push(i - 1);
        if (col < cols - 1) list.push(i + 1);
        if (i - cols >= 0) list.push(i - cols);
        if (i + cols < count) list.push(i + cols);
        return list;
    };

    const inside = new Uint8Array(count);
    const detail = new Float32Array(count);
    for (let i = 0; i < count; i += 1) {
        const u = uv.getX(i);
        const v = 1 - uv.getY(i);
        inside[i] = depth.inside(u, v) ? 1 : 0;
        detail[i] = depth.at(u, v);
    }

    // Distancia (en celdas) de cada punto de la persona al borde de la silueta.
    const edge = new Float32Array(count).fill(-1);
    let queue = [];
    for (let i = 0; i < count; i += 1) {
        if (!inside[i]) {
            edge[i] = 0;
            queue.push(i);
        }
    }
    while (queue.length) {
        const next = [];
        for (const i of queue) {
            for (const j of neighbours(i)) {
                if (edge[j] < 0) {
                    edge[j] = edge[i] + 1;
                    next.push(j);
                }
            }
        }
        queue = next;
    }
    let maxEdge = 1;
    for (let i = 0; i < count; i += 1) {
        maxEdge = Math.max(maxEdge, edge[i]);
    }

    let z = new Float32Array(count);
    const known = new Uint8Array(count);
    queue = [];
    for (let i = 0; i < count; i += 1) {
        if (!inside[i]) {
            continue;
        }
        // El volumen vale 0 justo en el contorno: así el frente y la espalda reflejada se cierran
        // en el borde y, de costado, la figura se ve como un cuerpo y no como dos láminas separadas.
        const volume = hasMask ? Math.sqrt(Math.max(0, edge[i] - 1) / maxEdge) : 0;
        const relief = Math.pow(detail[i], 1.4);
        z[i] = RELIEF * (hasMask ? volume * (0.82 + 0.18 * relief) : 0.6 * relief);
        known[i] = 1;
        queue.push(i);
    }

    // Fuera de la silueta se copia la profundidad del borde más cercano para que el contorno no se estire.
    while (queue.length) {
        const next = [];
        for (const i of queue) {
            for (const j of neighbours(i)) {
                if (!known[j]) {
                    z[j] = z[i];
                    known[j] = 1;
                    next.push(j);
                }
            }
        }
        queue = next;
    }

    // Suavizado: elimina escalones y bultos pequeños.
    for (let pass = 0; pass < 10; pass += 1) {
        const smoothed = new Float32Array(count);
        for (let i = 0; i < count; i += 1) {
            const around = neighbours(i);
            let sum = z[i] * 2;
            for (const j of around) {
                sum += z[j];
            }
            smoothed[i] = sum / (around.length + 2);
        }
        z = smoothed;
    }

    for (let i = 0; i < count; i += 1) {
        position.setZ(i, z[i]);
    }

    position.needsUpdate = true;
    geometry.computeVertexNormals();
    return geometry;
}

/** Three.js lee el alphaMap del canal verde: se pasa la máscara a gris. */
function alphaToGray(maskCanvas, width, height) {
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    ctx.drawImage(maskCanvas, 0, 0, width, height);
    const image = ctx.getImageData(0, 0, width, height);

    for (let i = 0; i < image.data.length; i += 4) {
        const alpha = image.data[i + 3];
        image.data[i] = alpha;
        image.data[i + 1] = alpha;
        image.data[i + 2] = alpha;
        image.data[i + 3] = 255;
    }

    ctx.putImageData(image, 0, 0);
    return canvas;
}

function platformRing(aspect) {
    const group = new THREE.Group();
    for (const [radius, opacity] of [[0.42, 0.95], [0.28, 0.55]]) {
        const ring = new THREE.Mesh(
            new THREE.TorusGeometry(radius * Math.min(1, aspect * 1.6), 0.006, 8, 96),
            new THREE.MeshBasicMaterial({ color: 0x3d8bff, transparent: true, opacity }),
        );
        ring.rotation.x = Math.PI / 2;
        ring.scale.set(1, 0.35, 1);
        ring.position.set(0, -0.49, RELIEF * 0.4);
        group.add(ring);
    }
    return group;
}
