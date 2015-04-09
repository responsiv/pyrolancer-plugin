<?php namespace Ahoy\Pyrolancer\Components;

use Auth;
use Input;
use ApplicationException;
use Cms\Classes\ComponentBase;
use Ahoy\Pyrolancer\Models\Project as ProjectModel;
use Ahoy\Pyrolancer\Models\ProjectMessage;
use Ahoy\Pyrolancer\Models\ProjectBid;
use Ahoy\Pyrolancer\Models\ProjectExtraDetail;

class Project extends ComponentBase
{

    use \Ahoy\Traits\ComponentUtils;

    public $project;

    public function componentDetails()
    {
        return [
            'name'        => 'Project Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [
            'slug' => [
                'title'       => 'Slug param name',
                'description' => 'The URL route parameter used for looking up the project by its slug. A hard coded slug can also be used.',
                'default'     => '{{ :slug }}',
                'type'        => 'string',
            ],
        ];
    }

    public function onRun()
    {
        $this->project = $this->lookupModel(new ProjectModel, function($query) {
            // $query->with('quotes');
        });
    }

    //
    // Client
    //

    public function onAddExtraDetails()
    {
        if (!$project = $this->lookupModelSecure(new ProjectModel))
            throw new ApplicationException('Action failed');

        $extra = new ProjectExtraDetail;
        $extra->description = post('description');
        $extra->project = $project;
        $extra->save();

        $this->page['project'] = $project;
    }

    //
    // Worker
    //

    public function onSubmitBid()
    {
        $user = $this->lookupUser();
        $project = $this->lookupModel(new ProjectModel);

        if (!$bid = $project->hasBid($user)) {
            $bid = new ProjectBid;
            $bid->user = $user;
            $bid->project = $project;
        }

        $bid->fill(post('Bid'));
        $bid->save();

        $this->page['bid'] = $bid;
        $this->page['project'] = $project;
        $this->page['success'] = true;
    }

    //
    // Messaging
    //

    public function onPostMessage()
    {
        $user = $this->lookupUser();
        $project = $this->lookupModel(new ProjectModel);

        $message = new ProjectMessage;
        $message->user = $user;
        $message->project = $project;
        $message->content = post('content');

        if ($parentId = post('parent_id'))
            $message->parent_id = $parentId;

        $message->save();

        return $this->page['message'] = $message;
    }

    public function onPostMessageReply()
    {
        $message = $this->onPostMessage();
        return $this->page['message'] = $message->getParent();
    }

    public function onUpdateMessage()
    {
        if (!$message = $this->lookupModelSecure(new ProjectMessage))
            throw new ApplicationException('Action failed');

        /*
         * Supported modes: edit, view, delete, save
         */
        $mode = post('mode', 'edit');
        if ($mode == 'save') {

            // if (__canPostToThis__)
            //     throw new ApplicationException('Action failed');

            $message->save(post());

        }
        elseif ($mode == 'delete') {
            $message->delete();
        }

        $this->page['mode'] = $mode;
        $this->page['message'] = $message;
    }

}