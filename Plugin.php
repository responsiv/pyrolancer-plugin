<?php namespace Responsiv\Pyrolancer;

use View;
use Event;
use Backend;
use Cms\Classes\Theme;
use System\Classes\PluginBase;
use RainLab\User\Models\User as UserModel;
use RainLab\Location\Models\State as StateModel;
use RainLab\Location\Models\Country as CountryModel;
use Responsiv\Pyrolancer\Models\UserEventLog;
use Responsiv\Pyrolancer\Classes\Worker as JobWorker;
use Exception;

/**
 * Pyrolancer Plugin Information File
 */
class Plugin extends PluginBase
{

    public $require = ['RainLab.User', 'RainLab.Location', 'RainLab.UserPlus'];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Pyrolancer',
            'description' => 'A service based marketplace for online workers.',
            'author'      => 'Responsiv Internet',
            'icon'        => 'icon-fire'
        ];
    }

    public function register()
    {
        $this->registerConsoleCommand('jobs.run', 'Responsiv\Pyrolancer\Console\JobsRun');
    }

    public function boot()
    {
        UserModel::extend(function($model) {
            $model->hasOne['worker'] = ['Responsiv\Pyrolancer\Models\Worker', 'delete' => true, 'softDelete' => true];
            $model->hasOne['client'] = ['Responsiv\Pyrolancer\Models\Client', 'delete' => true, 'softDelete' => true];
            $model->hasOne['event_log'] = ['Responsiv\Pyrolancer\Models\UserEventLog', 'delete' => true, 'softDelete' => true];

            $model->bindEvent('model.afterCreate', function() use ($model) {
                UserEventLog::add(UserEventLog::TYPE_USER_CREATED, [
                    'user' => $model,
                    'createdAt' => $model->created_at
                ]);
            });
        });

        StateModel::extend(function($model) {
            $model->hasMany['vicinities'] = ['Responsiv\Pyrolancer\Models\Vicinity'];
        });

        CountryModel::extend(function($model) {
            $model->hasMany['user_count'] = ['RainLab\User\Models\User', 'count' => true];
        });

        Event::listen('backend.form.extendFields', function ($widget) {
            if (
                !$widget->getController() instanceof \RainLab\Pages\Controllers\Index ||
                !$widget->model instanceof \RainLab\Pages\Classes\MenuItem
            ) {
                return;
            }

            $widget->addTabFields([
                'viewBag[visibleTo]' => [
                    'tab' => 'User group',
                    'commentAbove' => 'Make this menu item visible only to the following groups.',
                    'type' => 'dropdown',
                    'default' => 'all',
                    'options' => [
                        'all' => 'Everyone',
                        'guests' => 'Guests',
                        'users' => 'Users',
                        'clients' => 'Clients',
                        'workers' => 'Workers',
                        'hybrid' => 'Hybrid',
                    ]
                ]
            ]);
        });

        $theme = Theme::getActiveTheme();
        if ($theme->hasCustomData()) {
            View::share('site_name', $theme->site_name);
        }
    }

    public function registerNavigation()
    {
        return [
            'pyrolancer' => [
                'label'       => 'Freelance',
                'url'         => Backend::url('responsiv/pyrolancer/projects'),
                'icon'        => 'icon-briefcase',
                'iconSvg'     => 'plugins/responsiv/pyrolancer/assets/images/briefcase-icon.svg',
                'permissions' => ['blog.*'],
                'order'       => 10,

                'sideMenu' => [
                    'projects' => [
                        'label'       => 'Projects',
                        'icon'        => 'icon-trophy',
                        'url'         => Backend::url('responsiv/pyrolancer/projects'),
                        'permissions' => ['pyrolancer.access_projects'],
                    ],
                    'skills' => [
                        'label'       => 'Skills',
                        'icon'        => 'icon-graduation-cap',
                        'url'         => Backend::url('responsiv/pyrolancer/skills'),
                        'permissions' => ['pyrolancer.access_skills'],
                    ],
                    'workers' => [
                        'label'       => 'Workers',
                        'icon'        => 'icon-users',
                        'url'         => Backend::url('responsiv/pyrolancer/workers'),
                        'permissions' => ['pyrolancer.access_workers'],
                    ],
                    'clients' => [
                        'label'       => 'Clients',
                        'icon'        => 'icon-bell',
                        'url'         => Backend::url('responsiv/pyrolancer/clients'),
                        'permissions' => ['pyrolancer.access_clients'],
                    ],
                    'portfolios' => [
                        'label'       => 'Portfolios',
                        'icon'        => 'icon-camera',
                        'url'         => Backend::url('responsiv/pyrolancer/portfolios'),
                        'permissions' => ['pyrolancer.access_portfolios'],
                    ],
                ]

            ]
        ];
    }

    public function registerComponents()
    {
        return [
           '\Responsiv\Pyrolancer\Components\Jobs'              => 'jobs',
           '\Responsiv\Pyrolancer\Components\AttributeValues'   => 'attributeValues',
           '\Responsiv\Pyrolancer\Components\Activity'          => 'activity',
           '\Responsiv\Pyrolancer\Components\Account'           => 'account',
           '\Responsiv\Pyrolancer\Components\Collab'            => 'collab',
           '\Responsiv\Pyrolancer\Components\CollabUpdate'      => 'collabUpdate',
           '\Responsiv\Pyrolancer\Components\ContactForm'       => 'contactForm',
           '\Responsiv\Pyrolancer\Components\Dashboard'         => 'dashboard',
           '\Responsiv\Pyrolancer\Components\Directory'         => 'directory',
           '\Responsiv\Pyrolancer\Components\SeoDirectory'      => 'seoDirectory',
           '\Responsiv\Pyrolancer\Components\Favorites'         => 'favorites',
           '\Responsiv\Pyrolancer\Components\Portfolios'        => 'portfolios',
           '\Responsiv\Pyrolancer\Components\Profile'           => 'profile',
           '\Responsiv\Pyrolancer\Components\Project'           => 'project',
           '\Responsiv\Pyrolancer\Components\ProjectSubmit'     => 'projectSubmit',
           '\Responsiv\Pyrolancer\Components\Worker'            => 'worker',
           '\Responsiv\Pyrolancer\Components\WorkerManage'      => 'workerManage',
           '\Responsiv\Pyrolancer\Components\WorkerRegister'    => 'workerRegister',
           '\Responsiv\Pyrolancer\Components\WorkerPortfolio'   => 'workerPortfolio',
           '\Responsiv\Pyrolancer\Components\WorkerTestimonial' => 'workerTestimonial',
           '\Responsiv\Pyrolancer\Components\ClientProjects'    => 'clientProjects',
        ];
    }

    public function registerMailTemplates()
    {
        return [
            'responsiv.pyrolancer::mail.project-approval-request' => 'Sent to managers when a new project needs approval.',
            'responsiv.pyrolancer::mail.project-reapproval-request' => 'Sent to managers when a previously rejected project has been resubmitted for approval.',
            'responsiv.pyrolancer::mail.worker-testimonial-request' => "Sent to the worker's previous client or workplace, requesting they submit a testimonial about the worker.",
            'responsiv.pyrolancer::mail.worker-testimonial-complete' => "Sent to the worker when a previous client has left a testimonial about them.",
            'responsiv.pyrolancer::mail.worker-alert' => "Sent to a worker when an urgent project is submitted.",
            'responsiv.pyrolancer::mail.worker-digest' => "Sent to a worker with a compilation of related projects.",
            'responsiv.pyrolancer::mail.worker-bid-accepted' => "Sent to the worker when their bid on a project was accepted by the client.",
            'responsiv.pyrolancer::mail.client-project-approved' => 'Sent to the client when their project is approved.',
            'responsiv.pyrolancer::mail.client-project-rejected' => 'Sent to the client when their project is rejected.',
            'responsiv.pyrolancer::mail.client-bid-confirmed' => "Sent to the client when a project enters development status.",
            'responsiv.pyrolancer::mail.client-bid-declined' => "Sent to the client when the worker's bid was chosen but declined the offer.",
            'responsiv.pyrolancer::mail.client-digest' => "Sent to a client when a new bid or question is placed on their project.",
            'responsiv.pyrolancer::mail.client-project-expired' => "Sent to the client when one of their projects has expired.",
            'responsiv.pyrolancer::mail.collab-message' => "Sent when a user submits a new message to the project collaboration area.",
            'responsiv.pyrolancer::mail.collab-update' => "Sent when a user updates an exisiting collaboration area message with a major update.",
            'responsiv.pyrolancer::mail.collab-terminated' => "Someone terminated the project collaboration.",
            'responsiv.pyrolancer::mail.collab-complete' => "Someone marked the project collaboration as complete.",
            'responsiv.pyrolancer::mail.collab-review' => "Sent to the user when a review is left about them.",
            'responsiv.pyrolancer::mail.profile-contact' => "Sent to the user when a message is sent from their profile.",
        ];
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'Freelance settings',
                'description' => 'Manage freelance features and settings.',
                'category'    => 'Freelance',
                'icon'        => 'icon-briefcase',
                'class'       => 'Responsiv\Pyrolancer\Models\Settings',
                'order'       => 500,
                'keywords'    => 'project worker client'
            ]
        ];
    }

    /**
     * Register new Twig variables
     * @return array
     */
    public function registerMarkupTags()
    {
        return [
            'filters' => [
                'see_more' => [\Responsiv\Pyrolancer\Helpers\Html::class, 'seeMore']
            ]
        ];
    }

    public function registerSchedule($schedule)
    {
        $schedule->call(function(){
            JobWorker::instance()->process();
        })->everyFiveMinutes();
    }
}
