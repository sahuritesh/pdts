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
use App\Http\Controllers\DelayCategoriesController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\DelayRegistersController;
use App\Http\Controllers\DelayMitigationsController;
use App\Http\Controllers\DelayFinancialImpactsController;
use App\Http\Controllers\DelayAttachmentsController;
use App\Http\Controllers\RenovationProjectsController;

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

    Route::match(array('GET', 'POST'), '/delay-categories/add', [DelayCategoriesController::class, 'delay_category_form']);
    Route::match(array('GET', 'POST'), '/delay-categories/edit/{id}', [DelayCategoriesController::class, 'delay_category_form']);
    Route::post('insert_update_delay_category', [DelayCategoriesController::class, 'insert_update_delay_category']);
    Route::get('delay-categories-list', [DelayCategoriesController::class, 'delay_category_list']);
    Route::post('get_delay_category_list', [DelayCategoriesController::class, 'get_delay_category_list']);

    Route::match(array('GET', 'POST'), '/projects/add', [ProjectsController::class, 'project_form']);
    Route::match(array('GET', 'POST'), '/projects/edit/{id}', [ProjectsController::class, 'project_form']);
    Route::post('insert_update_project', [ProjectsController::class, 'insert_update_project']);
    Route::get('projects-list', [ProjectsController::class, 'project_list']);
    Route::post('get_project_list', [ProjectsController::class, 'get_project_list']);

    Route::match(array('GET', 'POST'), '/delay-registers/add', [DelayRegistersController::class, 'delay_register_form']);
    Route::match(array('GET', 'POST'), '/delay-registers/edit/{id}', [DelayRegistersController::class, 'delay_register_form']);
    Route::post('insert_update_delay_register', [DelayRegistersController::class, 'insert_update_delay_register']);
    Route::get('delay-registers-list', [DelayRegistersController::class, 'delay_register_list']);
    Route::post('get_delay_register_list', [DelayRegistersController::class, 'get_delay_register_list']);

    Route::match(array('GET', 'POST'), '/delay-mitigations/add', [DelayMitigationsController::class, 'mitigation_add']);
    Route::match(array('GET', 'POST'), '/delay-mitigations/add/{delayRegisterId}', [DelayMitigationsController::class, 'mitigation_add']);
    Route::match(array('GET', 'POST'), '/delay-mitigations/edit/{id}', [DelayMitigationsController::class, 'mitigation_edit']);
    Route::match(array('GET', 'POST'), '/delay-mitigations/panel/{delayRegisterId}', [DelayMitigationsController::class, 'mitigation_panel']);
    Route::post('insert_update_mitigation', [DelayMitigationsController::class, 'insert_update_mitigation']);
    Route::get('delay-mitigations-list/{delayRegisterId?}', [DelayMitigationsController::class, 'mitigation_list']);
    Route::post('get_delay_mitigation_list', [DelayMitigationsController::class, 'get_delay_mitigation_list']);

    Route::match(array('GET', 'POST'), '/delay-financial-impacts/add', [DelayFinancialImpactsController::class, 'financial_impact_add']);
    Route::match(array('GET', 'POST'), '/delay-financial-impacts/add/{delayRegisterId}', [DelayFinancialImpactsController::class, 'financial_impact_add']);
    Route::match(array('GET', 'POST'), '/delay-financial-impacts/edit/{id}', [DelayFinancialImpactsController::class, 'financial_impact_edit']);
    Route::match(array('GET', 'POST'), '/delay-financial-impacts/panel/{delayRegisterId}', [DelayFinancialImpactsController::class, 'financial_impact_panel']);
    Route::post('insert_update_financial_impact', [DelayFinancialImpactsController::class, 'insert_update_financial_impact']);
    Route::get('delay-financial-impacts-list/{delayRegisterId?}', [DelayFinancialImpactsController::class, 'financial_impact_list']);
    Route::post('get_delay_financial_impact_list', [DelayFinancialImpactsController::class, 'get_delay_financial_impact_list']);

    Route::match(array('GET', 'POST'), '/delay-attachments/add', [DelayAttachmentsController::class, 'attachment_add']);
    Route::match(array('GET', 'POST'), '/delay-attachments/add/{delayRegisterId}', [DelayAttachmentsController::class, 'attachment_add']);
    Route::match(array('GET', 'POST'), '/delay-attachments/edit/{id}', [DelayAttachmentsController::class, 'attachment_edit']);
    Route::match(array('GET', 'POST'), '/delay-attachments/panel/{delayRegisterId}', [DelayAttachmentsController::class, 'attachment_panel']);
    Route::post('insert_update_delay_attachment', [DelayAttachmentsController::class, 'insert_update_delay_attachment']);
    Route::get('delay-attachments-list/{delayRegisterId?}', [DelayAttachmentsController::class, 'attachment_list']);
    Route::post('get_delay_attachment_list', [DelayAttachmentsController::class, 'get_delay_attachment_list']);

    Route::match(array('GET', 'POST'), '/renovation-projects/add', [RenovationProjectsController::class, 'renovation_project_form']);
    Route::match(array('GET', 'POST'), '/renovation-projects/edit/{id}', [RenovationProjectsController::class, 'renovation_project_form']);
    Route::post('insert_update_renovation_project', [RenovationProjectsController::class, 'insert_update_renovation_project']);
    Route::get('renovation-projects-list', [RenovationProjectsController::class, 'renovation_project_list']);
    Route::post('get_renovation_project_list', [RenovationProjectsController::class, 'get_renovation_project_list']);
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
