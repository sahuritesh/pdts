<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoleManagement;
use App\Http\Controllers\UserManagement;
use App\Http\Controllers\Common_controller;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\DepartmentsController;
use App\Http\Controllers\LocationsController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\ProjectWizardController;
use App\Http\Controllers\SpocTasksController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Project Delay Tracking System (PDTS) — auth, users, roles, settings.
| Add domain-specific modules under the same middleware groups.
|
*/

Route::get('/', [LoginController::class, 'adminLogin']);
Route::post('/', [LoginController::class, 'Loginsubmit']);
Route::post('/admin', [LoginController::class, 'Loginsubmit']);

Route::post('/refreshCaptcha', [LoginController::class, 'refreshCaptcha']);
Route::post('adminlogin-verification', [LoginController::class, 'Loginsubmit'])->name('adminlogin.verification');
Route::get('/logout', [LoginController::class, 'logout'])->name('do-logout');
Route::get('/admin', [LoginController::class, 'adminLogin']);

Route::get('select-role', [LoginController::class, 'selectRole'])->name('select.role')->middleware('auth');
Route::post('switch-role', [LoginController::class, 'switchRole'])->name('switch.role')->middleware('auth');

Route::group(['middleware' => ['Admin', 'SanitizePostData']], function () {
    Route::get('dashboard', [DashboardController::class, 'dashboard']);

    Route::match(array('GET', 'POST'), '/role-management/add', [RoleManagement::class, 'role_management']);
    Route::match(array('GET', 'POST'), '/role-management/edit/{id}', [RoleManagement::class, 'role_management']);
    Route::post('insert_update_roles', [RoleManagement::class, 'insert_update_roles']);
    Route::get('role-management-list', [RoleManagement::class, 'role_management_list']);
    Route::post('get_role_management_list', [RoleManagement::class, 'get_role_management_list']);

    Route::match(array('GET', 'POST'), '/departments/add', [DepartmentsController::class, 'department_form']);
    Route::match(array('GET', 'POST'), '/departments/edit/{id}', [DepartmentsController::class, 'department_form']);
    Route::post('insert_update_department', [DepartmentsController::class, 'insert_update_department']);
    Route::get('departments-list', [DepartmentsController::class, 'department_list']);
    Route::post('get_department_list', [DepartmentsController::class, 'get_department_list']);

    Route::match(array('GET', 'POST'), '/locations/add', [LocationsController::class, 'location_form']);
    Route::match(array('GET', 'POST'), '/locations/edit/{id}', [LocationsController::class, 'location_form']);
    Route::post('insert_update_location', [LocationsController::class, 'insert_update_location']);
    Route::get('locations-list', [LocationsController::class, 'location_list']);
    Route::post('get_location_list', [LocationsController::class, 'get_location_list']);
    Route::post('get_locations_by_zone', [LocationsController::class, 'get_locations_by_zone']);

    Route::get('spoc-tasks-list', [SpocTasksController::class, 'task_list']);
    Route::match(array('GET', 'POST'), '/spoc-tasks/view/{id}', [SpocTasksController::class, 'task_detail']);
    Route::post('get_spoc_task_list', [SpocTasksController::class, 'get_spoc_task_list']);

    Route::get('projects-list', [ProjectsController::class, 'project_list']);
    Route::post('get_project_list', [ProjectsController::class, 'get_project_list']);
    Route::get('my-projects-list', [ProjectsController::class, 'my_project_list']);
    Route::post('get_my_project_list', [ProjectsController::class, 'get_my_project_list']);

    Route::match(array('GET', 'POST'), '/projects/wizard/panel/delay/{projectDepartmentId}', [ProjectWizardController::class, 'delay_panel']);
    Route::match(array('GET', 'POST'), '/projects/wizard/panel/financial/{projectDepartmentId}', [ProjectWizardController::class, 'financial_panel']);
    Route::match(array('GET', 'POST'), '/projects/wizard/panel/attachments/{projectDepartmentId}', [ProjectWizardController::class, 'attachment_panel']);
    Route::match(array('GET', 'POST'), '/projects/wizard/new', [ProjectWizardController::class, 'wizard']);
    Route::match(array('GET', 'POST'), '/projects/wizard/{id}', [ProjectWizardController::class, 'wizard']);
    Route::post('save_wizard_step1', [ProjectWizardController::class, 'save_wizard_step1']);
    Route::post('save_wizard_departments', [ProjectWizardController::class, 'save_wizard_departments']);
    Route::post('save_wizard_finish', [ProjectWizardController::class, 'save_wizard_finish']);
    Route::post('update_department_status', [ProjectWizardController::class, 'update_department_status']);
    Route::post('save_project_department', [ProjectWizardController::class, 'save_project_department']);
    Route::post('get_spoc_users', [ProjectWizardController::class, 'get_spoc_users']);
    Route::post('wizard_create_spoc_user', [ProjectWizardController::class, 'wizard_create_spoc_user']);
    Route::post('wizard_save_delay', [ProjectWizardController::class, 'wizard_save_delay']);
    Route::post('wizard_save_mitigation', [ProjectWizardController::class, 'wizard_save_mitigation']);
    Route::post('wizard_save_financial', [ProjectWizardController::class, 'wizard_save_financial']);
    Route::post('wizard_save_attachment', [ProjectWizardController::class, 'wizard_save_attachment']);
});

Route::group(['prefix' => '',  'middleware' => ['Admin', 'SanitizePostData']], function () {
    Route::match(array('GET', 'POST'), '/user-management/add', [UserManagement::class, 'user_management']);
    Route::match(array('GET', 'POST'), '/user-management/edit/{id}', [UserManagement::class, 'user_management']);
    Route::post('insert_update_user', [UserManagement::class, 'insert_update_user']);
    Route::get('user-management-list', [UserManagement::class, 'user_management_list']);
    Route::post('get_user_management_list', [UserManagement::class, 'get_user_management_list']);
    Route::post('send_forgotemail', [UserManagement::class, 'send_forgotemail']);
});

Route::group(['prefix' => '',  'middleware' => ['Admin', 'SanitizePostData']], function () {
    Route::post('/unlinktheFile', [Common_controller::class, 'unlinktheFile']);
    Route::post('/changestatus', [Common_controller::class, 'changestatus']);
    Route::post('/delete-record', [Common_controller::class, 'delete_record']);
    Route::match(array('GET', 'POST'), '/fileUploadAjax', [Common_controller::class, 'fileUploadAjax']);
});

Route::get('myprofile', [ProfileController::class, 'myprofile']);
Route::post('update_profile', [ProfileController::class, 'update_profile']);
Route::get('changepassword', [ProfileController::class, 'changepassword']);
Route::post('update_password', [ProfileController::class, 'update_password']);
Route::post('getNotificationCnt', [ProfileController::class, 'getNotificationCnt']);
Route::post('getNotifications', [ProfileController::class, 'getNotifications']);
Route::post('updateNotificationStatus', [ProfileController::class, 'updateNotificationStatus']);
Route::match(array('GET', 'POST'), '/getallNotifications', [ProfileController::class, 'getallNotifications']);
Route::post('get_notification_list', [ProfileController::class, 'get_notification_list']);

Route::get('forgetpassword', [ForgotPasswordController::class, 'forgotpassword']);
Route::post('forgotemail-verification', [ForgotPasswordController::class, 'submitforgotpassword'])->name('forgotemail.verification');
Route::get('resetpassword', [ForgotPasswordController::class, 'resetpassword']);
Route::post('resetpassword_verification', [ForgotPasswordController::class, 'submitresetpassword']);

Route::group(['prefix' => '',  'middleware' => ['Admin', 'SanitizePostData']], function () {
    Route::get('get_smtpsettings', [SettingsController::class, 'get_smtpsettings']);
    Route::post('update_smtpsettings', [SettingsController::class, 'update_smtpsettings']);
    Route::get('get_razorpay_settings', [SettingsController::class, 'get_razorpay_settings']);
    Route::post('update_razorpay_mode', [SettingsController::class, 'update_razorpay_mode']);
    Route::get('settings', [SettingsController::class, 'get_settings']);
    Route::post('update_settings', [SettingsController::class, 'update_settings']);
});

Route::get('/griddesign', [Common_controller::class, 'griddesign']);

Route::match(array('GET', 'POST'), '/email_templates/add', [EmailTemplateController::class, 'email_templates']);
Route::match(array('GET', 'POST'), '/email_templates/edit/{id}', [EmailTemplateController::class, 'email_templates']);
Route::post('insert_update_email_templates', [EmailTemplateController::class, 'insert_update_email_templates']);
Route::get('/email_templates_list', [EmailTemplateController::class, 'email_templates_list']);
Route::post('get_email_templates_list', [EmailTemplateController::class, 'get_email_templates_list']);

Route::match(array('GET', 'POST'), '/ImageUpload', [Common_controller::class, 'ImageUpload']);
Route::match(array('GET', 'POST'), '/docUpload', [Common_controller::class, 'docUpload']);

Route::post('/getNotification', [ProfileController::class, 'getNotification']);
Route::get('/deleteGeneralLogs/{type}', [CronController::class, 'deleteGeneralLogs']);

Route::post('/getstatesBycountryid', [Common_controller::class, 'getstatesBycountryid']);

Route::fallback(function () {
    return view('errors.404');
});
