<div class="panel panel-default">
    <div class="panel-body">
        <ul class="nav nav-pills">
            <li role="presentation" class="{!! $tools->fullUrlIs(route(Ticket\Ticketit\Models\Setting::grab('main_route') . '.index')) ? "active" : "" !!}">
                <a href="{{ route(Ticket\Ticketit\Models\Setting::grab('main_route') . '.index') }}">{{ trans('ticketit::lang.nav-active-tickets') }}
                    <span class="badge">
                         <?php 
                            if ($u->isAdmin()) {
                                echo Ticket\Ticketit\Models\Ticket::active()->count();
                            } elseif ($u->isAgent()) {
                                echo Ticket\Ticketit\Models\Ticket::active()->agentUserTickets($u->id)->count();
                            } else {
                                echo Ticket\Ticketit\Models\Ticket::userTickets($u->id)->active()->count();
                            }
                        ?>
                    </span>
                </a>
            </li>
            <li role="presentation" class="{!! $tools->fullUrlIs(route(Ticket\Ticketit\Models\Setting::grab('main_route') . '-complete')) ? "active" : "" !!}">
                <a href="{{ route(Ticket\Ticketit\Models\Setting::grab('main_route') . '-complete') }}">{{ trans('ticketit::lang.nav-completed-tickets') }}
                    <span class="badge">
                        <?php 
                            if ($u->isAdmin()) {
                                echo Ticket\Ticketit\Models\Ticket::complete()->count();
                            } elseif ($u->isAgent()) {
                                echo Ticket\Ticketit\Models\Ticket::complete()->agentUserTickets($u->id)->count();
                            } else {
                                echo Ticket\Ticketit\Models\Ticket::userTickets($u->id)->complete()->count();
                            }
                        ?>
                    </span>
                </a>
            </li>

            @if($u->isAdmin())
                <li role="presentation" class="{!! $tools->fullUrlIs(action('\Ticket\Ticketit\Controllers\DashboardController@index')) || Request::is($setting->grab('admin_route').'/indicator*') ? "active" : "" !!}">
                    <a href="{{ action('\Ticket\Ticketit\Controllers\DashboardController@index') }}">{{ trans('ticketit::admin.nav-dashboard') }}</a>
                </li>

                <li role="presentation" class="dropdown {!!
                    $tools->fullUrlIs(action('\Ticket\Ticketit\Controllers\StatusesController@index').'*') ||
                    $tools->fullUrlIs(action('\Ticket\Ticketit\Controllers\PrioritiesController@index').'*') ||
                    $tools->fullUrlIs(action('\Ticket\Ticketit\Controllers\AgentsController@index').'*') ||
                    $tools->fullUrlIs(action('\Ticket\Ticketit\Controllers\CategoriesController@index').'*') ||
                    $tools->fullUrlIs(action('\Ticket\Ticketit\Controllers\ConfigurationsController@index').'*') ||
                    $tools->fullUrlIs(action('\Ticket\Ticketit\Controllers\AdministratorsController@index').'*')
                    ? "active" : "" !!}">

                    <a class="dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                        {{ trans('ticketit::admin.nav-settings') }} <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li role="presentation" class="{!! $tools->fullUrlIs(action('\Ticket\Ticketit\Controllers\StatusesController@index').'*') ? "active" : "" !!}">
                            <a href="{{ action('\Ticket\Ticketit\Controllers\StatusesController@index') }}">{{ trans('ticketit::admin.nav-statuses') }}</a>
                        </li>
                        <li role="presentation"  class="{!! $tools->fullUrlIs(action('\Ticket\Ticketit\Controllers\PrioritiesController@index').'*') ? "active" : "" !!}">
                            <a href="{{ action('\Ticket\Ticketit\Controllers\PrioritiesController@index') }}">{{ trans('ticketit::admin.nav-priorities') }}</a>
                        </li>
                        <li role="presentation"  class="{!! $tools->fullUrlIs(action('\Ticket\Ticketit\Controllers\AgentsController@index').'*') ? "active" : "" !!}">
                            <a href="{{ action('\Ticket\Ticketit\Controllers\AgentsController@index') }}">{{ trans('ticketit::admin.nav-agents') }}</a>
                        </li>
                        <li role="presentation"  class="{!! $tools->fullUrlIs(action('\Ticket\Ticketit\Controllers\CategoriesController@index').'*') ? "active" : "" !!}">
                            <a href="{{ action('\Ticket\Ticketit\Controllers\CategoriesController@index') }}">{{ trans('ticketit::admin.nav-categories') }}</a>
                        </li>
                        <li role="presentation"  class="{!! $tools->fullUrlIs(action('\Ticket\Ticketit\Controllers\ConfigurationsController@index').'*') ? "active" : "" !!}">
                            <a href="{{ action('\Ticket\Ticketit\Controllers\ConfigurationsController@index') }}">{{ trans('ticketit::admin.nav-configuration') }}</a>
                        </li>
                        <li role="presentation"  class="{!! $tools->fullUrlIs(action('\Ticket\Ticketit\Controllers\AdministratorsController@index').'*') ? "active" : "" !!}">
                            <a href="{{ action('\Ticket\Ticketit\Controllers\AdministratorsController@index')}}">{{ trans('ticketit::admin.nav-administrator') }}</a>
                        </li>
                    </ul>
                </li>
            @endif

        </ul>
    </div>
</div>
