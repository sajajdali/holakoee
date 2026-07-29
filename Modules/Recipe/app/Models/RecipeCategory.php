<?php

namespace Modules\Recipe\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Recipe\Database\factories\RecipeCategoryFactory;

class RecipeCategory extends Model
{
    use HasFactory;

    protected $casts = [
        'detail' => 'json'
    ];

    protected $table = 'recipe_categories';

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id'];

    public function recipes()
    {
        return $this->hasMany(Recipe::class , 'category_id');
    }
    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

}
