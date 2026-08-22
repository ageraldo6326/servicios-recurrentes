<?php

use App\Http\Controllers\BeneficiaryController;
use App\Http\Controllers\CatalogServiceController;
use App\Http\Controllers\ChargeController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CommercialDashboardController;
use App\Http\Controllers\CommercialInvoiceController;
use App\Http\Controllers\CommercialQuoteController;
use App\Http\Controllers\CompanySettingController;
use App\Http\Controllers\ContractedServiceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinancialCommitmentController;
use App\Http\Controllers\GestionController;
use App\Http\Controllers\NotebookAttachmentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\ProviderInvoiceController;
use App\Http\Controllers\SidebarMenuOrderController;
use App\Livewire\Actions\Logout;
use App\Livewire\Breaks\Dashboard as BreaksDashboard;
use App\Livewire\CatalogServices\Index as CatalogServicesIndex;
use App\Livewire\Charges\Index as ChargesIndex;
use App\Livewire\Clients\Index as ClientsIndex;
use App\Livewire\ContractedServices\Index as ContractedServicesIndex;
use App\Livewire\Dashboard\FollowUp;
use App\Livewire\FinancialAgenda\Beneficiaries\Index as BeneficiariesIndex;
use App\Livewire\FinancialAgenda\Commitments\Index as CommitmentsIndex;
use App\Livewire\FinancialAgenda\CreditCardsDashboard;
use App\Livewire\FinancialAgenda\Dashboard as FinancialAgendaDashboard;
use App\Livewire\Gestiones\Index as GestionesIndex;
use App\Livewire\Notebooks\Workspace as NotebooksWorkspace;
use App\Livewire\Providers\Index as ProvidersIndex;
use App\Livewire\Tasks\Index as TasksIndex;
use App\Livewire\UnplannedExpenses\Dashboard as UnplannedExpensesDashboard;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware(['auth'])->group(function (): void {
    Route::get('/dashboard', FollowUp::class)->name('dashboard');
    Route::get('/dashboard/seguimiento', FollowUp::class)->name('dashboard.follow-up');
    Route::get('/dashboard/operativo', [DashboardController::class, 'operational'])->name('dashboard.operational');
    Route::get('/dashboard/ejecutivo', [DashboardController::class, 'executive'])->name('dashboard.executive');

    Route::get('clients', ClientsIndex::class)->name('clients.index');
    Route::get('cuadernos', NotebooksWorkspace::class)->name('notebooks.index');
    Route::get('notebooks/attachments/{attachment}', [NotebookAttachmentController::class, 'show'])->name('notebooks.attachments.show');
    Route::resource('clients', ClientController::class)->except(['show', 'destroy', 'index']);
    Route::get('catalog-services', CatalogServicesIndex::class)->name('catalog-services.index');
    Route::resource('catalog-services', CatalogServiceController::class)->except(['show', 'destroy', 'index']);
    Route::get('providers', ProvidersIndex::class)->name('providers.index');
    Route::resource('providers', ProviderController::class)->except(['show', 'destroy', 'index']);
    Route::get('contracted-services', ContractedServicesIndex::class)->name('contracted-services.index');
    Route::resource('contracted-services', ContractedServiceController::class)->except(['show', 'index']);
    Route::post('contracted-services/{contractedService}/cancel', [ContractedServiceController::class, 'cancel'])->name('contracted-services.cancel');
    Route::post('contracted-services/{contractedService}/mark-paid', [ContractedServiceController::class, 'markAsPaid'])->name('contracted-services.mark-paid');
    Route::get('charges', ChargesIndex::class)->name('charges.index');
    Route::resource('charges', ChargeController::class)->only(['create', 'store']);
    Route::resource('payments', PaymentController::class)->only(['index', 'create', 'store']);
    Route::post('payments/{payment}/validate', [PaymentController::class, 'validatePayment'])->name('payments.validate');
    Route::get('gestions', GestionesIndex::class)->name('gestions.index');
    Route::get('descansos', BreaksDashboard::class)->name('breaks.dashboard');
    Route::get('agenda', TasksIndex::class)->name('tasks.index');
    Route::resource('gestions', GestionController::class)->only(['create', 'store']);
    Route::resource('provider-invoices', ProviderInvoiceController::class)->only(['index', 'create', 'store']);
    Route::post('logout', function (Logout $logout) {
        $logout();

        return redirect()->route('login');
    })->name('logout');

    Route::prefix('comercial')->name('commercial.')->group(function (): void {
        Route::get('/', CommercialDashboardController::class)->name('dashboard');
        Route::get('cotizaciones', [CommercialQuoteController::class, 'index'])->name('quotes.index');
        Route::get('cotizaciones/crear', [CommercialQuoteController::class, 'create'])->name('quotes.create');
        Route::post('cotizaciones', [CommercialQuoteController::class, 'store'])->name('quotes.store');
        Route::get('cotizaciones/{quote}/editar', [CommercialQuoteController::class, 'edit'])->name('quotes.edit');
        Route::put('cotizaciones/{quote}', [CommercialQuoteController::class, 'update'])->name('quotes.update');
        Route::post('cotizaciones/{quote}/duplicar', [CommercialQuoteController::class, 'duplicate'])->name('quotes.duplicate');
        Route::post('cotizaciones/{quote}/convertir', [CommercialQuoteController::class, 'convert'])->name('quotes.convert');
        Route::get('cotizaciones/{quote}/pdf', [CommercialQuoteController::class, 'pdf'])->name('quotes.pdf');
        Route::post('cotizaciones/{quote}/email', [CommercialQuoteController::class, 'email'])->name('quotes.email');
        Route::get('facturas', [CommercialInvoiceController::class, 'index'])->name('invoices.index');
        Route::get('gastos-hormiga', UnplannedExpensesDashboard::class)->name('unplanned-expenses.dashboard');
        Route::get('facturas/crear', [CommercialInvoiceController::class, 'create'])->name('invoices.create');
        Route::post('facturas', [CommercialInvoiceController::class, 'store'])->name('invoices.store');
        Route::get('facturas/{invoice}/editar', [CommercialInvoiceController::class, 'edit'])->name('invoices.edit');
        Route::put('facturas/{invoice}', [CommercialInvoiceController::class, 'update'])->name('invoices.update');
        Route::post('facturas/{invoice}/duplicar', [CommercialInvoiceController::class, 'duplicate'])->name('invoices.duplicate');
        Route::get('facturas/{invoice}/pdf', [CommercialInvoiceController::class, 'pdf'])->name('invoices.pdf');
        Route::post('facturas/{invoice}/email', [CommercialInvoiceController::class, 'email'])->name('invoices.email');
    });

    Route::get('configuracion/empresa', [CompanySettingController::class, 'edit'])->name('settings.company.edit');
    Route::put('configuracion/empresa', [CompanySettingController::class, 'update'])->name('settings.company.update');
    Route::get('configuracion/empresa/logo', [CompanySettingController::class, 'logo'])->name('settings.company.logo');
    Route::put('configuracion/menu/orden', [SidebarMenuOrderController::class, 'update'])->name('settings.sidebar-menu-order.update');

    Route::prefix('financial-agenda')->name('financial-agenda.')->group(function (): void {
        Route::get('/', FinancialAgendaDashboard::class)->name('index');
        Route::get('tarjetas', CreditCardsDashboard::class)->name('cards.dashboard');
        Route::get('beneficiaries', BeneficiariesIndex::class)->name('beneficiaries.index');
        Route::resource('beneficiaries', BeneficiaryController::class)->except(['index', 'show', 'destroy']);
        Route::get('commitments', CommitmentsIndex::class)->name('commitments.index');
        Route::post('commitments/{commitment}/cancel', [FinancialCommitmentController::class, 'cancel'])->name('commitments.cancel');
        Route::resource('commitments', FinancialCommitmentController::class)->except(['index', 'show', 'destroy']);
    });
});

Route::view('profile', 'profile')->middleware(['auth'])->name('profile');

require __DIR__.'/auth.php';
