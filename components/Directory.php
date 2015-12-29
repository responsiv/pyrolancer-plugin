<?php namespace Ahoy\Pyrolancer\Components;

use Auth;
use Request;
use Ahoy\Pyrolancer\Models\Worker as WorkerModel;
use Ahoy\Pyrolancer\Models\Skill as SkillModel;
use Ahoy\Pyrolancer\Models\Vicinity as VicinityModel;
use Ahoy\Pyrolancer\Models\SkillCategory;
use Cms\Classes\ComponentBase;
use ApplicationException;

class Directory extends ComponentBase
{
    use \Ahoy\Traits\ComponentUtils;

    public $filterType;
    public $filterObject;

    public function componentDetails()
    {
        return [
            'name'        => 'Directory',
            'description' => 'For displaying a directory of workers'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        $this->handleFilter();

        $this->page['paginationCurrentUrl'] = $this->paginationCurrentUrl();
    }

    public function workers($options = null)
    {
        if ($options === null) {
            $options = $this->getFilterOptionsFromRequest();
        }

        return $this->lookupObject(__FUNCTION__, WorkerModel::listFrontEnd($options));
    }

    public function skillCategories()
    {
        return SkillCategory::applyVisible()->get();
    }

    public function activeFilters()
    {
        $selection = [
            'skills' => null,
            'countries' => null,
            'vicinity' => null,
            'sort' => null,
            'search' => null,
            'page' => null,
        ];

        if ($requestSelection = $this->getFilterOptionsFromRequest()) {
            $selection = array_merge($selection, $requestSelection);
        }

        return $selection;
    }

    public function paginationCurrentUrl()
    {
        $currentUrl = Request::url();
        $hasQuery = strpos($currentUrl, '?');
        if ($hasQuery !== false) {
            $currentUrl = substr($currentUrl, 0, $hasQuery);
        }

        $params = [];
        $params['page'] = '';

        return $currentUrl . '?' . http_build_query($params);
    }

    //
    // AJAX
    //

    public function onGetSkills()
    {
        $result = [];
        $result['skills'] = SkillModel::lists('name', 'id');
        return $result;
    }

    public function onFilterWorkers()
    {
        $options = post('Filter');
        $options['page'] = post('page', 1);
        $this->page['workers'] = $this->workers($options);
        $this->page['pageEventName'] = 'onFilterWorkers';
        $this->page['updatePartialName'] = 'directory/workers';
        $this->page['updateElement'] = '#partialDirectoryWorkers';
        $this->page['onSuccess'] = "directoryAfterPaginate()";
    }

    public function onLoadSkillsPopup()
    {
        if (!$worker = WorkerModel::find(post('id'))) {
            throw new ApplicationException('Action failed!');
        }

        $this->page['worker'] = $worker;
    }

    //
    // Filtering
    //

    protected function getFilterOptionsFromRequest()
    {
        $options = [];

        if ($searchQuery = input('search')) {
            $options['search'] = $searchQuery;
        }

        if ($pageNumber = input('page')) {
            $options['page'] = $pageNumber;
        }

        if ($this->filterType && $this->filterObject) {
            $options[$this->filterType] = (array) $this->filterObject->id;
        }

        return $options;
    }

    protected function handleFilter()
    {
        $filterType = strtolower($this->param('filter'));
        $filterValue = $this->param('with');
        if (!$filterType || !$filterValue) {
            return;
        }

        $filterObject = null;
        switch ($filterType) {
            case 'skill':
                $filterObject = SkillModel::whereSlug($filterValue)->first();
                $filterType = 'skills';
                break;
            case 'vicinity':
                $filterObject = VicinityModel::whereSlug($filterValue)->first();
                $filterType = 'vicinities';
                break;
        }

        if (!$filterObject) {
            return;
        }

        $this->filterType = $filterType;
        $this->filterObject = $filterObject;
    }

}
