// Giro 360° real del cliente a partir de un video corto girando sobre sí mismo.
// Todo ocurre en su navegador: los cuadros nunca se suben al servidor.
// - Se extraen FRAMES cuadros (de la cámara en vivo o de un video subido).
// - MediaPipe detecta en cuáles está de frente; solo en esos se superpone la prenda elegida
//   (de lado y de espaldas la detección no es fiable y se muestra la ropa con la que grabó).
// - El visor recorre los cuadros al arrastrar, con flechas o en reproducción automática.

export const FRAMES = 48;
const MAX_SIDE = 900;
const RECORD_MS = 8000;

/**
 * Cuadros de la cámara en vivo durante RECORD_MS, como blobs JPEG.
 */
export async function captureFromVideoElement(video, { onProgress, signal } = {}) {
    const frames = [];
    const interval = RECORD_MS / FRAMES;

    for (let i = 0; i < FRAMES; i += 1) {
        if (signal?.aborted) {
            throw new DOMException('Cancelado', 'AbortError');
        }
        frames.push(await grab(video));
        onProgress?.((i + 1) / FRAMES);
        await new Promise((resolve) => setTimeout(resolve, interval));
    }

    return frames;
}

/**
 * Cuadros repartidos a lo largo de un video subido por el cliente.
 */
export async function framesFromVideoFile(file, { onProgress } = {}) {
    const video = document.createElement('video');
    video.muted = true;
    video.playsInline = true;
    video.preload = 'auto';
    const url = URL.createObjectURL(file);
    video.src = url;

    try {
        await new Promise((resolve, reject) => {
            video.onloadeddata = resolve;
            video.onerror = () => reject(new Error('No pudimos leer el video.'));
        });

        const duration = video.duration;
        if (!Number.isFinite(duration) || duration < 2) {
            throw new Error('El video debe durar al menos 2 segundos.');
        }
        if (duration > 30) {
            throw new Error('El video no puede durar más de 30 segundos.');
        }

        const frames = [];
        for (let i = 0; i < FRAMES; i += 1) {
            video.currentTime = Math.min(duration - 0.05, (duration * i) / FRAMES);
            await new Promise((resolve) => {
                video.onseeked = resolve;
            });
            frames.push(await grab(video));
            onProgress?.((i + 1) / FRAMES);
        }

        return frames;
    } finally {
        URL.revokeObjectURL(url);
    }
}

async function grab(video) {
    const scale = Math.min(1, MAX_SIDE / Math.max(video.videoWidth, video.videoHeight));
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(video.videoWidth * scale);
    canvas.height = Math.round(video.videoHeight * scale);
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

    return new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.88));
}

/**
 * Analiza cada cuadro con el motor del probador y marca cuáles están de frente.
 *
 * @returns [{ blob, image, analysis, frontal }]
 */
export async function analyzeFrames(blobs, fittingModule, engine, { onProgress } = {}) {
    const frames = [];

    for (let i = 0; i < blobs.length; i += 1) {
        const url = URL.createObjectURL(blobs[i]);
        const image = await fittingModule.loadImage(url);
        let analysis = null;

        try {
            analysis = await fittingModule.analyzePerson(engine, image);
        } catch {
            analysis = null;
        }

        frames.push({ blob: blobs[i], url, image, analysis, frontal: false, score: 0 });
        onProgress?.((i + 1) / blobs.length);
    }

    // De frente: orientado hacia la cámara (orden de los hombros), cabeza entre los hombros y hombros
    // casi tan anchos como el máximo del giro. La nariz solo suma: con maniquíes o pelo largo puede no verse.
    const widths = frames.map(({ analysis }) => (analysis ? shoulderWidth(analysis.body) / analysis.width : 0));
    const maxWidth = Math.max(...widths, 0.0001);

    frames.forEach((frame, i) => {
        const body = frame.analysis?.body;
        if (!body) {
            return;
        }
        const between = body.nose.x > body.shoulderL.x && body.nose.x < body.shoulderR.x;
        const ratio = widths[i] / maxWidth;
        frame.frontal = body.facingCamera && between && ratio > 0.72;
        frame.score = frame.frontal ? ratio * (0.6 + 0.4 * body.nose.v) : 0;
    });

    return frames;
}

export function mostFrontalFrame(frames) {
    return frames.reduce((best, frame) => (frame.score > (best?.score ?? 0) ? frame : best), null);
}

/**
 * Visor: muestra el cuadro actual en un canvas y permite recorrer el giro.
 *
 * @param render (frame, canvas) => void  dibuja el cuadro (con o sin prenda)
 */
export function createTurntable(container, frames, render, { reducedMotion = false } = {}) {
    const canvas = document.createElement('canvas');
    canvas.className = 'mx-auto block h-full max-h-full w-auto max-w-full touch-none select-none rounded-xl object-contain';
    canvas.setAttribute('role', 'img');
    canvas.setAttribute('aria-label', 'Tu giro de 360°; arrástralo para girarlo');
    container.append(canvas);

    let index = Math.max(0, frames.indexOf(mostFrontalFrame(frames)));
    let playing = !reducedMotion;
    let timer = null;
    let dragStart = null;

    const draw = () => render(frames[index], canvas);
    const step = (delta) => {
        index = (index + delta + frames.length) % frames.length;
        draw();
    };

    const setPlaying = (value) => {
        playing = value;
        clearInterval(timer);
        timer = playing ? setInterval(() => step(1), 1000 / 10) : null;
    };

    canvas.addEventListener('pointerdown', (event) => {
        dragStart = { x: event.clientX, index };
        canvas.setPointerCapture(event.pointerId);
        setPlaying(false);
    });
    canvas.addEventListener('pointermove', (event) => {
        if (!dragStart) {
            return;
        }
        // Un ancho del canvas equivale a una vuelta completa.
        const perFrame = canvas.getBoundingClientRect().width / frames.length;
        const shift = Math.round((event.clientX - dragStart.x) / Math.max(2, perFrame));
        index = (((dragStart.index - shift) % frames.length) + frames.length) % frames.length;
        draw();
    });
    const endDrag = () => {
        dragStart = null;
    };
    canvas.addEventListener('pointerup', endDrag);
    canvas.addEventListener('pointercancel', endDrag);

    draw();
    setPlaying(playing);

    return {
        redraw: draw,
        fullTurn: true,
        rotateBy(radians) {
            setPlaying(false);
            const delta = Math.round((radians / (Math.PI * 2)) * frames.length);
            step(delta || Math.sign(radians));
        },
        setAutoRotate: setPlaying,
        get autoRotate() {
            return playing;
        },
        dispose() {
            clearInterval(timer);
            canvas.remove();
        },
    };
}

function shoulderWidth(body) {
    return Math.hypot(body.shoulderR.x - body.shoulderL.x, body.shoulderR.y - body.shoulderL.y);
}
