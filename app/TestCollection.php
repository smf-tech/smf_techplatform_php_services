<?php 
namespace App;


use Illuminate\Database\Eloquent\Model;


class TestCollection extends \Jenssegers\Mongodb\Eloquent\Model
{
     protected $table = 'test_collection';
     
}