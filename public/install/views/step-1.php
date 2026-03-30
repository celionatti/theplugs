<div class="space-y-10">
    <div class="space-y-4">
        <h2 class="text-3xl font-bold text-gray-900 tracking-tight">System Requirements</h2>
        <p class="text-gray-500 text-lg">We need to ensure your server environment is optimized for the Plugs Framework.</p>
    </div>

    <!-- Requirements Grid -->
    <div class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <?php
            $allPassed = true;
            foreach ($requirements as $key => $req):
                if ($req['required'] && !$req['passed']) {
                    $allPassed = false;
                }
                $statusColor = $req['passed'] ? 'text-emerald-600 bg-emerald-50 border-emerald-100' : ($req['required'] ? 'text-red-600 bg-red-50 border-red-100' : 'text-amber-600 bg-amber-50 border-amber-100');
                $icon = $req['passed'] ? 'fa-check-circle' : ($req['required'] ? 'fa-times-circle' : 'fa-exclamation-circle');
                ?>
                <div class="p-5 border border-gray-100 rounded-3xl bg-white/50 transition-all hover:border-emerald-200 hover:shadow-lg hover:shadow-emerald-500/5 group">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-bold text-gray-800 tracking-tight"><?= htmlspecialchars($req['name']) ?></span>
                        <div class="w-8 h-8 rounded-full flex items-center justify-center <?= $statusColor ?> border">
                            <i class="fas <?= $icon ?> text-xs"></i>
                        </div>
                    </div>
                    <div class="text-sm text-gray-500">
                        <?php if ($req['passed']): ?>
                            Detected: <span class="text-emerald-600 font-semibold"><?= htmlspecialchars($req['current']) ?></span>
                        <?php else: ?>
                            <?php if ($req['required']): ?>
                                <?php if (strpos(strtolower($req['name']), 'directory') !== false): ?>
                                    <span class="text-red-500 font-semibold">Not Writable</span>
                                <?php else: ?>
                                    <span class="text-red-500 font-semibold">Missing / Outdated</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-amber-500 font-semibold">Optional</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (!$allPassed): ?>
        <div class="p-6 bg-red-50 border border-red-100 rounded-[2rem] flex items-start gap-4 text-red-700 shadow-sm">
            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                <i class="fas fa-server"></i>
            </div>
            <div>
                <h4 class="font-bold text-lg mb-1">Incompatible Environment</h4>
                <p class="text-sm opacity-90">Please address the missing dependencies above and refresh this page to continue your installation.</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- License Section -->
    <div class="p-8 bg-gray-50 rounded-[2.5rem] border border-gray-100 space-y-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-gray-900 flex items-center justify-center text-white">
                <i class="fas fa-file-contract"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800">Apache License 2.0</h3>
        </div>
        
        <div class="space-y-4 text-gray-500 text-sm leading-relaxed">
            <p>Copyright <?= date('Y') ?> Plugs Framework. Licensed under the Apache License, Version 2.0.</p>
            <p>You may use, modify, and distribute this framework for both personal and commercial projects, provided you maintain the original copyright notice.</p>
            
            <div class="grid grid-cols-2 gap-3 pt-2">
                <div class="flex items-center gap-2 text-emerald-600 font-medium">
                    <i class="fas fa-check-circle text-xs"></i> Commercial Use
                </div>
                <div class="flex items-center gap-2 text-emerald-600 font-medium">
                    <i class="fas fa-check-circle text-xs"></i> Modifications
                </div>
                <div class="flex items-center gap-2 text-emerald-600 font-medium">
                    <i class="fas fa-check-circle text-xs"></i> Distribution
                </div>
                <div class="flex items-center gap-2 text-amber-600 font-medium">
                    <i class="fas fa-info-circle text-xs"></i> No Warranty
                </div>
            </div>
        </div>
    </div>

    <form method="post" action="?step=1" class="space-y-8">
        <label class="group flex items-start gap-4 p-6 border border-gray-100 rounded-[2rem] bg-white hover:border-emerald-200 cursor-pointer transition-all shadow-sm">
            <div class="relative flex items-center mt-1">
                <input type="checkbox" name="accept_license" value="1" required <?= !$allPassed ? 'disabled' : '' ?> class="peer h-6 w-6 cursor-pointer appearance-none rounded-lg border-2 border-gray-200 transition-all checked:border-emerald-500 checked:bg-emerald-500 disabled:opacity-50">
                <i class="fas fa-check absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-white text-[10px] opacity-0 transition-opacity peer-checked:opacity-100"></i>
            </div>
            <span class="text-gray-600 text-sm font-medium pt-1">
                I accept the <a href="https://www.apache.org/licenses/LICENSE-2.0" target="_blank" class="text-emerald-600 hover:underline">Apache License 2.0</a> terms and conditions.
            </span>
        </label>

        <div class="flex">
            <button type="submit" <?= !$allPassed ? 'disabled' : '' ?> class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-5 rounded-[2rem] shadow-xl shadow-emerald-500/20 transition-all hover:-translate-y-1 disabled:opacity-50 disabled:hover:translate-y-0 disabled:shadow-none flex items-center justify-center gap-3 text-lg">
                Continue to Database Configuration
                <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </form>
</div>
