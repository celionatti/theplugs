<?php
$dbTypes = $stepData['config']['database_types'] ?? [];
$sessionDb = $stepData['session_data']['database'] ?? [];
?>
<div class="space-y-10">
    <div class="space-y-4">
        <h2 class="text-3xl font-bold text-gray-900 tracking-tight">Database Engine</h2>
        <p class="text-gray-500 text-lg">Connect your application to its digital memory. We support modern SQL engines.</p>
    </div>

    <form method="post" action="?step=2" class="space-y-8">
        <div class="p-8 bg-gray-50 rounded-[2.5rem] border border-gray-100 space-y-6">
            <div class="space-y-2">
                <label for="db_driver" class="text-sm font-bold text-gray-700 uppercase tracking-wider ml-1">Database Driver</label>
                <div class="relative">
                    <select name="db_driver" id="db_driver" class="w-full h-14 pl-5 pr-12 bg-white border border-gray-200 rounded-2xl appearance-none focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-medium text-gray-800" required>
                        <?php foreach ($dbTypes as $driver => $info): ?>
                            <option value="<?= $driver ?>" <?= ($sessionDb['driver'] ?? 'mysql') === $driver ? 'selected' : '' ?>>
                                <?= htmlspecialchars($info['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                        <i class="fas fa-chevron-down text-xs"></i>
                    </div>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-6" id="db-host-group">
                <div class="space-y-2">
                    <label for="db_host" class="text-sm font-bold text-gray-700 uppercase tracking-wider ml-1">Host Address</label>
                    <input type="text" name="db_host" id="db_host" class="w-full h-14 px-5 bg-white border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-medium text-gray-800"
                        value="<?= htmlspecialchars($sessionDb['host'] ?? 'localhost') ?>" placeholder="e.g., localhost">
                </div>
                <div class="space-y-2" id="db-port-group">
                    <label for="db_port" class="text-sm font-bold text-gray-700 uppercase tracking-wider ml-1">Network Port</label>
                    <input type="number" name="db_port" id="db_port" class="w-full h-14 px-5 bg-white border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-medium text-gray-800"
                        value="<?= htmlspecialchars($sessionDb['port'] ?? '3306') ?>" placeholder="3306">
                </div>
            </div>

            <div class="space-y-2" id="db-database-group">
                <label for="db_database" class="text-sm font-bold text-gray-700 uppercase tracking-wider ml-1">Database / Schema Name</label>
                <input type="text" name="db_database" id="db_database" class="w-full h-14 px-5 bg-white border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-medium text-gray-800"
                    value="<?= htmlspecialchars($sessionDb['database'] ?? '') ?>" placeholder="my_application_db" required>
                <p class="text-[11px] text-gray-400 ml-1">Ensure the database exists on your server before proceeding.</p>
            </div>

            <div class="grid sm:grid-cols-2 gap-6">
                <div class="space-y-2" id="db-username-group">
                    <label for="db_username" class="text-sm font-bold text-gray-700 uppercase tracking-wider ml-1">Username</label>
                    <input type="text" name="db_username" id="db_username" class="w-full h-14 px-5 bg-white border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-medium text-gray-800"
                        value="<?= htmlspecialchars($sessionDb['username'] ?? 'root') ?>" placeholder="root">
                </div>
                <div class="space-y-2" id="db-password-group">
                    <label for="db_password" class="text-sm font-bold text-gray-700 uppercase tracking-wider ml-1">Security Password</label>
                    <input type="password" name="db_password" id="db_password" class="w-full h-14 px-5 bg-white border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-medium text-gray-800"
                        value="<?= htmlspecialchars($sessionDb['password'] ?? '') ?>" placeholder="••••••••">
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <button type="button" id="test-connection-btn" class="w-full h-14 bg-white border-2 border-emerald-500 text-emerald-600 font-bold rounded-2xl transition-all hover:bg-emerald-50 flex items-center justify-center gap-3">
                <i class="fas fa-plug text-sm"></i> Validate Connection Health
            </button>
            <div id="connection-status" class="text-center transition-all duration-300"></div>
        </div>

        <div class="flex gap-4 pt-4">
            <a href="?step=1" class="w-1/3 h-16 flex items-center justify-center text-gray-400 font-bold hover:text-gray-600 transition-colors">
                <i class="fas fa-chevron-left mr-2 text-xs"></i> Back
            </a>
            <button type="submit" class="w-2/3 h-16 bg-emerald-500 hover:bg-emerald-600 text-white font-bold rounded-[2rem] shadow-xl shadow-emerald-500/20 transition-all hover:-translate-y-1 flex items-center justify-center gap-3 text-lg">
                Continue to App Settings
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </form>
</div>