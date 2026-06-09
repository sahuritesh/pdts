<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
        
        'login',
        'userRegister',
        'userLogin',        
        'changeMasterDataStatus',
        'updateSortOrder',
        'fileUploadAjax',
        'ImageUpload',
        'docUpload',
        'resetpassword_verification',
        'refreshCaptcha',
        'get_role_management_list',
        'get_membership_registrations_list',
        'get_user_management_list',
        'get_membership_registrations_list',
        'get_payment_transactions_list',
        'get_donations_list',
        'get_speakers_list',
        'get_exhibitors_list',
        'get_pending_questions_list',
        'get_session_questions_list',
        'get_approved_questions_list',
        'get_attendance_list',
        'get_exhibitionusers_list',
        'get_videos_list',
        'get_cms_pages_list',
        'get_cms_media_list',
        'get_cms_sponser_list',
        'get_cms_blog_list',
        'get_cms_accommodation_list',
        'get_categories_list',
        'get_tickets_list',
        'get_countries_list',
        'get_states_list',
        'get_coupons_list',
        'get_qualifications_list',
        'get_specializations_list',
        'get_halls_list',
        'get_topics_list',
        'get_sessiontype_list',
        'get_speakers_list',
        'get_guest_users_list',
        'get_attendance_list',
        'get_exhibitionusers_list',
        'get_videos_list',
        'get_cms_pages_list',
        'get_cms_media_list',
        'get_cms_sponser_list',
        'get_cms_blog_list',
        'get_cms_accommodation_list',
        'get_agenda_components_list',
        'get_cms_blog_categories_list',
        'get_cms_templates_list',
        'get_cms_menus_list',
        'get_hear_about_sources_list',
        'get_workshops_list',
        'get_events_list',
        'get_modules_list',
        'get_event_mappings_list',
        'get_conference_registrations_list',
        'get_membership_registrations_list',
        'get_payment_transactions_list',
        'get_donations_list',
        'get_feedback_sections_list',
        'get_feedback_questions_list',
        'get_feedback_forms_list',
        'get_feedback_submissions_list',
        'get_photo_booth_frames_list',
        'get_photo_booth_uploads_list',
        'get_free_surgery_applications_list',
        'get_user_management_list',
        'get_email_templates_list'
    ];
}
