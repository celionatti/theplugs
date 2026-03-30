<?php
$timezones = $stepData['config']['timezones'] ?? [];
$sessionApp = $stepData['session_data']['app'] ?? [];
$detectedUrl = $stepData['detected_url'] ?? '';

// Fallback detection if controller data is missing
if (empty($detectedUrl)) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? '') == 443) ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = $_SERVER['REQUEST_URI'] ?? '/';
    $path = str_replace(['/public/install/index.php', '/public/install', '/install/index.php', '/install'], '', $path);
    if (($pos = strpos($path, '?')) !== false) $path = substr($path, 0, $pos);
    $detectedUrl = rtrim($protocol . '://' . $host . $path, '/');
}
?>
<div class="space-y-10">
    <div class="space-y-4">
        <h2 class="text-3xl font-bold text-gray-900 tracking-tight">Application Identity</h2>
        <p class="text-gray-500 text-lg">Define how the world sees and interacts with your Plugs application.</p>
    </div>

    <form method="post" action="?step=3" class="space-y-8">
        <div class="p-8 bg-gray-50 rounded-[2.5rem] border border-gray-100 space-y-6">
            <div class="space-y-2">
                <label for="app_name" class="text-sm font-bold text-gray-700 uppercase tracking-wider ml-1">Application Name</label>
                <input type="text" name="app_name" id="app_name" class="w-full h-14 px-5 bg-white border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-medium text-gray-800"
                    value="<?= htmlspecialchars($sessionApp['name'] ?? 'My Plugs App') ?>"
                    placeholder="e.g., Plugs Commerce" required>
                <p class="text-[11px] text-gray-400 ml-1 font-medium">Displayed in browser titles, emails, and system logs.</p>
            </div>

            <div class="space-y-2">
                <label for="app_url" class="text-sm font-bold text-gray-700 uppercase tracking-wider ml-1">Canonical URL</label>
                <input type="url" name="app_url" id="app_url" class="w-full h-14 px-5 bg-white border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-medium text-gray-800"
                    value="<?= htmlspecialchars($sessionApp['url'] ?? $detectedUrl) ?>"
                    placeholder="https://plugs.framework" required>
                <p class="text-[11px] text-gray-400 ml-1 font-medium">The base URL of your site. Do not include a trailing slash.</p>
            </div>

            <div class="grid sm:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label for="app_env" class="text-sm font-bold text-gray-700 uppercase tracking-wider ml-1">Current Environment</label>
                    <div class="relative">
                        <select name="app_env" id="app_env" class="w-full h-14 pl-5 pr-12 bg-white border border-gray-200 rounded-2xl appearance-none focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-medium text-gray-800" required>
                            <option value="local" <?= ($sessionApp['env'] ?? 'local') === 'local' ? 'selected' : '' ?>>
                                Local (Development)
                            </option>
                            <option value="staging" <?= ($sessionApp['env'] ?? '') === 'staging' ? 'selected' : '' ?>>
                                Staging (UAT)
                            </option>
                            <option value="production" <?= ($sessionApp['env'] ?? '') === 'production' ? 'selected' : '' ?>>
                                Production (Live)
                            </option>
                        </select>
                        <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                            <i class="fas fa-chevron-down text-xs"></i>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="app_timezone" class="text-sm font-bold text-gray-700 uppercase tracking-wider ml-1">System Timezone</label>
                    <div class="relative">
                        <select name="app_timezone" id="app_timezone" class="w-full h-14 pl-5 pr-12 bg-white border border-gray-200 rounded-2xl appearance-none focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-medium text-gray-800" required>
                            <?php foreach ($timezones as $tz => $label): ?>
                                <option value="<?= $tz ?>" <?= ($sessionApp['timezone'] ?? 'UTC') === $tz ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                            <i class="fas fa-chevron-down text-xs"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 bg-emerald-50 border border-emerald-100 rounded-[2rem] flex items-start gap-4 text-emerald-700 shadow-sm transition-all hover:bg-emerald-100/50">
            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div>
                <h4 class="font-bold text-lg mb-1">Security First</h4>
                <p class="text-sm opacity-90">Plugs will automatically generate a unique 256-bit encryption key for your application during setup.</p>
            </div>
        </div>

        <div class="flex gap-4 pt-4">
            <a href="?step=2" class="w-1/3 h-16 flex items-center justify-center text-gray-400 font-bold hover:text-gray-600 transition-colors">
                <i class="fas fa-chevron-left mr-2 text-xs"></i> Back
            </a>
            <button type="submit" class="w-2/3 h-16 bg-emerald-500 hover:bg-emerald-600 text-white font-bold rounded-[2rem] shadow-xl shadow-emerald-500/20 transition-all hover:-translate-y-1 flex items-center justify-center gap-3 text-lg">
                Continue to Admin Setup
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </form>
</div>
