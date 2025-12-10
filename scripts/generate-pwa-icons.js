import sharp from 'sharp';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';
import { readFileSync } from 'fs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const publicDir = join(__dirname, '..', 'public');
const svgPath = join(publicDir, 'pwa-icon.svg');

const sizes = [
    { name: 'pwa-192x192.png', size: 192 },
    { name: 'pwa-512x512.png', size: 512 },
    { name: 'apple-touch-icon.png', size: 180 },
];

async function generateIcons() {
    console.log('Generating PWA icons...');

    const svgBuffer = readFileSync(svgPath);

    for (const { name, size } of sizes) {
        const outputPath = join(publicDir, name);
        await sharp(svgBuffer)
            .resize(size, size)
            .png()
            .toFile(outputPath);
        console.log(`Generated: ${name} (${size}x${size})`);
    }

    console.log('Done!');
}

generateIcons().catch(console.error);
