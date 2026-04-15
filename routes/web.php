<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\TicketController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

// ─── Public ───
Route::get('/', fn() => redirect('/login'));

// ─── Auth ───
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ─── Stripe Webhook (no auth, no CSRF) ───
Route::post('/stripe/webhook', [PaymentController::class, 'stripeWebhook'])->name('stripe.webhook');

// ─── Customer Portal ───
Route::middleware('auth')->group(function () {
    Route::get('/portal', [DashboardController::class, 'index'])->name('portal');
    Route::match(['get', 'post'], '/portal/profile', [DashboardController::class, 'profile']);
    Route::match(['get', 'post'], '/portal/change-password', [DashboardController::class, 'changePassword']);
    Route::get('/portal/plans', [DashboardController::class, 'plans']);
    Route::get('/portal/subscribe/{plan}', [DashboardController::class, 'subscribe']);
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

    // Support tickets
    Route::get('/support/tickets', [TicketController::class, 'index']);
    Route::match(['get', 'post'], '/support/tickets/create', [TicketController::class, 'create']);
    Route::match(['get', 'post'], '/support/tickets/{ticket}', [TicketController::class, 'show']);

    // Web player
    Route::get('/player', function () {
        $user = \Illuminate\Support\Facades\Auth::user();
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
    Route::post('/users/{id}/suspend', [AdminController::class, 'userSuspend']);
    Route::post('/users/{id}/reactivate', [AdminController::class, 'userReactivate']);
    Route::post('/users/{id}/delete', [AdminController::class, 'userDelete']);
    Route::post('/users/{id}/wallet-adjust', [AdminController::class, 'walletAdjust']);

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

    // Tickets
    Route::get('/tickets', [AdminController::class, 'tickets']);
    Route::match(['get', 'post'], '/tickets/{id}', [AdminController::class, 'ticketDetail']);

    // Settings
    Route::match(['get', 'post'], '/settings/smtp', [AdminController::class, 'smtpConfig']);
    Route::get('/settings/email-templates', [AdminController::class, 'emailTemplates']);
    Route::match(['get', 'post'], '/settings/email-templates/create', [AdminController::class, 'emailTemplateEdit']);
    Route::match(['get', 'post'], '/settings/email-templates/{id}', [AdminController::class, 'emailTemplateEdit']);
});
