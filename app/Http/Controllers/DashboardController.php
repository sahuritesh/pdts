<?php

namespace App\Http\Controllers;

use App\Models\Common_model;
use App\Services\DashboardAnalyticsService;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;

class DashboardController extends Controller
{
    protected DashboardAnalyticsService $dashboardAnalytics;

    public function __construct(DashboardAnalyticsService $dashboardAnalytics)
    {
        $this->dashboardAnalytics = $dashboardAnalytics;
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
        $data['show_module3'] = RoleManagement::dashboardModuleHasWidgets($widgets, 3);
        $data['has_dashboard_widgets'] = RoleManagement::dashboardHasAnyWidget($widgets);

        if ($data['has_dashboard_widgets']) {
            $data['analytics'] = $this->dashboardAnalytics->getDashboardAnalytics($widgets);
        }

        return response()->view('dashboard.dashboard', compact(
            'pageTitle',
            'data'
        ))->header('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
    }
}
