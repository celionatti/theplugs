<?php
$sessionAdmin = $stepData['session_data']['admin'] ?? [];
?>
<div class="space-y-10">
    <div class="space-y-4">
        <h2 class="text-3xl font-bold text-gray-900 tracking-tight">Master Administrator</h2>
        <p class="text-gray-500 text-lg">Establish the primary authority for your new Plugs application.</p>
    </div>

    <form method="post" action="?step=4" class="space-y-8">
        <div class="p-8 bg-gray-50 rounded-[2.5rem] border border-gray-100 space-y-6">
            <div class="space-y-2">
                <label for="admin_name" class="text-sm font-bold text-gray-700 uppercase tracking-wider ml-1">Full Name</label>
                <input type="text" name="admin_name" id="admin_name" class="w-full h-14 px-5 bg-white border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-medium text-gray-800"
                    value="<?= htmlspecialchars($sessionAdmin['name'] ?? '') ?>" placeholder="e.g., John Doe"
                    required>
            </div>

            <div class="space-y-2">
                <label for="admin_email" class="text-sm font-bold text-gray-700 uppercase tracking-wider ml-1">Administrative Email</label>
                <input type="email" name="admin_email" id="admin_email" class="w-full h-14 px-5 bg-white border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-medium text-gray-800"
                    value="<?= htmlspecialchars($sessionAdmin['email'] ?? '') ?>" placeholder="admin@yourdomain.com"
                    required>
                <p class="text-[11px] text-gray-400 ml-1 font-medium">This email will serve as your primary login identifier.</p>
            </div>

            <div class="grid sm:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label for="admin_password" class="text-sm font-bold text-gray-700 uppercase tracking-wider ml-1">Secure Password</label>
                    <input type="password" name="admin_password" id="admin_password" class="w-full h-14 px-5 bg-white border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-medium text-gray-800"
                        placeholder="••••••••" minlength="8" required>
                    <p class="text-[11px] text-gray-400 ml-1 font-medium">Minimum 8 characters. Mix case, numbers, and symbols.</p>
                </div>

                <div class="space-y-2">
                    <label for="admin_password_confirm" class="text-sm font-bold text-gray-700 uppercase tracking-wider ml-1">Verify Password</label>
                    <input type="password" name="admin_password_confirm" id="admin_password_confirm"
                        class="w-full h-14 px-5 bg-white border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-medium text-gray-800" placeholder="••••••••" required>
                </div>
            </div>
        </div>

        <div class="p-6 bg-amber-50 border border-amber-100 rounded-[2rem] flex items-start gap-4 text-amber-700 shadow-sm transition-all hover:bg-amber-100/50">
            <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
                <i class="fas fa-lightbulb"></i>
            </div>
            <div>
                <h4 class="font-bold text-lg mb-1">Identity Note</h4>
                <p class="text-sm opacity-90">Ensure these credentials are kept secure. They grant full access to your application core.</p>
            </div>
        </div>

        <div class="flex gap-4 pt-4">
            <a href="?step=3" class="w-1/3 h-16 flex items-center justify-center text-gray-400 font-bold hover:text-gray-600 transition-colors">
                <i class="fas fa-chevron-left mr-2 text-xs"></i> Back
            </a>
            <button type="submit" class="w-2/3 h-16 bg-emerald-500 hover:bg-emerald-600 text-white font-bold rounded-[2rem] shadow-xl shadow-emerald-500/20 transition-all hover:-translate-y-1 flex items-center justify-center gap-3 text-lg">
                <i class="fas fa-magic"></i>
                Begin Full Installation
            </button>
        </div>
    </form>
</div>