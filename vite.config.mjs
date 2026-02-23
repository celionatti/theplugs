import { defineConfig } from 'vite';
import fs from 'fs';
import path from 'path';

export default defineConfig({
    plugins: [
        {
            name: 'plugs-vite-plugin',
            configureServer(server) {
                const hotFile = path.resolve('public/hot');
                server.httpServer?.once('listening', () => {
                    const address = server.httpServer?.address();
                    const isAddressInfo = (x) => typeof x === 'object' && x !== null;
                    
                    if (isAddressInfo(address)) {
                        const protocol = server.config.server.https ? 'https' : 'http';
                        const host = address.address === '::' ? 'localhost' : address.address;
                        const port = address.port;
                        const url = `${protocol}://${host}:${port}`;
                        
                        fs.writeFileSync(hotFile, url);
                    }
                });

                process.on('SIGINT', () => {
                    if (fs.existsSync(hotFile)) fs.unlinkSync(hotFile);
                    process.exit();
                });
            },
            buildStart() {
                const hotFile = path.resolve('public/hot');
                if (fs.existsSync(hotFile)) fs.unlinkSync(hotFile);
            },
            handleHotUpdate({ file, server }) {
                if (file.endsWith('.php')) {
                    server.ws.send({
                        type: 'full-reload',
                        path: '*'
                    });
                }
            }
        }
    ],
    publicDir: false,
    build: {
        outDir: 'public/build',
        manifest: 'manifest.json',
        rollupOptions: {
            input: ['resources/js/app.js', 'resources/css/app.css'],
        },
    },
    server: {
        host: 'localhost',
        port: 5173,
    }
});
