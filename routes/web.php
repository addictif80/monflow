<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\FeedbackController;
use App\Http\Controllers\Portal\TicketController;
use App\Http\Controllers\Portal\PlaylistController;
use App\Http\Controllers\Portal\DeemixProxyController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

// ─── Public profiles ───
Route::get('/u/{displayName}', [\App\Http\Controllers\PublicProfileController::class, 'show'])->name('public.profile');
Route::get('/public/playlists/{sharedId}/tracks', [\App\Http\Controllers\PublicProfileController::class, 'playlistTracks']);

// ─── Public ───
Route::get('/', function () {
    if (\Illuminate\Support\Facades\Auth::check()) {
        return redirect(\Illuminate\Support\Facades\Auth::user()->is_admin ? '/admin' : '/portal');
    }
    return redirect('/login');
});

// ─── Auth ───
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:password-reset');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:auth');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ─── Email verification (public, token-based) ───
Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmail'])->name('verify.email');
Route::post('/verify-email/resend', [AuthController::class, 'resendVerification'])->middleware('auth')->name('verify.resend');
Route::post('/verify-email/resend-public', [AuthController::class, 'resendVerificationPublic'])->middleware('throttle:auth')->name('verify.resend.public');

// ─── Stripe Webhook (no auth, no CSRF) ───
Route::post('/stripe/webhook', [PaymentController::class, 'stripeWebhook'])->name('stripe.webhook');

// ─── Customer Portal ───
Route::middleware('auth')->group(function () {
    Route::get('/portal', [DashboardController::class, 'index'])->name('portal');
    Route::match(['get', 'post'], '/portal/profile', [DashboardController::class, 'profile']);
    Route::post('/portal/profile/display-name', [DashboardController::class, 'updateDisplayName']);
    Route::post('/portal/profile/avatar', [DashboardController::class, 'updateAvatar']);
    Route::match(['get', 'post'], '/portal/change-password', [DashboardController::class, 'changePassword']);
    Route::get('/portal/plans', [DashboardController::class, 'plans']);
    Route::get('/portal/subscribe/{plan}', [DashboardController::class, 'subscribe']);
    Route::post('/portal/resume-payment', [DashboardController::class, 'resumePayment']);
    Route::get('/portal/cancel-subscription', [DashboardController::class, 'cancelSubscriptionConfirm']);
    Route::post('/portal/cancel-subscription', [DashboardController::class, 'cancelSubscription']);
    Route::get('/portal/wallet', [DashboardController::class, 'wallet']);
    Route::post('/portal/wallet/topup', [DashboardController::class, 'walletTopup']);
    Route::match(['get', 'post'], '/portal/gift', [DashboardController::class, 'gift']);
    Route::get('/portal/devices', [DashboardController::class, 'devices']);
    Route::delete('/portal/devices/{device}', [DashboardController::class, 'revokeDevice']);
    Route::get('/portal/payments', [DashboardController::class, 'payments']);

    // Payment success pages
    Route::get('/payments/success', [PaymentController::class, 'success']);
    Route::get('/payments/wallet-success', [PaymentController::class, 'walletSuccess']);
    Route::get('/payments/gift-success', [PaymentController::class, 'giftSuccess']);

    // Deemix integration (reverse proxy, tout est sous /portal/deemix)
    Route::middleware('subscribed')->group(function () {
        Route::any('/portal/deemix', [DeemixProxyController::class, 'handle']);
        Route::any('/portal/deemix/{any?}', [DeemixProxyController::class, 'handle'])->where('any', '.*');
    });

    // Support tickets
    Route::get('/support/tickets', [TicketController::class, 'index']);
    Route::match(['get', 'post'], '/support/tickets/create', [TicketController::class, 'create']);
    Route::match(['get', 'post'], '/support/tickets/{ticket}', [TicketController::class, 'show']);

    // Feedback
    Route::get('/portal/feedback', [FeedbackController::class, 'index']);
    Route::match(['get', 'post'], '/portal/feedback/create', [FeedbackController::class, 'create']);
    Route::get('/portal/feedback/{id}', [FeedbackController::class, 'show']);

    // Wallet payment for subscription
    Route::post('/portal/wallet-pay', [DashboardController::class, 'walletPay']);

    // Notifications
    Route::get('/portal/notifications', [DashboardController::class, 'notifications']);
    Route::post('/portal/notifications/read', [DashboardController::class, 'markNotificationsRead']);

    // Invoice PDF
    Route::get('/portal/payments/{id}/invoice', [DashboardController::class, 'invoice']);

    // Playlists
    Route::get('/portal/playlists', [PlaylistController::class, 'index']);
    Route::post('/portal/playlists', [PlaylistController::class, 'store']);
    Route::get('/portal/playlists/search', [PlaylistController::class, 'search']);
    Route::get('/portal/playlists/{id}', [PlaylistController::class, 'show']);
    Route::put('/portal/playlists/{id}', [PlaylistController::class, 'update']);
    Route::delete('/portal/playlists/{id}', [PlaylistController::class, 'destroy']);
    Route::post('/portal/playlists/{id}/tracks', [PlaylistController::class, 'addTracks']);
    Route::delete('/portal/playlists/{id}/tracks', [PlaylistController::class, 'removeTrack']);
    Route::get('/portal/playlists/{id}/info', [PlaylistController::class, 'info']);
    Route::post('/portal/playlists/{id}/toggle-public', [PlaylistController::class, 'togglePublic']);
    Route::post('/portal/playlists/{id}/share', [PlaylistController::class, 'share']);
    Route::post('/portal/shared/{sharedId}/subscribe', [PlaylistController::class, 'subscribe'])->name('playlist.subscribe');
    Route::delete('/portal/shared/{sharedId}/unsubscribe', [PlaylistController::class, 'unsubscribe']);

    // GDPR data export
    Route::get('/portal/export-data', [DashboardController::class, 'exportData']);

    // Stop impersonation (admin only, via session)
    Route::post('/portal/stop-impersonate', [AdminController::class, 'stopImpersonate']);

    // Account deletion
    Route::match(['get', 'post'], '/portal/delete-account', [DashboardController::class, 'deleteAccount']);

    // Top songs (server-side, toutes écoutes confondues)
    Route::get('/player/top-songs', function (\App\Services\NavidromeService $nd) {
        try {
            return response()->json($nd->getTopPlayedSongs(20));
        } catch (\Exception $e) {
            return response()->json([], 500);
        }
    });

    // Web player — réservé aux abonnés actifs (et admins)
    Route::get('/player', function () {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user->is_admin && !$user->activeSubscription) {
            return redirect('/portal/plans')->with('error', 'Le lecteur est réservé aux abonnés. Souscrivez à une formule pour accéder à votre musique.');
        }
        $password = $user->getDecryptedPassword();
        if (!$password) {
            return redirect('/portal')->with('error', 'Lecteur web indisponible : mot de passe non stocké. Changez votre mot de passe depuis votre profil.');
        }
        $salt = \Illuminate\Support\Str::random(12);
        return view('player.index', [
            'ndUrl' => rtrim(config('navidrome.public_url'), '/'),
            'ndUser' => $user->username,
            'ndSalt' => $salt,
            'ndToken' => md5($password . $salt),
        ]);
    });
});

// ─── Admin Panel ───
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin');

    // Users
    Route::get('/users', [AdminController::class, 'users']);
    Route::match(['get', 'post'], '/users/create', [AdminController::class, 'userCreate']);
    Route::match(['get', 'post'], '/users/{id}/edit', [AdminController::class, 'userEdit']);
    Route::get('/users/{id}', [AdminController::class, 'userDetail']);
    Route::post('/users/{id}/reveal-password', [AdminController::class, 'revealPassword']);
    Route::post('/users/{id}/suspend', [AdminController::class, 'userSuspend']);
    Route::post('/users/{id}/reactivate', [AdminController::class, 'userReactivate']);
    Route::post('/users/{id}/delete', [AdminController::class, 'userDelete']);
    Route::post('/users/{id}/release-email', [AdminController::class, 'userReleaseEmail']);
    Route::post('/users/{id}/wallet-adjust', [AdminController::class, 'walletAdjust']);
    Route::post('/users/{id}/impersonate', [AdminController::class, 'impersonate']);

    // Plans
    Route::get('/plans', [AdminController::class, 'plans']);
    Route::match(['get', 'post'], '/plans/create', [AdminController::class, 'planCreate']);
    Route::match(['get', 'post'], '/plans/{id}/edit', [AdminController::class, 'planEdit']);

    // Promos
    Route::get('/promos', [AdminController::class, 'promos']);
    Route::match(['get', 'post'], '/promos/create', [AdminController::class, 'promoCreate']);
    Route::match(['get', 'post'], '/promos/{id}/edit', [AdminController::class, 'promoEdit']);

    // Payments & Refunds
    Route::get('/payments', [AdminController::class, 'payments']);
    Route::get('/refunds', [AdminController::class, 'refunds']);
    Route::match(['get', 'post'], '/payments/{id}/refund', [AdminController::class, 'refundCreate']);

    // Subscriptions
    Route::get('/subscriptions', [AdminController::class, 'subscriptions']);
    Route::get('/subscriptions/reminders-eligible', [AdminController::class, 'subscriptionRemindersEligible']);
    Route::post('/subscriptions/{id}/send-reminder', [AdminController::class, 'subscriptionSendReminder']);
    Route::post('/subscriptions/preview-overdue', [AdminController::class, 'subscriptionPreviewOverdue']);
    Route::post('/subscriptions/process-overdue', [AdminController::class, 'subscriptionProcessOverdue']);
    Route::post('/subscriptions/process-reminders', [AdminController::class, 'subscriptionProcessReminders']);
    Route::get('/subscriptions/{id}', [AdminController::class, 'subscriptionDetail']);
    Route::post('/subscriptions/{id}/extend', [AdminController::class, 'subscriptionExtend']);
    Route::post('/subscriptions/{id}/cancel', [AdminController::class, 'subscriptionCancel']);
    Route::post('/subscriptions/{id}/change-plan', [AdminController::class, 'subscriptionChangePlan']);
    Route::post('/subscriptions/{id}/update-dates', [AdminController::class, 'subscriptionUpdateDates']);
    Route::post('/subscriptions/create', [AdminController::class, 'subscriptionCreate']);

    // Tickets
    Route::get('/tickets', [AdminController::class, 'tickets']);
    Route::match(['get', 'post'], '/tickets/{id}', [AdminController::class, 'ticketDetail']);

    // Feedbacks
    Route::get('/feedbacks', [AdminController::class, 'feedbacks']);
    Route::match(['get', 'post'], '/feedbacks/{id}', [AdminController::class, 'feedbackDetail']);
    Route::post('/feedbacks/{id}/to-ticket', [AdminController::class, 'feedbackToTicket']);

    // Lyrics management
    Route::get('/lyrics', [AdminController::class, 'lyrics']);
    Route::get('/lyrics/missing', [AdminController::class, 'lyricsMissing']);
    Route::get('/lyrics/{id}/get', [AdminController::class, 'lyricsGet']);
    Route::get('/lyrics/{id}/download', [AdminController::class, 'lyricsDownload']);
    Route::get('/lyrics/{id}/edit', [AdminController::class, 'lyricsEdit']);
    Route::post('/lyrics/{id}/save', [AdminController::class, 'lyricsSave']);
    Route::get('/lyrics/{id}/stream', [AdminController::class, 'lyricsStream']);

    // Metadata management
    Route::get('/metadata', [AdminController::class, 'metadata']);
    Route::get('/metadata/search-artwork', [AdminController::class, 'metadataSearchArtwork']);
    Route::get('/metadata/missing-covers', [AdminController::class, 'metadataMissingCovers']);
    Route::get('/metadata/{id}/cover-art', [AdminController::class, 'metadataCoverArt']);
    Route::get('/metadata/{id}/edit', [AdminController::class, 'metadataEdit']);
    Route::post('/metadata/{id}/save', [AdminController::class, 'metadataSave']);
    Route::post('/metadata/{id}/cover', [AdminController::class, 'metadataCover']);

    // Duplicate management
    Route::get('/duplicates', [AdminController::class, 'duplicates']);
    Route::get('/duplicates/scan-status', [AdminController::class, 'duplicateScanStatus']);
    Route::post('/duplicates/batch-delete', [AdminController::class, 'duplicateBatchDelete']);

    // Newsletters
    Route::get('/newsletters', [AdminController::class, 'newsletters']);
    Route::get('/newsletters/weekly-preview', [AdminController::class, 'weeklyNewsletterPreview']);
    Route::match(['get', 'post'], '/newsletters/template', [AdminController::class, 'newsletterTemplate']);
    Route::match(['get', 'post'], '/newsletters/create', [AdminController::class, 'newsletterCreate']);
    Route::match(['get', 'post'], '/newsletters/{id}/edit', [AdminController::class, 'newsletterEdit']);
    Route::post('/newsletters/{id}/send', [AdminController::class, 'newsletterSend']);
    Route::get('/newsletters/{id}/preview', [AdminController::class, 'newsletterPreview']);

    // Email logs
    Route::get('/email-logs', [AdminController::class, 'emailLogs']);
    Route::get('/email-logs/{id}/preview', [AdminController::class, 'emailLogPreview']);

    // Audit logs
    Route::get('/audit-logs', [AdminController::class, 'auditLogs']);
    Route::get('/logs', [AdminController::class, 'serverLogs']);

    // Settings
    Route::match(['get', 'post'], '/settings/smtp', [AdminController::class, 'smtpConfig']);
    Route::match(['get', 'post'], '/settings/restoration-fee', [AdminController::class, 'restorationFeeSettings']);
    Route::get('/settings/email-templates', [AdminController::class, 'emailTemplates']);
    Route::match(['get', 'post'], '/settings/email-templates/create', [AdminController::class, 'emailTemplateEdit']);
    Route::match(['get', 'post'], '/settings/email-templates/{id}', [AdminController::class, 'emailTemplateEdit']);
});
