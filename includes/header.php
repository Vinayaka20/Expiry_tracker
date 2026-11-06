<?php
/**
 * Header Component
 * Contains HTML head, navigation, and top bar
 */

require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/functions.php';

$user = getCurrentUser();
$unread_count = $user ? getUnreadNotificationCount($user['id']) : 0;
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LifeLeft - Personal Expiry & Renewal Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {}
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        // Apply saved theme before first paint to prevent flash
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <div class="flex min-h-screen">
        <!-- Mobile overlay -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-30 hidden lg:hidden"></div>

        <!-- Sidebar -->
        <aside id="sidebar" class="glass-sidebar w-64 fixed inset-y-0 left-0 text-white overflow-y-auto z-40 -translate-x-full lg:translate-x-0">
            <div class="p-5">
                <div class="flex items-center justify-between">
                    <h1 class="text-xl font-extrabold flex items-center gap-3">
                        <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-400 to-blue-500 flex items-center justify-center shadow-lg shadow-teal-500/30 flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </span>
                        <span class="brand-text bg-gradient-to-r from-white to-teal-200 bg-clip-text text-transparent">LifeLeft</span>
                    </h1>
                    <button id="sidebarCollapse" class="hidden lg:flex p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-colors" title="Collapse sidebar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                        </svg>
                    </button>
                </div>
                <p class="nav-section-label text-slate-400 text-xs mt-2 ml-[52px] uppercase tracking-widest font-semibold">Expiry Tracker</p>
            </div>

            <nav class="mt-4 px-4 space-y-1">
                <p class="nav-section-label px-4 pb-1 text-[0.65rem] font-bold uppercase tracking-[0.15em] text-slate-500">Menu</p>
                <a href="<?php echo BASE_URL; ?>/dashboard/" class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-white/10 hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && dirname($_SERVER['PHP_SELF']) == '/dashboard' ? 'nav-link-active' : ''; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span class="nav-text">Dashboard</span>
                </a>
                
                <a href="<?php echo BASE_URL; ?>/records/" class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-white/10 hover:text-white transition-all duration-200 <?php echo strpos($_SERVER['PHP_SELF'], '/records/') !== false ? 'nav-link-active' : ''; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <span class="nav-text">Records</span>
                </a>
                
                <a href="<?php echo BASE_URL; ?>/renewals/" class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-white/10 hover:text-white transition-all duration-200 <?php echo strpos($_SERVER['PHP_SELF'], '/renewals/') !== false ? 'nav-link-active' : ''; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span class="nav-text">Renewals</span>
                </a>
                
                <a href="<?php echo BASE_URL; ?>/calendar/" class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-white/10 hover:text-white transition-all duration-200 <?php echo strpos($_SERVER['PHP_SELF'], '/calendar/') !== false ? 'nav-link-active' : ''; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span class="nav-text">Calendar</span>
                </a>
                
                <a href="<?php echo BASE_URL; ?>/reminders/" class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-white/10 hover:text-white transition-all duration-200 <?php echo strpos($_SERVER['PHP_SELF'], '/reminders/') !== false ? 'nav-link-active' : ''; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <span class="nav-text">Reminders</span>
                </a>
                
                <a href="<?php echo BASE_URL; ?>/notifications/" class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-white/10 hover:text-white transition-all duration-200 <?php echo strpos($_SERVER['PHP_SELF'], '/notifications/') !== false ? 'nav-link-active' : ''; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <span class="nav-text">Notifications</span>
                    <?php if ($unread_count > 0): ?>
                    <span class="ml-auto bg-gradient-to-r from-rose-500 to-red-500 text-white text-xs px-2 py-0.5 rounded-full shadow-md shadow-red-500/30"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="<?php echo BASE_URL; ?>/settings/" class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-white/10 hover:text-white transition-all duration-200 <?php echo strpos($_SERVER['PHP_SELF'], '/settings/') !== false ? 'nav-link-active' : ''; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span class="nav-text">Settings</span>
                </a>
            </nav>
            
            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-white/10">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-teal-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold shadow-lg shadow-teal-500/30 flex-shrink-0">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                    <div class="user-meta flex-1 min-w-0">
                        <p class="font-semibold text-sm truncate"><?php echo htmlspecialchars($user['full_name']); ?></p>
                        <p class="text-xs text-slate-400 truncate"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>
                <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="logout-text mt-3 flex items-center gap-2 text-slate-400 hover:text-white text-sm transition-colors px-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    Logout
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="flex-1 lg:ml-64 min-w-0">
            <!-- Top Bar -->
            <header class="glass-topbar px-5 lg:px-8 py-4 sticky top-0 z-10">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <button id="sidebarToggle" class="lg:hidden p-2.5 rounded-xl bg-white/60 dark:bg-white/10 backdrop-blur-md border border-white/40 dark:border-white/10 text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-white/20 transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                        <div class="min-w-0">
                            <h2 class="text-xl lg:text-2xl font-extrabold bg-gradient-to-r from-slate-800 to-slate-500 dark:from-white dark:to-slate-300 bg-clip-text text-transparent truncate"><?php echo $page_title ?? 'Dashboard'; ?></h2>
                            <p class="text-slate-500 dark:text-slate-400 text-sm truncate"><?php echo $page_subtitle ?? 'Welcome back!'; ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <!-- Dark Mode Toggle -->
                        <button id="darkModeToggle" class="p-2.5 rounded-xl bg-white/60 dark:bg-white/10 backdrop-blur-md border border-white/40 dark:border-white/10 text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-white/20 transition-all duration-200 shadow-sm">
                            <svg id="sunIcon" class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <svg id="moonIcon" class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                            </svg>
                        </button>
                        <a href="<?php echo BASE_URL; ?>/records/add.php" class="bg-gradient-to-r from-teal-600 to-blue-600 text-white px-5 py-2.5 rounded-xl hover:from-teal-500 hover:to-blue-500 transition-all duration-200 flex items-center gap-2 shadow-lg shadow-teal-500/30 hover:shadow-blue-500/40 hover:-translate-y-0.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Add Record
                        </a>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <div class="p-8">
                <?php
                $flash = getFlashMessage();
                if ($flash):
                ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $flash['type'] == 'success' ? 'bg-green-100 text-green-800' : ($flash['type'] == 'error' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'); ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
                <?php endif; ?>
    <?php else: ?>
    <!-- Public Header (for login/register pages) -->
    <div class="min-h-screen bg-gradient-to-br from-[#0B1220] via-[#111B31] to-[#0A1628] relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-teal-500/20 via-transparent to-transparent pointer-events-none"></div>
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-teal-600/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="container mx-auto px-4 py-8 relative">
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-white flex items-center justify-center gap-3">
                    <span class="w-14 h-14 rounded-2xl bg-gradient-to-br from-teal-500 to-emerald-500 flex items-center justify-center shadow-xl shadow-teal-500/40">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </span>
                    <span class="bg-gradient-to-r from-white via-teal-200 to-emerald-200 bg-clip-text text-transparent">LifeLeft</span>
                </h1>
                <p class="text-slate-400 mt-2">Personal Expiry & Renewal Tracker</p>
            </div>
    <?php endif; ?>
