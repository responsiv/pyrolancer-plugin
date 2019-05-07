<?php namespace Responsiv\Pyrolancer\Controllers;

use File;
use Lang;
use Flash;
use Markdown;
use Backend;
use BackendMenu;
use Backend\Classes\Controller;
use Responsiv\Pyrolancer\Models\Project as ProjectModel;

/**
 * Projects Back-end Controller
 */
class Projects extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController',
        'Backend.Behaviors.RelationController',
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
    public $relationConfig = 'config_relation.yaml';

    public $actionAssets = [
        'preview' => [
            '/plugins/responsiv/pyrolancer/assets/css/pyrolancer.css',
            '/plugins/responsiv/pyrolancer/assets/js/project-preview.js',
        ]
    ];

    public function __construct()
    {
        parent::__construct();

        $this->loadAssets();

        BackendMenu::setContext('Responsiv.Pyrolancer', 'pyrolancer', 'projects');
    }

    protected function loadAssets()
    {
        if (!isset($this->actionAssets[$this->action])) {
            return;
        }

        foreach ($this->actionAssets[$this->action] as $asset) {
            $ext = File::extension($asset);

            if ($ext == "js") {
                $this->addJs($asset);
            }
            elseif ($ext == "css") {
                $this->addCss($asset);
            }
            else {
                $this->addRss($asset);
            }
        }
    }

    public function listExtendQuery($query)
    {
        $query->withTrashed();
    }

    public function formExtendQuery($query)
    {
        $query->withTrashed();
    }

    public function formExtendModel($model)
    {
        $model->revisionsEnabled = false;
        return $model;
    }

    /**
     * Force delete a project.
     */
    public function update_onDelete($recordId = null)
    {
        $model = $this->formFindModelObject($recordId);

        $model->forceDelete();

        Flash::success(Lang::get('backend::lang.form.delete_success'));

        if ($redirect = $this->makeRedirect('delete', $model)) {
            return $redirect;
        }
    }

    //
    // Preview
    //

    public function preview($recordId = null, $context = null)
    {
        $result = $this->asExtension('FormController')->preview($recordId, $context);
        if ($this->fatalError) {
            return $result;
        }

        return $result;
    }

    public function preview_onLoadRejectForm($recordId = null)
    {
        return $this->makePartial('reject_form');
    }

    public function preview_onApprove($recordId = null)
    {
        $model = $this->formFindModelObject($recordId);
        $model->markApproved();

        Flash::success('This project has been approved.');

        return $this->redirectToNextUnapprovedProject();
    }

    public function preview_onReject($recordId = null)
    {
        $model = $this->formFindModelObject($recordId);

        if ($reason = trim(post('reason'))) {
            $model->markRejected($reason);
            Flash::success('This project has been rejected.');

            return $this->redirectToNextUnapprovedProject();
        }
        else {
            throw new ApplicationException('Please supply a reason.');
        }
    }

    public function preview_onFlagSpam($recordId = null)
    {
        $model = $this->formFindModelObject($recordId);
        $model->user->ban();
        $model->forceDelete();

        Flash::success('Project marked as spam.');

        return $this->redirectToNextUnapprovedProject();
    }

    public function preview_onRefreshConversationPreview()
    {
        $previewHtml = Markdown::parse(trim(post('reason')));

        return [
            'preview' => $previewHtml
        ];
    }

    protected function redirectToNextUnapprovedProject()
    {
        $project = ProjectModel::applyStatus(ProjectModel::STATUS_PENDING)->first();

        $redirectUri = $project ? 'projects/preview/'.$project->id : 'projects';

        return Backend::redirect('responsiv/pyrolancer/'.$redirectUri);
    }
}
