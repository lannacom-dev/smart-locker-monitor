<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Models\SystemAlert;
use App\Models\SystemHealthCheck;
use App\Services\SystemHealthService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class SystemHealthPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-heart';
    protected static ?string $navigationLabel = 'System Health';
    protected static ?string $title           = 'System Health Dashboard';
    protected static ?string $slug            = 'system-health';
    protected static ?string $navigationGroup = 'Monitoring';
    protected static ?int    $navigationSort  = 3;
    protected static string  $view            = 'filament.pages.system-health';

    // ── Filter state ──────────────────────────────────────────────
    public string|int $filterCompany       = '';
    public string     $filterAlertStatus   = 'open';
    public string     $filterAlertSeverity = '';

    // ── Acknowledge modal state ───────────────────────────────────
    public ?int    $acknowledgeAlertId   = null;
    public string  $acknowledgeNote      = '';

    // ── Access control ────────────────────────────────────────────

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->can('view system health');
    }

    // ── Data methods ──────────────────────────────────────────────

    /** Overall system health: healthy | warning | critical | unknown */
    public function getOverallStatus(): string
    {
        return app(SystemHealthService::class)
            ->getOverallStatus(auth()->user(), $this->selectedCompanyId());
    }

    /** Latest health checks (device / connection / API) grouped by type */
    public function getHealthChecks(): Collection
    {
        return app(SystemHealthService::class)
            ->getLatestChecks(auth()->user(), $this->selectedCompanyId());
    }

    /** Open alert counts by severity */
    public function getIssueSummary(): array
    {
        return app(SystemHealthService::class)
            ->getIssueSummary(auth()->user(), $this->selectedCompanyId());
    }

    /** Alert list (paginated collection for blade) */
    public function getAlerts(): Collection
    {
        return app(SystemHealthService::class)
            ->getAlertsQuery(
                user:            auth()->user(),
                filterCompanyId: $this->selectedCompanyId(),
                filterStatus:    $this->filterAlertStatus  ?: null,
                filterSeverity:  $this->filterAlertSeverity ?: null,
            )
            ->limit(100)
            ->get();
    }

    /** Companies list for super-admin filter */
    public function getCompanies(): Collection
    {
        return auth()->user()->isSuperAdmin()
            ? Company::orderBy('name')->get()
            : collect();
    }

    /** When the last health check was run */
    public function getLastCheckedAt(): ?string
    {
        $latest = \App\Models\SystemHealthCheck::query()
            ->orderByDesc('checked_at')
            ->value('checked_at');

        return $latest ? \Carbon\Carbon::parse($latest)->diffForHumans() : null;
    }

    // ── Acknowledge alert ─────────────────────────────────────────

    public function openAcknowledgeModal(int $alertId): void
    {
        if (! auth()->user()->can('acknowledge alerts')) {
            Notification::make()->title('ไม่มีสิทธิ์')->danger()->send();
            return;
        }

        $this->acknowledgeAlertId = $alertId;
        $this->acknowledgeNote    = '';
        $this->dispatch('open-modal', id: 'acknowledge-modal');
    }

    public function submitAcknowledge(): void
    {
        if (! auth()->user()->can('acknowledge alerts')) {
            Notification::make()->title('ไม่มีสิทธิ์')->danger()->send();
            return;
        }

        $alert = SystemAlert::find($this->acknowledgeAlertId);

        if (! $alert) {
            Notification::make()->title('ไม่พบ Alert')->danger()->send();
            return;
        }

        // Authorization: tenant admin can only acknowledge alerts for their scope
        if (! auth()->user()->isSuperAdmin()) {
            $ids = auth()->user()->accessibleCompanyIds();
            if ($alert->company_id !== null && ! in_array($alert->company_id, $ids, true)) {
                Notification::make()->title('ไม่มีสิทธิ์เข้าถึง Alert นี้')->danger()->send();
                return;
            }
        }

        app(SystemHealthService::class)->acknowledge(
            alert: $alert,
            user:  auth()->user(),
            note:  $this->acknowledgeNote ?: null,
        );

        Notification::make()
            ->title('รับทราบ Alert แล้ว')
            ->body($alert->title)
            ->success()
            ->send();

        $this->dispatch('close-modal', id: 'acknowledge-modal');
        $this->acknowledgeAlertId = null;
    }

    // ── Internal ──────────────────────────────────────────────────

    private function selectedCompanyId(): ?int
    {
        return $this->filterCompany !== '' ? (int) $this->filterCompany : null;
    }
}
