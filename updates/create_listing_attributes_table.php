<?php namespace Ahoy\Pyrolancer\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateAttributesTable extends Migration
{

    public function up()
    {
        Schema::create('ahoy_pyrolancer_attributes', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('type')->nullable();
            $table->string('name')->nullable();
            $table->string('label')->nullable();
            $table->string('code')->nullable();
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ahoy_pyrolancer_attributes');
    }

}