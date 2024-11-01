<?php

namespace Ticket\Ticketit\Seeds;

use Illuminate\Database\Seeder;
use Ticket\Ticketit\Helpers\LaravelVersion;
use Ticket\Ticketit\Models\Setting;

class SettingsTableSeeder extends Seeder
{
    public $config = [];

    /**
     * Seed the Plans table.
     */
    public function run()
    {
        $defaults = [];

        $defaults = $this->cleanupAndMerge($this->getDefaults(), $this->config);

        foreach ($defaults as $slug => $column) {
            $setting = Setting::bySlug($slug);

            if ($setting->count()) {
                $setting->first()->update([
                    'default' => $column,
                ]);
            } else {
                Setting::create([
                    'lang'    => null,
                    'slug'    => $slug,
                    'value'   => $column,
                    'default' => $column,
                ]);
            }
        }
    }

    /**
     * Takes config/ticketit.php, merge with package defaults, and returns serialized array.
     *
     * @param $defaults
     * @param $config
     *
     * @return array
     */
    public function cleanupAndMerge($defaults, $config)
    {
        $merged = array_merge($defaults, $config);

        foreach ($merged as $slug => $column) {
            if (is_array($column)) {
                foreach ($column as $key => $value) {
                    if ($value == 'yes') {
                        $merged[$slug][$key] = true;
                    }

                    if ($value == 'no') {
                        $merged[$slug][$key] = false;
                    }
                }

                $merged[$slug] = serialize($merged[$slug]);
            }

            if ($column == 'yes') {
                $merged[$slug] = true;
            }

            if ($column == 'no') {
                $merged[$slug] = false;
            }
        }

        return (array) $merged;
    }

    public function getDefaults()
    {
        return [
            /*
             * Model and Authentication configurations
             */
            'models' => [
                'customer' => 'App\Customer',
                'user' => 'App\User',
            ],

            'guards' => [
                'customer' => 'customer',
                'user' => 'web',
            ],

            /*
             * Route configurations
             */
            'main_route'      => 'tickets',
            'main_route_path' => 'tickets',
            'admin_route'      => 'tickets-admin',
            'admin_route_path' => 'tickets-admin',
            'customer_route'      => 'customer/tickets', 
            'customer_route_path' => 'customer/tickets',

            /*
             * Permission settings
             */
            'customer_tickets' => [
                'can_create' => 'yes',
                'can_view_own' => 'yes',
                'can_comment' => 'yes',
                'can_edit_own' => 'no',
                'can_delete_own' => 'no'
            ],

            /*
             * Template adherence settings
             */
            'master_template' => 'master',

            /*
             * Bootstrap version settings
             */
            'bootstrap_version' => LaravelVersion::min('5.6') ? '4' : '3',

            /*
             * Email template settings
             */
            'email.template' => 'ticketit::emails.templates.ticketit',
            'email.header'           => 'Ticket Update',
            'email.signoff'          => 'Thank you for your patience!',
            'email.signature'        => 'Your friends',
            'email.dashboard'        => 'My Dashboard',
            'email.google_plus_link' => '#',
            'email.facebook_link'    => '#',
            'email.twitter_link'     => '#',
            'email.footer'           => 'Powered by Ticketit',
            'email.footer_link'      => 'https://github.com/thekordy/ticketit',
            'email.color_body_bg'    => '#FFFFFF',
            'email.color_header_bg'  => '#44B7B7',
            'email.color_content_bg' => '#F46B45',
            'email.color_footer_bg'  => '#414141',
            'email.color_button_bg'  => '#AC4D2F',

            /*
             * Status settings
             */
            'default_status_id' => 1,
            'default_close_status_id' => false,
            'default_reopen_status_id' => false,

            /*
             * Pagination settings
             */
            'paginate_items' => 10,
            'length_menu' => [[10, 50, 100], [10, 50, 100]],

            /*
             * Notification settings
             */
            'status_notification' => 'yes',
            'comment_notification' => 'yes',
            'queue_emails' => 'no',
            'assigned_notification' => 'yes',
            'customer_notification' => 'yes',

            /*
             * Agent settings
             */
            'agent_restrict' => 'no',

            /*
             * Permission settings for different roles
             */
            'close_ticket_perm' => [
                'owner' => 'yes', 
                'agent' => 'yes', 
                'admin' => 'yes',
                'customer' => 'no'
            ],
            'reopen_ticket_perm' => [
                'owner' => 'yes', 
                'agent' => 'yes', 
                'admin' => 'yes',
                'customer' => 'yes'
            ],

            /*
             * Delete confirmation settings
             */
            'delete_modal_type' => 'builtin',

            /*
             * Editor settings
             */
            'editor_enabled' => 'yes',
            'include_font_awesome' => 'yes',
            'summernote_locale' => 'en',
            'editor_html_highlighter' => 'yes',
            'codemirror_theme' => 'monokai',
            'summernote_options_json_file' => 'vendor/kordy/ticketit/src/JSON/summernote_init.json',

            /*
             * Purifier settings
             */
            'purifier_config' => [
                'HTML.SafeIframe'      => 'true',
                'URI.SafeIframeRegexp' => '%^(http://|https://|//)(www.youtube.com/embed/|player.vimeo.com/video/)%',
                'URI.AllowedSchemes'   => ['data' => true, 'http' => true, 'https' => true, 'mailto' => true, 'ftp' => true],
            ],

            /*
             * Routes settings
             */
            'routes' => base_path('vendor/ticket/ticketit/src').'/routes.php',
        ];
    }

}