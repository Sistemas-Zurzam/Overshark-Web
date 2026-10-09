// Probador virtual local: todo ocurre en el navegador del cliente, sin servicios externos.
// 1. MediaPipe Pose Landmarker detecta hombros, codos, cadera y tobillos en la foto.
// 2. MediaPipe Image Segmenter separa a la persona del fondo.
// 3. Cada prenda (PNG con fondo transparente o ilustración) se deforma con una malla de triángulos
//    para que siga el cuerpo, se recorta a la silueta y toma las sombras de la foto.
// Es una simulación aproximada: no reproduce pliegues ni caída real de la tela.

import { FilesetResolver, ImageSegmenter, PoseLandmarker } from '@mediapipe/tasks-vision';

const WASM_PATH = '/vendor/mediapipe/wasm';
const POSE_MODEL = '/models/mediapipe/pose_landmarker_full.task';
const SEGMENT_MODEL = '/models/mediapipe/selfie_segmenter.tflite';
const MAX_SIDE = 1024;
const GRID = 26;

const LM = {
    nose: 0, earA: 7, earB: 8,
    shoulderA: 11, shoulderB: 12, elbowA: 13, elbowB: 14,
    hipA: 23, hipB: 24, kneeA: 25, kneeB: 26, ankleA: 27, ankleB: 28,
};

let enginePromise = null;

export function loadFittingEngine() {
    enginePromise ??= (async () => {
        const fileset = await FilesetResolver.forVisionTasks(WASM_PATH);
        const [pose, segmenter] = await Promise.all([
            PoseLandmarker.createFromOptions(fileset, {
                baseOptions: { modelAssetPath: POSE_MODEL, delegate: 'CPU' },
                runningMode: 'IMAGE',
                numPoses: 1,
            }),
            ImageSegmenter.createFromOptions(fileset, {
                baseOptions: { modelAssetPath: SEGMENT_MODEL, delegate: 'CPU' },
                runningMode: 'IMAGE',
                outputConfidenceMasks: true,
                outputCategoryMask: false,
            }),
        ]);

        return { pose, segmenter };
    })();

    enginePromise.catch(() => {
        enginePromise = null;
    });

    return enginePromise;
}

export function loadImage(src) {
    return new Promise((resolve, reject) => {
        const image = new Image();
        image.decoding = 'async';
        image.onload = () => resolve(image);
        image.onerror = () => reject(new Error('No se pudo cargar la imagen.'));
        image.src = src;
    });
}

/**
 * Analiza la foto: escala, pose y máscara de la persona. Devuelve null si no hay un cuerpo utilizable.
 */
export async function analyzePerson(engine, image) {
    const scale = Math.min(1, MAX_SIDE / Math.max(image.naturalWidth, image.naturalHeight));
    const width = Math.round(image.naturalWidth * scale);
    const height = Math.round(image.naturalHeight * scale);
    const base = document.createElement('canvas');
    base.width = width;
    base.height = height;
    base.getContext('2d').drawImage(image, 0, 0, width, height);

    // Se analiza la imagen original: con el canvas reducido la pose sale desplazada (codos dentro del torso).
    const poseResult = engine.pose.detect(image);
    const landmarks = poseResult.landmarks?.[0];

    if (!landmarks) {
        return null;
    }

    const point = (index) => ({ x: landmarks[index].x * width, y: landmarks[index].y * height, v: landmarks[index].visibility ?? 1 });
    const pair = (a, b) => [point(a), point(b)].sort((p, q) => p.x - q.x);
    const [shoulderL, shoulderR] = pair(LM.shoulderA, LM.shoulderB);

    if (shoulderL.v < 0.4 || shoulderR.v < 0.4 || distance(shoulderL, shoulderR) < width * 0.08) {
        return null;
    }

    const elbows = pair(LM.elbowA, LM.elbowB);
    const [hipL, hipR] = pair(LM.hipA, LM.hipB);
    const [kneeL, kneeR] = pair(LM.kneeA, LM.kneeB);
    const [ankleL, ankleR] = pair(LM.ankleA, LM.ankleB);

    const segmentResult = engine.segmenter.segment(image);
    const masks = segmentResult.confidenceMasks ?? [];
    const personMask = masks.length > 1 ? masks[masks.length - 1] : masks[0];
    const maskCanvas = personMask ? maskToCanvas(personMask) : null;
    masks.forEach((mask) => mask.close());

    return {
        base,
        width,
        height,
        maskCanvas,
        body: {
            shoulderL, shoulderR, elbowL: elbows[0], elbowR: elbows[1], hipL, hipR, kneeL, kneeR, ankleL, ankleR,
            nose: point(LM.nose), ears: pair(LM.earA, LM.earB),
            // De frente, el hombro izquierdo de la persona (11) queda a la derecha de la imagen; de espaldas, al revés.
            facingCamera: landmarks[LM.shoulderA].x > landmarks[LM.shoulderB].x,
        },
    };
}

/**
 * Dibuja la foto con las prendas: inferior primero y superior encima.
 *
 * @param garments [{ slot: 'top'|'bottom', image, anchors }]
 */
export function renderOutfit(canvas, analysis, garments) {
    const { base, width, height, maskCanvas, body } = analysis;
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(base, 0, 0);

    const ordered = [...garments].sort((a, b) => (a.slot === 'bottom' ? -1 : 1) - (b.slot === 'bottom' ? -1 : 1));

    if (!ordered.length) {
        return;
    }

    const layer = document.createElement('canvas');
    layer.width = width;
    layer.height = height;
    const layerCtx = layer.getContext('2d');

    for (const garment of ordered) {
        const mapper = garment.slot === 'bottom'
            ? bottomMapper(garment.anchors, body)
            : topMapper(garment.anchors, body);
        // Se deforma en una capa propia y se pinta dos veces: así las uniones semitransparentes
        // entre triángulos quedan opacas y no asoma la ropa original en franjas.
        const warped = document.createElement('canvas');
        warped.width = width;
        warped.height = height;
        warpImage(warped.getContext('2d'), garment.image, garment.anchors.bounds, mapper);
        layerCtx.drawImage(warped, 0, 0);
        layerCtx.drawImage(warped, 0, 0);
    }

    // Recorta a la silueta (con un margen holgado), solo si la segmentación cubre bien el cuerpo:
    // con maniquíes, ropa del color del fondo o mala luz, la máscara puede perder medio torso.
    if (maskCanvas) {
        const silhouette = document.createElement('canvas');
        silhouette.width = width;
        silhouette.height = height;
        const sctx = silhouette.getContext('2d', { willReadFrequently: true });
        sctx.drawImage(maskCanvas, 0, 0, width, height);

        if (maskCoversBody(sctx, body, width, height)) {
            // Dilata la silueta: desenfoca y vuelve a superponer para engrosar el borde.
            const dilated = document.createElement('canvas');
            dilated.width = width;
            dilated.height = height;
            const dctx = dilated.getContext('2d');
            dctx.filter = `blur(${Math.max(3, Math.round(width / 90))}px)`;
            for (let i = 0; i < 3; i += 1) {
                dctx.drawImage(silhouette, 0, 0);
            }
            dctx.filter = 'none';
            dctx.drawImage(silhouette, 0, 0);

            layerCtx.globalCompositeOperation = 'destination-in';
            layerCtx.drawImage(dilated, 0, 0);
            layerCtx.globalCompositeOperation = 'source-over';
        }
    }

    shadeWithPhoto(layer, base);
    ctx.drawImage(layer, 0, 0);
    restoreHead(ctx, base, body);
}

/**
 * Silueta de la persona al tamaño de la foto, o null si la segmentación no cubre bien el cuerpo.
 */
export function reliablePersonMask(analysis) {
    const { maskCanvas, body, width, height } = analysis;

    if (!maskCanvas) {
        return null;
    }

    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    ctx.filter = `blur(${Math.max(1, Math.round(width / 300))}px)`;
    ctx.drawImage(maskCanvas, 0, 0, width, height);

    return maskCoversBody(ctx, body, width, height) ? canvas : null;
}

// ---- Anclajes de la prenda ----

/**
 * Puntos de referencia de una prenda en su propia imagen.
 * - Superiores: costuras de los hombros (tl, tr), bajo (bl, br) y dirección de las mangas.
 * - Inferiores: cintura (tl, tr) y bajo de cada pierna (legL, legR).
 * Para PNG subidos se estiman a partir del área no transparente.
 */
export function anchorsFromAlpha(image, slot) {
    const bounds = alphaBounds(image);
    const { x, y, w, h } = bounds;

    if (slot === 'bottom') {
        return {
            bounds,
            tl: { x: x + w * 0.04, y }, tr: { x: x + w * 0.96, y },
            legL: [{ x, y: y + h }, { x: x + w * 0.46, y: y + h }],
            legR: [{ x: x + w * 0.54, y: y + h }, { x: x + w, y: y + h }],
        };
    }

    return {
        bounds,
        tl: { x: x + w * 0.27, y: y + h * 0.07 }, tr: { x: x + w * 0.73, y: y + h * 0.07 },
        bl: { x: x + w * 0.26, y: y + h }, br: { x: x + w * 0.74, y: y + h },
        sleeveL: { x: -1, y: 0.9 }, sleeveR: { x: 1, y: 0.9 },
    };
}

// Ilustraciones del catálogo (viewBox 120×120, ver components/storefront/garment.blade.php).
const ILLUSTRATION_ANCHORS = {
    polo: {
        tl: { x: 38, y: 22 }, tr: { x: 82, y: 22 }, bl: { x: 36, y: 105 }, br: { x: 84, y: 105 },
        sleeveL: { x: -15, y: 14 }, sleeveR: { x: 15, y: 14 },
    },
    camisa: {
        tl: { x: 40, y: 19 }, tr: { x: 80, y: 19 }, bl: { x: 34, y: 108 }, br: { x: 86, y: 108 },
        sleeveL: { x: -6, y: 26 }, sleeveR: { x: 6, y: 26 },
    },
    pantalon: {
        tl: { x: 36, y: 12 }, tr: { x: 84, y: 12 },
        legL: [{ x: 29, y: 108 }, { x: 53, y: 108 }], legR: [{ x: 67, y: 108 }, { x: 91, y: 108 }],
    },
};

export function anchorsForIllustration(type, size) {
    const source = ILLUSTRATION_ANCHORS[type];

    if (!source) {
        return null;
    }

    const k = size / 120;
    const scalePoint = (p) => ({ x: p.x * k, y: p.y * k });
    const scaled = { bounds: { x: 0, y: 0, w: size, h: size } };

    for (const [key, value] of Object.entries(source)) {
        if (key.startsWith('sleeve')) {
            scaled[key] = value;
        } else if (Array.isArray(value)) {
            scaled[key] = value.map(scalePoint);
        } else {
            scaled[key] = scalePoint(value);
        }
    }

    return scaled;
}

// ---- Mapeos prenda → cuerpo ----

function topMapper(a, body) {
    const { shoulderL, shoulderR, elbowL, elbowR, hipL, hipR } = body;
    const shoulderW = distance(shoulderL, shoulderR);
    const across = unit(sub(shoulderR, shoulderL));
    const down = { x: -across.y, y: across.x };
    const hipMid = mid(hipL, hipR);
    const shoulderMid = mid(shoulderL, shoulderR);
    const torso = Math.max(distance(shoulderMid, hipMid), shoulderW * 0.9);
    const hemW = Math.max(distance(hipL, hipR) * 1.55, shoulderW * 1.02);
    const hemCenter = add(hipMid, scale(down, torso * 0.1));

    const target = {
        tl: add(add(shoulderL, scale(across, -shoulderW * 0.2)), scale(down, -shoulderW * 0.13)),
        tr: add(add(shoulderR, scale(across, shoulderW * 0.2)), scale(down, -shoulderW * 0.13)),
        bl: add(hemCenter, scale(across, -hemW / 2)),
        br: add(hemCenter, scale(across, hemW / 2)),
    };

    const sourceW = distance(a.tl, a.tr);
    // Las mangas se agrandan un 30 %: así cubren la manga de la ropa original.
    const k = (distance(target.tl, target.tr) / sourceW) * 1.3;
    const sleeve = (sourcePoint, origin, targetOrigin, sourceDir, elbow) => {
        const armDir = elbow ? unit(sub(elbow, targetOrigin)) : unit(add(down, scale(sub(targetOrigin, shoulderMid), 0.002)));
        const angle = Math.atan2(armDir.y, armDir.x) - Math.atan2(sourceDir.y, sourceDir.x);
        return add(targetOrigin, rotate(scale(sub(sourcePoint, origin), k), angle));
    };

    return (p) => {
        const { u, v } = quadCoords(p, a.tl, a.tr, a.bl, a.br);
        const flat = bilinear(target, u, v);

        if (u >= 0 && u <= 1) {
            return flat;
        }

        // Mangas: giran alrededor del hombro para seguir la dirección del brazo.
        const left = u < 0;
        const sleevePoint = left
            ? sleeve(p, a.tl, target.tl, a.sleeveL, elbowL.v > 0.3 ? elbowL : null)
            : sleeve(p, a.tr, target.tr, a.sleeveR, elbowR.v > 0.3 ? elbowR : null);
        const t = clamp((left ? -u : u - 1) / 0.12, 0, 1) * clamp(1.25 - v, 0, 1);

        return lerpPoint(flat, sleevePoint, t);
    };
}

function bottomMapper(a, body) {
    const { hipL, hipR, kneeL, kneeR, ankleL, ankleR, shoulderL, shoulderR } = body;
    const hipW = Math.max(distance(hipL, hipR), distance(shoulderL, shoulderR) * 0.55);
    const across = unit(sub(hipR, hipL));
    const down = { x: -across.y, y: across.x };
    const hipMid = mid(hipL, hipR);
    const thigh = Math.max(distance(mid(hipL, hipR), mid(kneeL, kneeR)), hipW * 1.4);
    const waistW = hipW * 2.05;
    const waistCenter = add(hipMid, scale(down, -thigh * 0.12));
    const waistL = add(waistCenter, scale(across, -waistW / 2));
    const waistR = add(waistCenter, scale(across, waistW / 2));

    const legEnd = (hip, knee, ankle) => (ankle.v > 0.3 ? ankle : add(knee, sub(knee, hip)));
    const sourceWaistW = distance(a.tl, a.tr);
    const k = waistW / sourceWaistW;
    const legQuad = (sourceLeg, hip, knee, ankle) => {
        const end = legEnd(hip, knee, ankle);
        const legAcross = unit({ x: -(end.y - hip.y), y: end.x - hip.x });
        const hemW = distance(sourceLeg[0], sourceLeg[1]) * k * 1.5;
        const sign = legAcross.x < 0 ? -1 : 1;
        const hemDown = add(end, scale(unit(sub(end, knee)), hemW * 0.15));
        return [add(hemDown, scale(legAcross, -sign * hemW / 2)), add(hemDown, scale(legAcross, sign * hemW / 2))];
    };

    const [hemLL, hemLR] = legQuad(a.legL, hipL, kneeL, ankleL);
    const [hemRL, hemRR] = legQuad(a.legR, hipR, kneeR, ankleR);
    const sourceCenterTop = mid(a.tl, a.tr);
    const leftTarget = { tl: waistL, tr: waistCenter, bl: hemLL, br: hemLR };
    const rightTarget = { tl: waistCenter, tr: waistR, bl: hemRL, br: hemRR };

    return (p) => {
        if (p.x < sourceCenterTop.x) {
            const { u, v } = quadCoords(p, a.tl, sourceCenterTop, a.legL[0], a.legL[1]);
            return bilinear(leftTarget, u, v);
        }

        const { u, v } = quadCoords(p, sourceCenterTop, a.tr, a.legR[0], a.legR[1]);
        return bilinear(rightTarget, u, v);
    };
}

// ---- Deformación por malla ----

function warpImage(ctx, image, bounds, mapper) {
    const cols = GRID;
    const rows = GRID;
    const points = [];

    for (let r = 0; r <= rows; r += 1) {
        const row = [];
        for (let c = 0; c <= cols; c += 1) {
            const s = { x: bounds.x + (bounds.w * c) / cols, y: bounds.y + (bounds.h * r) / rows };
            row.push({ s, d: mapper(s) });
        }
        points.push(row);
    }

    for (let r = 0; r < rows; r += 1) {
        for (let c = 0; c < cols; c += 1) {
            const p00 = points[r][c];
            const p10 = points[r][c + 1];
            const p01 = points[r + 1][c];
            const p11 = points[r + 1][c + 1];
            drawTriangle(ctx, image, p00, p10, p11);
            drawTriangle(ctx, image, p00, p11, p01);
        }
    }
}

function drawTriangle(ctx, image, p0, p1, p2) {
    const { s: s0, d: d0 } = p0;
    const { s: s1, d: d1 } = p1;
    const { s: s2, d: d2 } = p2;
    const det = (s1.x - s0.x) * (s2.y - s0.y) - (s2.x - s0.x) * (s1.y - s0.y);

    if (Math.abs(det) < 1e-6) {
        return;
    }

    // Transformación afín que lleva el triángulo de origen al de destino.
    const a = ((d1.x - d0.x) * (s2.y - s0.y) - (d2.x - d0.x) * (s1.y - s0.y)) / det;
    const b = ((d1.y - d0.y) * (s2.y - s0.y) - (d2.y - d0.y) * (s1.y - s0.y)) / det;
    const c = ((d2.x - d0.x) * (s1.x - s0.x) - (d1.x - d0.x) * (s2.x - s0.x)) / det;
    const d = ((d2.y - d0.y) * (s1.x - s0.x) - (d1.y - d0.y) * (s2.x - s0.x)) / det;
    const e = d0.x - a * s0.x - c * s0.y;
    const f = d0.y - b * s0.x - d * s0.y;

    // Se agranda medio píxel para que no se vean costuras entre triángulos.
    const centroid = mid3(d0, d1, d2);
    const grow = (p) => add(p, scale(unit(sub(p, centroid)), 1.2));
    const g0 = grow(d0);
    const g1 = grow(d1);
    const g2 = grow(d2);

    ctx.save();
    ctx.beginPath();
    ctx.moveTo(g0.x, g0.y);
    ctx.lineTo(g1.x, g1.y);
    ctx.lineTo(g2.x, g2.y);
    ctx.closePath();
    ctx.clip();
    ctx.setTransform(a, b, c, d, e, f);
    ctx.drawImage(image, 0, 0);
    ctx.restore();
}

// ---- Acabado ----

function shadeWithPhoto(layer, base) {
    const alpha = document.createElement('canvas');
    alpha.width = layer.width;
    alpha.height = layer.height;
    alpha.getContext('2d').drawImage(layer, 0, 0);

    const ctx = layer.getContext('2d');
    ctx.save();
    ctx.globalCompositeOperation = 'multiply';
    ctx.globalAlpha = 0.5;
    ctx.filter = 'grayscale(1) brightness(1.35) contrast(1.15)';
    ctx.drawImage(base, 0, 0);
    ctx.restore();

    ctx.save();
    ctx.globalCompositeOperation = 'destination-in';
    ctx.drawImage(alpha, 0, 0);
    ctx.restore();
}

/** Vuelve a pintar la cabeza encima para que el cuello de la prenda no tape la barbilla. */
function restoreHead(ctx, base, body) {
    const [earL, earR] = body.ears;
    const earW = Math.max(distance(earL, earR), distance(body.shoulderL, body.shoulderR) * 0.32);
    const center = { x: (earL.x + earR.x) / 2, y: Math.min(earL.y, earR.y, body.nose.y) };

    ctx.save();
    ctx.beginPath();
    ctx.ellipse(center.x, center.y + earW * 0.05, earW * 0.62, earW * 0.9, 0, 0, Math.PI * 2);
    ctx.clip();
    ctx.drawImage(base, 0, 0);
    ctx.restore();
}

/**
 * Comprueba que la máscara marque como "persona" el torso y la parte alta de los brazos
 * (puntos interiores entre hombros, cadera y codos). Si falla en muchos, no se usa.
 */
function maskCoversBody(ctx, body, width, height) {
    const { data } = ctx.getImageData(0, 0, width, height);
    const { shoulderL, shoulderR, hipL, hipR, elbowL, elbowR } = body;
    const samples = [];

    for (const u of [0.2, 0.35, 0.5, 0.65, 0.8]) {
        for (const v of [0.15, 0.35, 0.55, 0.75]) {
            samples.push(lerpPoint(lerpPoint(shoulderL, shoulderR, u), lerpPoint(hipL, hipR, u), v));
        }
    }

    for (const [shoulder, elbow] of [[shoulderL, elbowL], [shoulderR, elbowR]]) {
        if (elbow.v > 0.4) {
            samples.push(lerpPoint(shoulder, elbow, 0.35), lerpPoint(shoulder, elbow, 0.6));
        }
    }

    const inside = samples.filter((p) => {
        const x = Math.round(p.x);
        const y = Math.round(p.y);
        return x >= 0 && y >= 0 && x < width && y < height && data[(y * width + x) * 4 + 3] > 110;
    });

    return inside.length / samples.length >= 0.85;
}

function maskToCanvas(mask) {
    const width = mask.width;
    const height = mask.height;
    const values = mask.getAsFloat32Array();
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');
    const data = ctx.createImageData(width, height);

    for (let i = 0; i < values.length; i += 1) {
        const alpha = clamp((values[i] - 0.15) * 1.8, 0, 1) * 255;
        data.data[i * 4 + 3] = alpha;
    }

    ctx.putImageData(data, 0, 0);
    return canvas;
}

function alphaBounds(image) {
    const canvas = document.createElement('canvas');
    const width = image.naturalWidth || image.width;
    const height = image.naturalHeight || image.height;
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    ctx.drawImage(image, 0, 0);
    const { data } = ctx.getImageData(0, 0, width, height);
    let minX = width;
    let minY = height;
    let maxX = 0;
    let maxY = 0;

    for (let y = 0; y < height; y += 2) {
        for (let x = 0; x < width; x += 2) {
            if (data[(y * width + x) * 4 + 3] > 24) {
                minX = Math.min(minX, x);
                maxX = Math.max(maxX, x);
                minY = Math.min(minY, y);
                maxY = Math.max(maxY, y);
            }
        }
    }

    return maxX > minX ? { x: minX, y: minY, w: maxX - minX, h: maxY - minY } : { x: 0, y: 0, w: width, h: height };
}

// ---- Geometría ----

function quadCoords(p, tl, tr, bl, br) {
    const top = (tl.y + tr.y) / 2;
    const bottom = (bl.y + br.y) / 2;
    const v = (p.y - top) / (bottom - top || 1);
    const left = tl.x + (bl.x - tl.x) * v;
    const right = tr.x + (br.x - tr.x) * v;
    return { u: (p.x - left) / (right - left || 1), v };
}

function bilinear(q, u, v) {
    const top = lerpPoint(q.tl, q.tr, u);
    const bottom = lerpPoint(q.bl, q.br, u);
    return lerpPoint(top, bottom, v);
}

const add = (p, q) => ({ x: p.x + q.x, y: p.y + q.y });
const sub = (p, q) => ({ x: p.x - q.x, y: p.y - q.y });
const scale = (p, k) => ({ x: p.x * k, y: p.y * k });
const mid = (p, q) => ({ x: (p.x + q.x) / 2, y: (p.y + q.y) / 2 });
const mid3 = (p, q, r) => ({ x: (p.x + q.x + r.x) / 3, y: (p.y + q.y + r.y) / 3 });
const distance = (p, q) => Math.hypot(p.x - q.x, p.y - q.y);
const unit = (p) => {
    const length = Math.hypot(p.x, p.y) || 1;
    return { x: p.x / length, y: p.y / length };
};
const rotate = (p, angle) => ({ x: p.x * Math.cos(angle) - p.y * Math.sin(angle), y: p.x * Math.sin(angle) + p.y * Math.cos(angle) });
const lerpPoint = (p, q, t) => ({ x: p.x + (q.x - p.x) * t, y: p.y + (q.y - p.y) * t });
const clamp = (value, min, max) => Math.min(max, Math.max(min, value));
