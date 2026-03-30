<?php
$appName = $stepData['session_data']['app']['name'] ?? 'Plugs Framework';
$appUrl = $stepData['session_data']['app']['url'] ?? '';
?>
<div class="space-y-12 text-center">
    <div class="space-y-6">
        <div class="w-24 h-24 bg-emerald-500 rounded-[2rem] flex items-center justify-center text-white text-4xl mx-auto shadow-2xl shadow-emerald-500/40 animate-bounce">
            <i class="fas fa-check"></i>
        </div>
        <div class="space-y-3">
            <h2 class="text-4xl font-extrabold text-gray-900 tracking-tight">System Operational</h2>
            <p class="text-gray-500 text-lg">
                Congratulations! <span class="text-emerald-600 font-bold"><?= htmlspecialchars($appName) ?></span> has been successfully deployed.
            </p>
        </div>
    </div>

    <div class="p-6 bg-red-50 border border-red-100 rounded-[2rem] flex items-start gap-4 text-red-700 shadow-sm text-left">
        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
            <i class="fas fa-shield-virus"></i>
        </div>
        <div>
            <h4 class="font-bold text-lg mb-1">Security Alert</h4>
            <p class="text-sm opacity-90">Please remove the <code class="bg-red-100 px-1.5 py-0.5 rounded font-bold">public/install</code> directory immediately to secure your installation.</p>
        </div>
    </div>

    <div class="p-8 bg-gray-50 rounded-[2.5rem] border border-gray-100 space-y-8 text-left">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-emerald-500 flex items-center justify-center text-white">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 tracking-tight">Deployment Summary</h3>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="flex items-center gap-3 text-emerald-600 font-medium text-sm">
                <div class="w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center text-[10px]"><i class="fas fa-check"></i></div>
                File Hierarchy Ready
            </div>
            <div class="flex items-center gap-3 text-emerald-600 font-medium text-sm">
                <div class="w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center text-[10px]"><i class="fas fa-check"></i></div>
                Database Migrated
            </div>
            <div class="flex items-center gap-3 text-emerald-600 font-medium text-sm">
                <div class="w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center text-[10px]"><i class="fas fa-check"></i></div>
                Admin Identity Created
            </div>
            <div class="flex items-center gap-3 text-emerald-600 font-medium text-sm">
                <div class="w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center text-[10px]"><i class="fas fa-check"></i></div>
                Environment Active
            </div>
        </div>

        <div class="pt-6 border-t border-gray-200">
            <p class="text-gray-400 font-bold text-xs uppercase tracking-widest mb-4">Immediate Next Steps</p>
            <ul class="space-y-3 text-gray-500 text-sm">
                <li class="flex gap-3"><span class="text-emerald-500 font-bold">01.</span> Execute secure cleanup below.</li>
                <li class="flex gap-3"><span class="text-emerald-500 font-bold">02.</span> Explore the <a href="https://github.com/celionatti/plugs" class="text-emerald-600 hover:underline font-bold">Documentation</a>.</li>
                <li class="flex gap-3"><span class="text-emerald-500 font-bold">03.</span> Create your first controller using <code class="bg-gray-200 px-1 rounded text-gray-700">php plugs make:controller</code>.</li>
            </ul>
        </div>
    </div>

    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <button type="button" id="btn-cleanup" class="flex-1 bg-gray-900 border-2 border-gray-900 hover:bg-black text-white font-bold py-5 rounded-[2rem] shadow-xl transition-all hover:-translate-y-1 flex items-center justify-center gap-3 text-lg">
                <i class="fas fa-rocket"></i>
                Launch & Clean Up
            </button>
            <button type="button" id="btn-composer" class="flex-none sm:w-48 bg-white border-2 border-emerald-500 text-emerald-600 font-bold py-5 rounded-[2rem] transition-all hover:bg-emerald-50 flex items-center justify-center gap-3">
                <i class="fas fa-box"></i>
                Sync Deps
            </button>
        </div>
        <div id="action-status" class="text-sm font-medium text-gray-400 pb-2">Ready for take-off.</div>
        <a href="<?= htmlspecialchars($appUrl) ?>/" class="inline-block text-gray-400 text-xs font-bold uppercase tracking-widest hover:text-emerald-500 transition-colors">
            Skip to Homepage <i class="fas fa-external-link-alt ml-1"></i>
        </a>
    </div>
</div>

<style>
    .confetti {
        position: absolute;
        width: 10px;
        height: 10px;
        background-color: #f2d74e;
        top: -10px;
        z-index: 1000;
        border-radius: 20%;
        animation: confetti-fall 3s linear forwards;
    }

    @keyframes confetti-fall {
        0% { transform: translateY(0) rotate(0deg); opacity: 1; }
        100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
    }
</style>

<script>
    function createConfetti() {
        const colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'];
        for (let i = 0; i < 50; i++) {
            const confetti = document.createElement('div');
            confetti.className = 'confetti';
            confetti.style.left = Math.random() * 100 + 'vw';
            confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            confetti.style.animationDelay = Math.random() * 2 + 's';
            confetti.style.width = Math.random() * 8 + 5 + 'px';
            confetti.style.height = confetti.style.width;
            document.body.appendChild(confetti);
            setTimeout(() => confetti.remove(), 5000);
        }
    }

    // Trigger initial celebration
    setTimeout(createConfetti, 500);

    document.getElementById('btn-composer').addEventListener('click', async function () {
        const btn = this;
        const status = document.getElementById('action-status');
        btn.disabled = true;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';
        status.innerHTML = '<span class="text-emerald-600">Initializing Composer synchronization... Please wait.</span>';

        try {
            const response = await fetch('?action=composer', { method: 'POST' });
            const data = await response.json();

            if (data.success) {
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Synced';
                btn.className = btn.className.replace('border-emerald-500 text-emerald-600', 'border-emerald-600 bg-emerald-600 text-white');
                status.innerHTML = '<span class="text-emerald-600">' + data.message + '</span>';
                createConfetti();
            } else {
                btn.innerHTML = '<i class="fas fa-exclamation-circle"></i> Failed';
                btn.disabled = false;
                status.innerHTML = '<span class="text-red-500">Error: ' + (data.error || 'Sync failed') + '</span>';
                if (data.details) {
                    console.error(data.details);
                    alert('Composer Output:\n' + data.details);
                }
            }
        } catch (e) {
            btn.innerHTML = '<i class="fas fa-wifi-slash"></i> Error';
            btn.disabled = false;
            status.innerHTML = '<span class="text-red-500">Communication failed.</span>';
        }
    });

    document.getElementById('btn-cleanup').addEventListener('click', async function () {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Optimizing...';
        
        createConfetti();

        try {
            const response = await fetch('?action=cleanup', { method: 'POST' });
            const data = await response.json();

            if (data.success) {
                setTimeout(() => {
                    window.location.href = data.redirect || '<?= htmlspecialchars($appUrl) ?>/';
                }, 1500);
            } else {
                alert(data.error || 'Cleanup failed. Please delete the install folder manually.');
                window.location.href = '<?= htmlspecialchars($appUrl) ?>/';
            }
        } catch (e) {
            window.location.href = '<?= htmlspecialchars($appUrl) ?>/';
        }
    });
</script>
