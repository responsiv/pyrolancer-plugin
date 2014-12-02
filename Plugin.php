<?php namespace Responsiv\Pyrolancer;

use Backend;
use System\Classes\PluginBase;
use RainLab\User\Models\User;

/**
 * Pyrolancer Plugin Information File
 */
class Plugin extends PluginBase
{

    public $require = ['RainLab.User'];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Pyrolancer',
            'description' => 'No description provided yet...',
            'author'      => 'Responsiv',
            'icon'        => 'icon-fire'
        ];
    }

    public function boot()
    {
        User::extend(function($model) {
            $model->hasOne['worker'] = ['Responsiv\Pyrolancer\Models\Worker'];
        });
    }

    public function registerNavigation()
    {
        return [
            'pyrolancer' => [
                'label'       => 'Freelance',
                'url'         => Backend::url('responsiv/pyrolancer/freelancers'),
                'icon'        => 'icon-briefcase',
                'permissions' => ['blog.*'],
                'order'       => 500,

                'sideMenu' => [
                    'freelancers' => [
                        'label'       => 'Freelancers',
                        'icon'        => 'icon-users',
                        'url'         => Backend::url('responsiv/pyrolancer/freelancers'),
                        'permissions' => ['pyrolancer.access_freelancers'],
                    ],
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
                ]

            ]
        ];
    }

    public function registerComponents()
    {
        return [
           '\Responsiv\Pyrolancer\Components\Project'        => 'project',
           '\Responsiv\Pyrolancer\Components\Projects'       => 'projects',
           '\Responsiv\Pyrolancer\Components\ProjectManage'  => 'projectManage',
           '\Responsiv\Pyrolancer\Components\ProjectSubmit'  => 'projectSubmit',
           '\Responsiv\Pyrolancer\Components\WorkerSkills'   => 'workerSkills',
           '\Responsiv\Pyrolancer\Components\WorkerRegister' => 'workerRegister',
           '\Responsiv\Pyrolancer\Components\ClientProjects' => 'clientProjects',
        ];
    }

}
