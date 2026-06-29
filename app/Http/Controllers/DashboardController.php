<?php

namespace App\Http\Controllers;

use App\Models\Common_model;
use App\Services\DashboardAnalyticsService;
use App\Services\DashboardDrilldownService;
use App\Services\UserScopeService;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;

class DashboardController extends Controller
{
    protected DashboardAnalyticsService $dashboardAnalytics;
    protected DashboardDrilldownService $dashboardDrilldown;
    protected UserScopeService $userScope;

    public function __construct(
        DashboardAnalyticsService $dashboardAnalytics,
        DashboardDrilldownService $dashboardDrilldown,
        UserScopeService $userScope
    ) {
        $this->dashboardAnalytics = $dashboardAnalytics;
        $this->dashboardDrilldown = $dashboardDrilldown;
        $this->userScope = $userScope;
    }

    public function dashboard(Request $request)
    {
        reloadCurrentUserPermissions();

        if (permissionexists('dashboard_view') != '1') {
            return redirect(getProjectUrl('admin'))
                ->with('error', 'You do not have permission to view the dashboard. Contact your administrator.');
        }

        $user = auth()->user();
        if (!$user) {
            $pageTitle = 'Login';
            $url = getbaseUrl();

            return Redirect::to($url)->with(Auth::logout());
        }

        $effectiveRoleId = (int) Session::get('effective_role_id', $user->user_type);
        $data['user_type'] = $effectiveRoleId;

        $roles = Common_model::getDataFromTable(
            'tbl_roles',
            ['role_name', 'role_description'],
            ['id' => $effectiveRoleId],
            '',
            '',
            'ASC',
            '',
            0,
            true,
            ''
        );

        if (!is_array($roles) || count($roles) === 0) {
            $pageTitle = 'Dashboard';

            return redirect()->route('do-logout');
        }

        $data['pageHeading'] = 'Dashboard';
        $data['pageSubHeading'] = 'Application overview';
        $data['tableHeading'] = '';
        $pageTitle = 'Dashboard';
        $data['role_name'] = $roles[0]['role_name'];
        $data['user_name'] = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? 'User');
        $data['last_logged_on'] = $user->last_logged_on;

        $data['total_users'] = (int) (Common_model::countResult(
            'tbl_user',
            'status',
            ACTIVE
        ) ?: 0);

        $data['active_roles'] = (int) (Common_model::countResult(
            'tbl_roles',
            'status',
            ACTIVE
        ) ?: 0);

        $widgets = RoleManagement::resolveDashboardWidgets();
        $data['widgets'] = $widgets;
        $data['show_module1'] = RoleManagement::dashboardModuleHasWidgets($widgets, 1);
        $data['show_module3'] = false;
        $data['has_dashboard_widgets'] = RoleManagement::dashboardHasAnyWidget($widgets);

        if ($data['has_dashboard_widgets']) {
            $filters = $this->parseDashboardFilters($request);
            $data = array_merge($data, $this->buildDashboardFilterViewData($filters));
            $data['analytics'] = $this->loadDashboardAnalytics($widgets, $filters, ['all'], false);
            $data['drill_links'] = $this->dashboardDrilldown->buildKpiLinks(
                $filters['zone_id'],
                $filters['project_id']
            );
        }

        return response()->view('dashboard.dashboard', compact(
            'pageTitle',
            'data'
        ))->header('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
    }

    public function dashboardAnalytics(Request $request)
    {
        if (permissionexists('dashboard_view') != '1') {
            return response()->json([
                'error' => 1,
                'message' => 'You do not have permission to view the dashboard.',
            ]);
        }

        $widgets = RoleManagement::resolveDashboardWidgets();
        if (!RoleManagement::dashboardHasAnyWidget($widgets)) {
            return response()->json([
                'error' => 1,
                'message' => 'No dashboard widgets are enabled for your role.',
            ]);
        }

        $filters = $this->parseDashboardFilters($request);
        $viewData = $this->buildDashboardFilterViewData($filters);
        $sections = $this->parseDashboardSections($request);
        $analytics = $this->loadDashboardAnalytics($widgets, $filters, $sections, true);

        return response()->json([
            'error' => 0,
            'message' => 'Dashboard data loaded.',
            'filters' => [
                'zone_id' => $filters['zone_id'],
                'project_id' => $filters['project_id'],
                'date_from' => $filters['date_from'],
                'date_to' => $filters['date_to'],
                'date_preset' => $filters['date_preset'],
            ],
            'projects' => $viewData['projects'],
            'show_zone_chart' => empty($filters['project_id']),
            'analytics' => $analytics,
            'drill_links' => $this->dashboardDrilldown->buildKpiLinks(
                $filters['zone_id'],
                $filters['project_id']
            ),
        ]);
    }

    /**
     * @param  array<string, bool>  $widgets
     * @param  array<string, mixed>  $filters
     * @param  string[]  $sections
     */
    private function loadDashboardAnalytics(
        array $widgets,
        array $filters,
        array $sections = ['all'],
        bool $useCache = false
    ): array {
        $loader = function () use ($widgets, $filters, $sections) {
            $analytics = $this->dashboardAnalytics->getDashboardAnalytics(
                $widgets,
                $filters['zone_id'],
                $filters['project_id'],
                $filters['date_from'],
                $filters['date_to'],
                $sections
            );

            return $this->dashboardDrilldown->attachChartDrillUrls(
                $analytics,
                $filters['zone_id'],
                $filters['project_id']
            );
        };

        if (!$useCache) {
            return $loader();
        }

        return Cache::remember(
            $this->buildDashboardCacheKey($widgets, $filters, $sections),
            60,
            $loader
        );
    }

    /**
     * @param  array<string, bool>  $widgets
     * @param  array<string, mixed>  $filters
     * @param  string[]  $sections
     */
    private function buildDashboardCacheKey(array $widgets, array $filters, array $sections): string
    {
        $enabledWidgets = array_keys(array_filter($widgets));
        sort($enabledWidgets);
        sort($sections);

        $payload = [
            'user_id' => auth()->id() ?? 0,
            'role_id' => (int) Session::get('effective_role_id', 0),
            'widgets' => $enabledWidgets,
            'sections' => $sections,
            'zone_id' => $filters['zone_id'],
            'project_id' => $filters['project_id'],
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
            'date_preset' => $filters['date_preset'],
        ];

        return 'pdts_dashboard:' . md5(json_encode($payload));
    }

    /**
     * @return string[]
     */
    private function parseDashboardSections(Request $request): array
    {
        $raw = trim((string) $request->query('sections', 'all'));
        if ($raw === '' || $raw === 'all') {
            return ['all'];
        }

        $allowed = ['kpis', 'charts', 'tables', 'zone'];
        $sections = array_values(array_unique(array_filter(array_map(
            'trim',
            explode(',', $raw)
        ))));
        $sections = array_values(array_intersect($sections, $allowed));

        return $sections === [] ? ['all'] : $sections;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDashboardFilterViewData(array $filters): array
    {
        $projects = $this->getProjectsForDashboard($filters['zone_id']);
        $projectId = $filters['project_id'];

        if ($projectId && !collect($projects)->contains(fn ($p) => (int) $p['id'] === $projectId)) {
            $projectId = null;
        }

        return [
            'selected_zone_id' => $filters['zone_id'],
            'selected_project_id' => $projectId,
            'selected_date_from' => $filters['date_from'],
            'selected_date_to' => $filters['date_to'],
            'selected_date_preset' => $filters['date_preset'],
            'zones' => $this->getZones(),
            'projects' => $projects,
        ];
    }

    /**
     * @return array{
     *     zone_id: ?int,
     *     project_id: ?int,
     *     date_from: ?string,
     *     date_to: ?string,
     *     date_preset: string
     * }
     */
    private function parseDashboardFilters(Request $request): array
    {
        $zoneId = $request->query('zone_id');
        $zoneId = ($zoneId !== null && $zoneId !== '' && $zoneId !== 'all') ? (int) $zoneId : null;

        $projectId = $request->query('project_id');
        $projectId = ($projectId !== null && $projectId !== '' && $projectId !== 'all') ? (int) $projectId : null;

        $datePreset = trim((string) $request->query('date_preset', 'all'));
        if (!in_array($datePreset, ['all', '7d', '30d', '90d', '180d', 'custom'], true)) {
            $datePreset = 'all';
        }

        $dateFrom = $this->parseDashboardDate($request->query('date_from'));
        $dateTo = $this->parseDashboardDate($request->query('date_to'));

        if ($datePreset !== 'custom' && $datePreset !== 'all') {
            [$dateFrom, $dateTo] = $this->resolveDatePresetRange($datePreset);
        }

        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        if ($datePreset === 'all') {
            $dateFrom = null;
            $dateTo = null;
        }

        return [
            'zone_id' => $zoneId,
            'project_id' => $projectId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'date_preset' => $datePreset,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveDatePresetRange(string $preset): array
    {
        $to = now()->toDateString();
        $from = match ($preset) {
            '7d' => now()->subDays(6)->toDateString(),
            '30d' => now()->subDays(29)->toDateString(),
            '90d' => now()->subDays(89)->toDateString(),
            '180d' => now()->subDays(179)->toDateString(),
            default => now()->subDays(29)->toDateString(),
        };

        return [$from, $to];
    }

    private function parseDashboardDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string) $value);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $parsed = \DateTime::createFromFormat('d/m/Y', $value)
                ?: \DateTime::createFromFormat('d-m-Y', $value)
                ?: \DateTime::createFromFormat('m/d/Y', $value);
            if (!$parsed) {
                return null;
            }
            return $parsed->format('Y-m-d');
        }

        return $value;
    }

    private function getZones(): array
    {
        if (!Schema::hasTable('tbl_zones')) {
            return [];
        }

        return DB::table('tbl_zones')
            ->where('is_delete', 0)
            ->where('status', 1)
            ->orderBy('zone_name')
            ->get(['id', 'zone_name'])
            ->map(fn ($r) => ['id' => $r->id, 'label' => $r->zone_name])
            ->all();
    }

    private function getProjectsForDashboard(?int $zoneId): array
    {
        if (!Schema::hasTable('tbl_projects')) {
            return [];
        }

        $query = DB::table('tbl_projects as tp')
            ->where('tp.is_delete', 0)
            ->orderBy('tp.project_code')
            ->orderBy('tp.project_name');

        if ($zoneId) {
            $query->where('tp.zone_id', $zoneId);
        }

        $this->userScope->applyProjectScope($query, 'tp');

        return $query
            ->get(['tp.id', 'tp.project_code', 'tp.project_name'])
            ->map(function ($row) {
                $code = trim((string) ($row->project_code ?? ''));
                $name = trim((string) ($row->project_name ?? ''));

                return [
                    'id' => (int) $row->id,
                    'label' => $code !== '' ? $code . ' — ' . $name : $name,
                ];
            })
            ->all();
    }
}
