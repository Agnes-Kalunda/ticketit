<?php

namespace Ticket\Ticketit\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Ticket\Ticketit\Models\Configuration;
use Ticket\Ticketit\Models\Setting;

class ConfigurationsController extends BaseTicketController
{
    public function __construct()
    {
        // Only staff with admin privileges can access configurations
        $this->middleware('auth:web');
        $this->middleware('Ticket\Ticketit\Middleware\IsAdminMiddleware');
    }

    /**
     * Display a listing of the Setting.
     *
     * @return Response
     */
    public function index()
    {
        if (!$this->isAdmin()) {
            return redirect()->route('ticketit.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted'));
        }

        $configurations = Configuration::all();
        $configurations_by_sections = [
            'init' => [], 
            'email' => [], 
            'tickets' => [], 
            'perms' => [], 
            'editor' => [], 
            'other' => []
        ];

        
        $init_section = ['main_route', 'main_route_path', 'admin_route', 'admin_route_path', 
            'master_template', 'bootstrap_version', 'routes'];
        
        $email_section = ['status_notification', 'comment_notification', 'queue_emails', 
            'assigned_notification', 'email.template', 'email.header', 'email.signoff', 
            'email.signature', 'email.dashboard', 'email.google_plus_link', 'email.facebook_link', 
            'email.twitter_link', 'email.footer', 'email.footer_link', 'email.color_body_bg', 
            'email.color_header_bg', 'email.color_content_bg', 'email.color_footer_bg', 
            'email.color_button_bg'];
        
        $tickets_section = ['default_status_id', 'default_close_status_id', 
            'default_reopen_status_id', 'paginate_items'];
        
        $perms_section = ['agent_restrict', 'close_ticket_perm', 'reopen_ticket_perm'];
        
        $editor_section = ['editor_enabled', 'include_font_awesome', 'editor_html_highlighter', 
            'codemirror_theme', 'summernote_locale', 'summernote_options_json_file', 
            'purifier_config'];

        // Split configurations into sections for tabs
        foreach ($configurations as $config_item) {
            
            $config_item->value = $config_item->getShortContent(25, 'value');
            $config_item->default = $config_item->getShortContent(25, 'default');

            
            if (in_array($config_item->slug, $init_section)) {
                $configurations_by_sections['init'][] = $config_item;
            } elseif (in_array($config_item->slug, $email_section)) {
                $configurations_by_sections['email'][] = $config_item;
            } elseif (in_array($config_item->slug, $tickets_section)) {
                $configurations_by_sections['tickets'][] = $config_item;
            } elseif (in_array($config_item->slug, $perms_section)) {
                $configurations_by_sections['perms'][] = $config_item;
            } elseif (in_array($config_item->slug, $editor_section)) {
                $configurations_by_sections['editor'][] = $config_item;
            } else {
                $configurations_by_sections['other'][] = $config_item;
            }
        }

        return view('ticketit::admin.configuration.index', 
            compact('configurations', 'configurations_by_sections'));
    }

    /**
     * Show the form for creating a new Setting.
     *
     * @return Response
     */
    public function create()
    {
        if (!$this->isAdmin()) {
            return redirect()->route('ticketit.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted'));
        }

        return view('ticketit::admin.configuration.create');
    }

    /**
     * Store a newly created Configuration in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        if (!$this->isAdmin()) {
            return redirect()->route('ticketit.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted'));
        }

        $this->validate($request, [
            'slug'      => 'required',
            'default'   => 'required',
            'value'     => 'required',
        ]);

        $configuration = new Configuration();
        $configuration->create($request->all());

        Session::flash('configuration', 'Setting saved successfully.');
        Cache::forget('ticketit::settings'); // refresh cached settings
        
        return redirect()->action('\Ticket\Ticketit\Controllers\ConfigurationsController@index');
    }

    /**
     * Show the form for editing the specified Configuration.
     *
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        if (!$this->isAdmin()) {
            return redirect()->route('ticketit.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted'));
        }

        $configuration = Configuration::findOrFail($id);
        $should_serialize = Setting::is_serialized($configuration->value);
        $default_serialized = Setting::is_serialized($configuration->default);

        return view('ticketit::admin.configuration.edit', 
            compact('configuration', 'should_serialize', 'default_serialized'));
    }

    /**
     * Update the specified Configuration in storage.
     *
     * @param int     $id
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        if (!$this->isAdmin()) {
            return redirect()->route('ticketit.index')
                ->with('warning', trans('ticketit::lang.you-are-not-permitted'));
        }

        $configuration = Configuration::findOrFail($id);
        $value = $request->value;

        if ($request->serialize) {
            if (!Auth::guard('web')->attempt($request->only('password'), false)) {
                return back()->withErrors([trans('ticketit::admin.config-edit-auth-failed')]);
            }
            if (false === eval('$value = serialize('.$value.');')) {
                return back()->withErrors([trans('ticketit::admin.config-edit-eval-error')]);
            }
        }

        $configuration->update([
            'value' => $value,
            'lang' => $request->lang
        ]);

        Session::flash('configuration', trans('ticketit::lang.configuration-name-has-been-modified', 
            ['name' => $request->name]));
            
        
        Cache::forget('ticketit::settings');
        Cache::forget('ticketit::settings.'.$configuration->slug);

        return redirect()->action('\Ticket\Ticketit\Controllers\ConfigurationsController@index');
    }
}