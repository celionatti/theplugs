import { defineConfig } from 'vite';
import fs from 'fs';
import path from 'path';
import { execSync } from 'child_process';

/**
 * Run Plugs CSS Build
 */
const buildPlugsCss = () => {
    try {
        console.log('\x1b[36m%s\x1b[0m', '⚡ Plugs CSS: Rebuilding...');
        execSync('php theplugs css:build', { stdio: 'inherit' });
    } catch (error) {
        console.error('\x1b[31m%s\x1b[0m', '❌ Plugs CSS Build Failed');
    }
};

export default defineConfig({
    plugins: [
        {
            name: 'plugs-vite-plugin',
            configureServer(server) {
                const hotFile = path.resolve('public/hot');
                
                // Initial build
                buildPlugsCss();

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
                
                // Also build on production build start
                if (process.env.NODE_ENV === 'production') {
                    buildPlugsCss();
                }
            },
            handleHotUpdate({ file, server }) {
                // If a template changes, rebuild Plugs CSS
                if (file.endsWith('.php') || file.endsWith('.plug.php')) {
                    buildPlugsCss();
                    
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
