<?php namespace Ahoy\Pyrolancer\Components;

use Auth;
use Mail;
use Flash;
use Request;
use Redirect;
use Ahoy\Pyrolancer\Models\Project as ProjectModel;
use Ahoy\Pyrolancer\Models\ProjectMessage as ProjectMessageModel;
use System\Models\File as FileModel;
use Cms\Classes\ComponentBase;
use Cms\Classes\Page;
use ApplicationException;
use Exception;

class CollabUpdate extends ComponentBase
{
    use \Ahoy\Traits\ComponentUtils;

    public function componentDetails()
    {
        return [
            'name'        => 'Collab update',
            'description' => 'Update a collaboration message content or attachments'
        ];
    }

    public function defineProperties()
    {
        return [
            'collabPage' => [
                'title'       => 'Collaboration Page',
                'description' => 'Page name to use for the collaboration area.',
                'type'        => 'dropdown',
            ],
        ];
    }

    public function getPropertyOptions($property)
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function init()
    {
        if ($message = $this->message()) {
            $component = $this->addComponent('fileUploader', 'messageFileUploader', ['deferredBinding' => false]);
            $component->bindModel('attachments', $message);
        }
    }

    public function returnUrl()
    {
        if (!$message = $this->message()) {
            return null;
        }

        $collabPage = $this->property('collabPage');

        $returnUrl = $this->pageUrl($collabPage, [
            'id' => $message->project->id,
            'slug' => $message->project->slug
        ]).'#message-'.$message->id;

        return $returnUrl;
    }

    public function message()
    {
        return $this->lookupObject(__FUNCTION__, function() {
            $user = $this->lookupUser();

            if (!$messageId = $this->param('id')) {
                return null;
            }

            $message = ProjectMessageModel::find($messageId);

            if (!$message || !$message->isOwner()) {
                return null;
            }

            return $message;
        });
    }

    public function onUpdateMessage()
    {
        try {
            if (!$message = $this->message()) {
                throw new ApplicationException('Message cannot be found.');
            }

            $message->fill(post());
            $message->save();

            Flash::success('The message has been updated successfully.');

            /*
             * Notify other user
             */
            if (!post('minor_update', true)) {
                $project = $message->project;
                $project->resetUrlComponent('collab');

                $otherUser = $project->isOwner() ? $project->chosen_user : $project->user;
                $params = [
                    'project' => $project,
                    'user' => $otherUser,
                    'otherUser' => $message->user,
                    'collabMessage' => $message,
                ];
                Mail::sendTo($otherUser, 'ahoy.pyrolancer::mail.collab-update', $params);
            }

            /*
             * Redirect to the collab page
             */
            $redirectUrl = $this->returnUrl();

            if ($redirectUrl = post('redirect', $redirectUrl)) {
                return Redirect::to($redirectUrl);
            }
        }
        catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
    }

}