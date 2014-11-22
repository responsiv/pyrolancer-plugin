<?php namespace Responsiv\Pyrolancer\Models;

use Model;

/**
 * ProjectExtraDetail Model
 */
class ProjectExtraDetail extends Model
{

    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'responsiv_pyrolancer_project_extra_details';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /*
     * Validation
     */
    public $rules = [
        'description' => 'required',
    ];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'project' => ['Responsiv\Pyrolancer\Models\Project'],
    ];

}