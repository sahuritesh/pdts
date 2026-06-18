<?php

namespace App\Http\Controllers;

use App\Models\Common_model;
use App\Services\DashboardAnalyticsService;
use App\Services\DashboardDrilldownService;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;

class DashboardController extends Controller
{
    protected DashboardAnalyticsService $dashboardAnalytics;
    protected DashboardDrilldownService $dashboardDrilldown;

    public function __construct(
        DashboardAnalyticsService $dashboardAnalytics,
        DashboardDrilldownService $dashboardDrilldown
    ) {
        $this->dashboardAnalytics = $dashboardAnalytics;
        $this->dashboardDrilldown = $dashboardDrilldown;
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
            $zoneId = $request->query('zone_id');
            $zoneId = ($zoneId !== null && $zoneId !== '' && $zoneId !== 'all') ? (int) $zoneId : null;
            $data['selected_zone_id'] = $zoneId;
            $data['zones'] = $this->getZones();
            $analytics = $this->dashboardAnalytics->getDashboardAnalytics($widgets, $zoneId);
            $data['analytics'] = $this->dashboardDrilldown->attachChartDrillUrls($analytics, $zoneId);
            $data['drill_links'] = $this->dashboardDrilldown->buildKpiLinks($zoneId);
        }

        return response()->view('dashboard.dashboard', compact(
            'pageTitle',
            'data'
        ))->header('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
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
}
