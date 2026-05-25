<?php

namespace App\Filament\Pages;

use App\Models\Locker;
use App\Models\LockerStatusLog;
use App\Models\Location;
use App\Models\Company;
use App\Services\LockerStatusService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class LockerMonitorPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-signal';
    protected static ?string $navigationLabel = 'Live Monitor';
    protected static ?string $title           = 'Locker Status Monitor';
    protected static ?string $slug            = 'locker-monitor';
    protected static ?int    $navigationSort  = 1;
    protected static string  $view            = 'filament.pages.locker-monitor';

    public string      $filterStatus   = '';
    public string|int  $filterLocation = '';
    public string|int  $filterCompany  = '';

    public ?string $updateLockerId     = null;
    public string  $updateNewStatus    = '';
    public string  $updateReason       = '';

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->can('view lockers');
    }

    public function mount(): void
    {
        $this->updateNewStatus = Locker::STATUS_AVAILABLE;
    }

    public function getLockers(): Collection
    {
        /** @var \App\Services\LockerStatusService $service */
        $service = app(LockerStatusService::class);

        return $service->getFilteredQuery(
            user:       auth()->user(),
            locationId: $this->filterLocation !== '' ? (int) $this->filterLocation : null,
            status:     $this->filterStatus !== '' ? $this->filterStatus : null,
            companyId:  $this->filterCompany !== '' ? (int) $this->filterCompany : null,
        )
        ->with(['company', 'location', 'statusLogs' => fn($q) => $q->latestFirst()->with('changedBy')->limit(1)])
        ->get();
    }

    public function getLocations(): Collection
    {
        $user = auth()->user();
        $query = Location::query()->orderBy('name');
        if (!$user->isSuperAdmin()) {
            $query->where('company_id', $user->company_id);
        }
        return $query->get();
    }

    public function getCompanies(): Collection
    {
        if (!auth()->user()->isSuperAdmin()) {
            return collect();
        }
        return Company::orderBy('name')->get();
    }

    public function getRecentLogs(): Collection
    {
        $user  = auth()->user();
        $query = LockerStatusLog::with(['locker', 'changedBy'])->latestFirst()->limit(50);

        if (!$user->isSuperAdmin()) {
            $query->forCompany($user->company_id);
        }

        return $query->get();
    }

    public function getStatusCounts(): array
    {
        $lockers = $this->getLockers();
        return [
            'available' => $lockers->where('status', Locker::STATUS_AVAILABLE)->count(),
            'in_use'    => $lockers->where('status', Locker::STATUS_IN_USE)->count(),
            'fault'     => $lockers->where('status', Locker::STATUS_FAULT)->count(),
            'offline'   => $lockers->where('status', Locker::STATUS_OFFLINE)->count(),
            'disabled'  => $lockers->where('status', Locker::STATUS_DISABLED)->count(),
        ];
    }

    public function openUpdateModal(int $lockerId): void
    {
        $this->updateLockerId  = (string) $lockerId;
        $this->updateNewStatus = Locker::STATUS_AVAILABLE;
        $this->updateReason    = '';
        $this->dispatch('open-modal', id: 'update-status-modal');
    }

    public function submitStatusUpdate(): void
    {
        if (!auth()->user()->can('edit lockers')) {
            Notification::make()->title('ไม่มีสิทธิ์')->danger()->send();
            return;
        }

        $locker = Locker::findOrFail((int) $this->updateLockerId);

        try {
            app(LockerStatusService::class)->updateStatus(
                locker:    $locker,
                newStatus: $this->updateNewStatus,
                changedBy: auth()->user(),
                reason:    $this->updateReason ?: null,
            );

            Notification::make()
                ->title('อัปเดตสถานะสำเร็จ')
                ->body("{$locker->name} → " . Locker::statusOptions()[$this->updateNewStatus])
                ->success()
                ->send();

            $this->dispatch('close-modal', id: 'update-status-modal');
            $this->updateLockerId = null;
        } catch (\Exception $e) {
            Notification::make()->title('เกิดข้อผิดพลาด')->body($e->getMessage())->danger()->send();
        }
    }
}
